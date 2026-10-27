<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Crypto;
use App\Core\Database;
use App\Core\DB;
use App\Core\Migrator;
use App\Core\Settings;

/**
 * Creates a shop: its own MySQL database (and optionally its own MySQL user),
 * runs the shop schema, seeds settings + the shop admin, and registers it in
 * the platform `tenants` table.
 */
final class TenantProvisioner
{
    public const RESERVED = ['www', 'admin', 'api', 'platform', 'install', 'assets', 'uploads', 'app', 'mail', 's'];

    /** Database names a shop may never take (MySQL system schemas + the master DB). */
    public const RESERVED_DATABASES = ['mysql', 'information_schema', 'performance_schema', 'sys', 'master', 'test'];

    /** Shop database name: optional prefix + the shop address, e.g. "eagle-electronics". */
    public static function databaseName(string $slug): string
    {
        return substr((string) Config::get('db.tenant_prefix', '') . $slug, 0, 64);
    }

    /**
     * @param array{slug: string, name: string, name_ar?: string, owner_name: string, owner_email: string, owner_password: string,
     *              plan_id?: ?int, domain?: ?string, status?: string, locale?: string, currency?: string, currency_symbol?: string,
     *              primary?: string, demo?: bool} $data
     */
    public static function create(array $data): int
    {
        $platform = DB::platform();
        $slug = strtolower(trim($data['slug']));
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) || strlen($slug) > 40 || in_array($slug, self::RESERVED, true)) {
            throw new \InvalidArgumentException(__('Shop address is invalid or reserved.'));
        }
        if ($platform->value('SELECT 1 FROM tenants WHERE slug = ?', [$slug])) {
            throw new \InvalidArgumentException(__('This shop address is already taken.'));
        }
        if (!empty($data['domain']) && $platform->value('SELECT 1 FROM tenants WHERE domain = ?', [strtolower($data['domain'])])) {
            throw new \InvalidArgumentException(__('This domain is already used by another shop.'));
        }

        $dbName = self::databaseName($slug);
        if (in_array(strtolower($dbName), self::RESERVED_DATABASES, true) || $dbName === (string) Config::get('db.platform_db')) {
            throw new \InvalidArgumentException(__('Shop address is invalid or reserved.'));
        }
        $server = DB::server();
        if ($server->value('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?', [$dbName])) {
            // Never touch a database that is not ours (other sites may share this MySQL server).
            throw new \InvalidArgumentException(__('This shop address is already taken.'));
        }

        $server->exec('CREATE DATABASE ' . Database::ident($dbName) . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        $dbUser = null;
        $dbPass = null;
        try {
            if (Config::get('db.per_tenant_user')) {
                // Dedicated MySQL account that can only touch this shop's database.
                $dbUser = 'mj_' . substr(hash('sha256', $dbName), 0, 20);
                $plainPass = Crypto::token(24);
                $host = (string) Config::get('db.tenant_user_host', '%');
                $q = $server->pdo();
                $account = $q->quote($dbUser) . '@' . $q->quote($host);
                $server->exec("CREATE USER {$account} IDENTIFIED BY " . $q->quote($plainPass));
                $server->exec('GRANT ALL PRIVILEGES ON ' . Database::ident($dbName) . ".* TO {$account}");
                $dbPass = Crypto::encrypt($plainPass);
            }

            $tenantRow = ['db_name' => $dbName, 'db_user' => $dbUser, 'db_pass' => $dbPass];
            $db = DB::forTenant($tenantRow);
            Migrator::run($db, BASE_PATH . '/database/tenant');
            self::seed($db, $data);
            if (!empty($data['demo'])) {
                DemoSeeder::run($db);
            }

            return $platform->insert('tenants', [
                'slug' => $slug,
                'name' => $data['name'],
                'domain' => !empty($data['domain']) ? strtolower($data['domain']) : null,
                'plan_id' => $data['plan_id'] ?? null,
                'status' => $data['status'] ?? 'active',
                'db_name' => $dbName,
                'db_user' => $dbUser,
                'db_pass' => $dbPass,
                'owner_name' => $data['owner_name'],
                'owner_email' => strtolower($data['owner_email']),
                'trial_ends_at' => ($data['status'] ?? '') === 'trial' ? date('Y-m-d', strtotime('+14 days')) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $server->exec('DROP DATABASE IF EXISTS ' . Database::ident($dbName));
            if ($dbUser) {
                self::dropUser($server, $dbUser);
            }
            throw $e;
        }
    }

    private static function seed(Database $db, array $data): void
    {
        $settings = Theme::defaults() + [
            'company.name' => $data['name'],
            'company.name_ar' => $data['name_ar'] ?? '',
            'company.email' => strtolower($data['owner_email']),
            'store.currency' => $data['currency'] ?? 'USD',
            'store.currency_symbol' => $data['currency_symbol'] ?? '$',
            'store.tax_rate' => '0',
            'store.order_prefix' => 'ORD',
            'store.allow_guest_checkout' => '1',
            'payment.cod' => '1',
            'payment.bank' => '0',
            'inventory.provider' => 'local',
            'inventory.realtime' => '0',
            'inventory.cache_ttl' => '300',
            'inventory.sync_interval' => '15',
            'inventory.webhook_secret' => Crypto::token(24),
            'delivery.tracking_prefix' => 'MJ',
        ];
        if (!empty($data['locale'])) {
            $settings['theme.default_locale'] = $data['locale'];
        }
        if (!empty($data['primary']) && preg_match('/^#[0-9a-fA-F]{6}$/', $data['primary'])) {
            $settings['theme.primary'] = $data['primary'];
        }
        Settings::setMany($settings, $db, 'settings');

        $db->insert('users', [
            'name' => $data['owner_name'],
            'email' => strtolower($data['owner_email']),
            'password' => password_hash($data['owner_password'], PASSWORD_DEFAULT),
            'role' => 'admin',
            'created_at' => now(),
        ]);
    }

    public static function delete(array $tenant): void
    {
        $server = DB::server();
        $server->exec('DROP DATABASE IF EXISTS ' . Database::ident($tenant['db_name']));
        if (!empty($tenant['db_user'])) {
            self::dropUser($server, (string) $tenant['db_user']);
        }
        DB::platform()->delete('tenants', 'id = ?', [$tenant['id']]);
        $dir = BASE_PATH . '/public/uploads/' . $tenant['slug'];
        if (is_dir($dir) && preg_match('/^[a-z0-9-]+$/', $tenant['slug'])) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($it as $file) {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }
            rmdir($dir);
        }
    }

    private static function dropUser(Database $server, string $user): void
    {
        $q = $server->pdo();
        $server->exec('DROP USER IF EXISTS ' . $q->quote($user) . '@' . $q->quote((string) Config::get('db.tenant_user_host', '%')));
    }
}
