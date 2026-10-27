<?php
declare(strict_types=1);

/**
 * Non-interactive installer (same result as the /install web wizard).
 *
 *   php bin/install.php --mode=multi --db-user=matjar --db-pass=secret \
 *       --company="SysEagles Shop" --admin-name="Admin" \
 *       --admin-email=admin@example.com --admin-password='S3cret!!' [--demo]
 *
 * Options (defaults in brackets):
 *   --mode=single|multi [multi]   --db-host [127.0.0.1]   --db-port [3306]
 *   --db-user  --db-pass          --platform-db [master]
 *   --tenant-prefix ['' = each shop DB is named after the shop]  --per-tenant-user (create a MySQL user per shop)
 *   --company  --company-ar  --admin-name  --admin-email  --admin-password
 *   --locale=en|ar [en]  --primary [#7c3aed]  --currency [USD]  --currency-symbol [$]
 *   --base-domain (multi: shops at {slug}.base-domain)  --demo (sample data)
 * Every option can also come from the environment, e.g. MATJAR_DB_PASS, MATJAR_ADMIN_PASSWORD.
 */

use App\Core\Config;
use App\Core\DB;
use App\Services\Installer;

require dirname(__DIR__) . '/app/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$opts = [];
foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--([a-z-]+)(?:=(.*))?$/s', $arg, $m)) {
        $opts[$m[1]] = $m[2] ?? '1';
    }
}
$opt = static function (string $name, ?string $default = null) use ($opts): ?string {
    $env = getenv('MATJAR_' . strtoupper(str_replace('-', '_', $name)));
    return $opts[$name] ?? ($env !== false && $env !== '' ? $env : $default);
};
$fail = static function (string $msg): never {
    fwrite(STDERR, "Error: {$msg}\n");
    exit(1);
};

if (Config::installed()) {
    $fail('Matjar is already installed (config/config.php exists).');
}

$mode = $opt('mode', 'multi') === 'single' ? 'single' : 'multi';
$db = [
    'host' => (string) $opt('db-host', '127.0.0.1'),
    'port' => (int) $opt('db-port', '3306'),
    'user' => (string) $opt('db-user', ''),
    'pass' => (string) $opt('db-pass', ''),
    'platform_db' => (string) $opt('platform-db', 'master'),
    'tenant_prefix' => (string) $opt('tenant-prefix', ''),
    'per_tenant_user' => $opt('per-tenant-user') === '1',
];
$data = [
    'company_name' => (string) $opt('company', 'Matjar'),
    'company_name_ar' => (string) $opt('company-ar', ''),
    'admin_name' => (string) $opt('admin-name', 'Administrator'),
    'admin_email' => strtolower((string) $opt('admin-email', '')),
    'admin_password' => (string) $opt('admin-password', ''),
    'locale' => $opt('locale', 'en') === 'ar' ? 'ar' : 'en',
    'primary' => (string) $opt('primary', '#7c3aed'),
    'currency' => (string) $opt('currency', 'USD'),
    'currency_symbol' => (string) $opt('currency-symbol', '$'),
    'base_domain' => (string) $opt('base-domain', ''),
    'demo' => $opt('demo') === '1',
];

if ($db['user'] === '') {
    $fail('--db-user is required.');
}
if (!preg_match('/^[A-Za-z0-9_-]{1,64}$/', $db['platform_db']) || !preg_match('/^[A-Za-z0-9_-]*$/', $db['tenant_prefix'])) {
    $fail('Database names may only contain letters, numbers, _ and -.');
}
if (!filter_var($data['admin_email'], FILTER_VALIDATE_EMAIL)) {
    $fail('--admin-email must be a valid email.');
}
if (strlen($data['admin_password']) < 8) {
    $fail('--admin-password must be at least 8 characters.');
}
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $data['primary'])) {
    $fail('--primary must be a hex colour like #7c3aed.');
}

try {
    DB::server(null, $db)->value('SELECT 1');
} catch (Throwable $e) {
    $fail('Cannot connect to MySQL: ' . $e->getMessage());
}

try {
    Installer::run($db, $mode, $data);
} catch (Throwable $e) {
    $fail('Installation failed: ' . $e->getMessage());
}

echo "Matjar installed in {$mode} mode.\n";
echo "  master database: {$db['platform_db']}\n";
echo "  shop databases : {$db['tenant_prefix']}<shop-address>  (e.g. {$db['tenant_prefix']}demo)\n";
echo $mode === 'multi'
    ? "  sign in at /platform/login with {$data['admin_email']}\n"
    : "  sign in at /login with {$data['admin_email']}\n";
