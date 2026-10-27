<?php
declare(strict_types=1);

/**
 * Apply new database migrations to the platform DB and to EVERY shop DB.
 *   php bin/migrate.php
 * Add new schema changes as database/tenant/00X_name.sql (or database/platform/...).
 */

use App\Core\Config;
use App\Core\DB;
use App\Core\Migrator;

require dirname(__DIR__) . '/app/bootstrap.php';

if (!Config::installed()) {
    fwrite(STDERR, "Matjar is not installed yet. Open /install in your browser.\n");
    exit(1);
}

$ran = Migrator::run(DB::platform(), BASE_PATH . '/database/platform');
echo 'platform: ' . ($ran ? implode(', ', $ran) : 'up to date') . "\n";

foreach (DB::platform()->fetchAll('SELECT * FROM tenants ORDER BY id') as $tenant) {
    try {
        $ran = Migrator::run(DB::forTenant($tenant), BASE_PATH . '/database/tenant');
        echo $tenant['slug'] . ': ' . ($ran ? implode(', ', $ran) : 'up to date') . "\n";
    } catch (Throwable $e) {
        fwrite(STDERR, $tenant['slug'] . ' FAILED: ' . $e->getMessage() . "\n");
    }
}
