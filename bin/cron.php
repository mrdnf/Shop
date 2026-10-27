<?php
declare(strict_types=1);

/**
 * Scheduler. Run every minute:
 *   * * * * * php /path/to/matjar/bin/cron.php >> /path/to/matjar/storage/logs/cron.log 2>&1
 *
 * For every active shop (each with its own database):
 *   - pulls inventory balances from the connected system when the sync interval is due
 *   - polls courier APIs for new tracking events
 */

use App\Core\Config;
use App\Core\DB;
use App\Core\Tenant;
use App\Services\Delivery\DeliveryService;
use App\Services\Inventory\InventoryService;

require dirname(__DIR__) . '/app/bootstrap.php';

if (!Config::installed()) {
    fwrite(STDERR, "Matjar is not installed yet.\n");
    exit(1);
}

$lock = fopen(BASE_PATH . '/storage/cron.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    echo "Another cron run is in progress.\n";
    exit(0);
}

$only = $argv[1] ?? null; // optional: php bin/cron.php <shop-slug>
$tenants = DB::platform()->fetchAll("SELECT * FROM tenants WHERE status IN ('active','trial') ORDER BY id");

foreach ($tenants as $tenant) {
    if ($only && $tenant['slug'] !== $only) {
        continue;
    }
    $started = microtime(true);
    try {
        Tenant::set($tenant);
        $log = [];
        if (InventoryService::syncDue()) {
            $run = InventoryService::sync('cron');
            $log[] = 'inventory ' . ($run['status'] ?? '?') . ': ' . ($run['message'] ?? '');
        }
        $events = DeliveryService::syncAllActive();
        if ($events) {
            $log[] = "{$events} tracking events";
        }
        if ($log) {
            printf("[%s] %s: %s (%.2fs)\n", date('c'), $tenant['slug'], implode('; ', $log), microtime(true) - $started);
        }
    } catch (Throwable $e) {
        fprintf(STDERR, "[%s] %s failed: %s\n", date('c'), $tenant['slug'], $e->getMessage());
    } finally {
        Tenant::clear();
    }
}
