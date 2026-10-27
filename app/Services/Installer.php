<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use App\Core\DB;
use App\Core\Migrator;
use App\Core\Settings;

/**
 * Performs the installation (used by the web wizard and by bin/install.php).
 * Writes config/config.php, creates the platform (master) database and, in
 * single mode, the one shop database. On failure everything is rolled back.
 */
final class Installer
{
    /**
     * @param array  $db   host, port, user, pass, platform_db, tenant_prefix, per_tenant_user
     * @param string $mode 'single' | 'multi'
     * @param array  $data company_name, company_name_ar, admin_name, admin_email, admin_password,
     *                     locale, primary, currency, currency_symbol, base_domain, demo
     */
    public static function run(array $db, string $mode, array $data): void
    {
        $config = [
            'installed' => false,
            'app_name' => 'Matjar',
            'app_key' => base64_encode(random_bytes(32)),
            'mode' => $mode === 'multi' ? 'multi' : 'single',
            'base_domain' => strtolower(trim((string) ($data['base_domain'] ?? ''))),
            'timezone' => 'UTC',
            'debug' => false,
            'db' => $db,
        ];

        $createdPlatformDb = false;
        try {
            Config::write($config);

            $server = DB::server();
            $createdPlatformDb = !$server->value('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?', [$db['platform_db']]);
            $server->exec('CREATE DATABASE IF NOT EXISTS ' . Database::ident($db['platform_db']) . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $platform = DB::platform();
            if ($platform->value("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'tenants'", [$db['platform_db']])) {
                $createdPlatformDb = false;
                throw new \RuntimeException("Database {$db['platform_db']} already contains an installation.");
            }
            Migrator::run($platform, BASE_PATH . '/database/platform');

            Settings::setMany(array_merge(Theme::defaults(), [
                'company.name' => $data['company_name'],
                'company.name_ar' => (string) ($data['company_name_ar'] ?? ''),
                'theme.default_locale' => $data['locale'] ?? 'en',
                'theme.primary' => $data['primary'] ?? '#7c3aed',
                'theme.admin_menu_layout' => 'sidebar',
                'platform.allow_signup' => $mode === 'multi' ? '1' : '0',
                'theme.hero_title_en' => 'Launch your online shop in minutes',
                'theme.hero_title_ar' => 'أطلق متجرك الإلكتروني في دقائق',
                'theme.hero_subtitle_en' => 'Live ERP stock sync, delivery tracking, Arabic & English, and your own brand. Every shop gets its own secure database.',
                'theme.hero_subtitle_ar' => 'مزامنة مباشرة للمخزون، تتبع التوصيل، عربي وإنجليزي، وهويتك الخاصة. لكل متجر قاعدة بيانات آمنة خاصة به.',
            ]), $platform, 'platform_settings');

            $owner = [
                'owner_name' => $data['admin_name'],
                'owner_email' => $data['admin_email'],
                'owner_password' => $data['admin_password'],
                'locale' => $data['locale'] ?? 'en',
                'primary' => $data['primary'] ?? '#7c3aed',
                'currency' => ($data['currency'] ?? '') ?: 'USD',
                'currency_symbol' => ($data['currency_symbol'] ?? '') ?: '$',
            ];

            if ($mode === 'multi') {
                $plans = [
                    ['Starter', 'المبتدئ', 0, 50, 2, "Up to 50 products\n2 staff accounts\nLive delivery tracking", 0],
                    ['Business', 'الأعمال', 29, 1000, 10, "Up to 1,000 products\n10 staff accounts\nERP inventory integration\nCourier API integration", 1],
                    ['Enterprise', 'المؤسسات', 99, null, null, "Unlimited products\nUnlimited staff\nCustom domain\nPriority support", 0],
                ];
                $firstPlan = null;
                foreach ($plans as $i => [$name, $nameAr, $price, $maxP, $maxS, $features, $featured]) {
                    $id = $platform->insert('plans', [
                        'name' => $name, 'name_ar' => $nameAr, 'price' => $price, 'max_products' => $maxP, 'max_staff' => $maxS,
                        'features' => $features, 'is_featured' => $featured, 'sort' => $i, 'created_at' => now(),
                    ]);
                    $firstPlan ??= $id;
                }
                Settings::setMany(['platform.default_plan_id' => (string) $firstPlan], $platform, 'platform_settings');

                $platform->insert('platform_admins', [
                    'name' => $data['admin_name'],
                    'email' => strtolower((string) $data['admin_email']),
                    'password' => password_hash((string) $data['admin_password'], PASSWORD_DEFAULT),
                    'created_at' => now(),
                ]);

                if (!empty($data['demo'])) {
                    TenantProvisioner::create($owner + ['slug' => 'demo', 'name' => 'Demo Store', 'name_ar' => 'متجر تجريبي', 'plan_id' => $firstPlan, 'demo' => true]);
                }
            } else {
                TenantProvisioner::create($owner + [
                    'slug' => 'main',
                    'name' => $data['company_name'],
                    'name_ar' => (string) ($data['company_name_ar'] ?? ''),
                    'demo' => !empty($data['demo']),
                ]);
            }

            $config['installed'] = true;
            Config::write($config);
        } catch (\Throwable $e) {
            if ($createdPlatformDb) {
                try {
                    DB::server()->exec('DROP DATABASE IF EXISTS ' . Database::ident($db['platform_db']));
                } catch (\Throwable) {
                }
            }
            @unlink(Config::path());
            throw $e;
        }
    }
}
