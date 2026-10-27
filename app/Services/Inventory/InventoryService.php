<?php
declare(strict_types=1);

namespace App\Services\Inventory;

use App\Core\Crypto;
use App\Core\DB;
use App\Core\Http;
use App\Core\Settings;
use App\Services\Inventory\Providers\CsvFeedProvider;
use App\Services\Inventory\Providers\DatabaseProvider;
use App\Services\Inventory\Providers\RestApiProvider;

/**
 * Stock balance orchestration for one shop.
 *
 *  - Pull:  a provider (REST / CSV / DB) is synced on a schedule (bin/cron.php)
 *           or on demand, and optionally checked in real time at checkout.
 *  - Push:  any external system can POST balances to /api/v1/inventory.
 *  - Out:   every order can be pushed to a webhook so the ERP deducts stock.
 */
final class InventoryService
{
    /** Register new connectors here. */
    public const PROVIDERS = [
        'rest' => RestApiProvider::class,
        'csv' => CsvFeedProvider::class,
        'database' => DatabaseProvider::class,
    ];

    public static function providerKey(): string
    {
        $key = (string) Settings::get('inventory.provider', 'local');
        return isset(self::PROVIDERS[$key]) ? $key : 'local';
    }

    public static function config(): array
    {
        return Crypto::decryptArray((string) Settings::get('inventory.config', ''));
    }

    public static function make(?string $key = null, ?array $config = null): ?InventoryProvider
    {
        $key ??= self::providerKey();
        $class = self::PROVIDERS[$key] ?? null;
        return $class ? new $class($config ?? self::config()) : null;
    }

    /** Products that follow the external balance (stock_source = auto). */
    private static function followsProvider(array $product): bool
    {
        return self::providerKey() !== 'local' && $product['stock_source'] === 'auto' && (int) $product['track_stock'] === 1;
    }

    /**
     * Pull every balance from the provider and update matching SKUs.
     */
    public static function sync(string $trigger = 'manual'): array
    {
        $db = DB::tenant();
        $key = self::providerKey();
        $runId = $db->insert('inventory_sync_runs', ['provider' => $key, 'trigger_type' => $trigger, 'status' => 'running', 'started_at' => now()]);

        try {
            $provider = self::make();
            if (!$provider) {
                throw new \RuntimeException(__('Inventory is managed locally, no external system configured.'));
            }
            $balances = $provider->fetchAll();
            $updated = 0;
            $products = $db->fetchAll("SELECT id, sku, stock FROM products WHERE track_stock = 1 AND stock_source = 'auto'");
            foreach ($products as $product) {
                if (!array_key_exists($product['sku'], $balances)) {
                    continue;
                }
                if (self::setBalance((int) $product['id'], (int) $balances[$product['sku']], 'sync', 'Sync from ' . $key, (int) $product['stock'])) {
                    $updated++;
                }
            }
            $db->query("UPDATE products SET stock_synced_at = ? WHERE track_stock = 1 AND stock_source = 'auto'", [now()]);
            $db->update('inventory_sync_runs', [
                'status' => 'success', 'items_received' => count($balances), 'items_updated' => $updated, 'finished_at' => now(),
                'message' => __(':received balances received, :updated products changed.', ['received' => count($balances), 'updated' => $updated]),
            ], 'id = ?', [$runId]);
            Settings::set('inventory.last_sync_at', now());
        } catch (\Throwable $e) {
            $db->update('inventory_sync_runs', ['status' => 'error', 'message' => mb_substr($e->getMessage(), 0, 1000), 'finished_at' => now()], 'id = ?', [$runId]);
        }
        return $db->fetch('SELECT * FROM inventory_sync_runs WHERE id = ?', [$runId]) ?? [];
    }

    /** Is a scheduled sync due? */
    public static function syncDue(): bool
    {
        if (self::providerKey() === 'local') {
            return false;
        }
        $last = Settings::get('inventory.last_sync_at');
        $interval = max(1, (int) Settings::get('inventory.sync_interval', 15));
        return !$last || strtotime((string) $last) <= time() - $interval * 60;
    }

    /**
     * Quantity available to sell. With real-time mode on, a stale balance is
     * refreshed from the external system first (cached for cache_ttl seconds).
     */
    public static function available(array $product): int
    {
        if ((int) $product['track_stock'] !== 1) {
            return PHP_INT_MAX;
        }
        if (self::followsProvider($product) && Settings::get('inventory.realtime', '0') === '1') {
            $ttl = (int) Settings::get('inventory.cache_ttl', 300);
            $fresh = $product['stock_synced_at'] && strtotime((string) $product['stock_synced_at']) > time() - $ttl;
            if (!$fresh) {
                try {
                    $qty = self::make()?->fetchOne((string) $product['sku']);
                    if ($qty !== null) {
                        self::setBalance((int) $product['id'], $qty, 'realtime', 'Real-time check', (int) $product['stock']);
                        DB::tenant()->update('products', ['stock_synced_at' => now()], 'id = ?', [$product['id']]);
                        return max(0, $qty);
                    }
                } catch (\Throwable $e) {
                    error_log('Inventory real-time check failed: ' . $e->getMessage());
                }
            }
        }
        return max(0, (int) $product['stock']);
    }

    /** Set an absolute balance. Returns true when it changed. */
    public static function setBalance(int $productId, int $qty, string $source, string $reason, ?int $current = null): bool
    {
        $db = DB::tenant();
        $current ??= (int) $db->value('SELECT stock FROM products WHERE id = ?', [$productId]);
        if ($current === $qty) {
            return false;
        }
        $db->update('products', ['stock' => $qty, 'updated_at' => now()], 'id = ?', [$productId]);
        $db->insert('inventory_logs', ['product_id' => $productId, 'change_qty' => $qty - $current, 'balance' => $qty, 'reason' => $reason, 'source' => $source, 'created_at' => now()]);
        return true;
    }

    /** Relative change (orders, cancellations, manual adjustments). */
    public static function adjust(int $productId, int $change, string $source, string $reason): void
    {
        $db = DB::tenant();
        $db->query('UPDATE products SET stock = stock + ?, updated_at = ? WHERE id = ?', [$change, now(), $productId]);
        $balance = (int) $db->value('SELECT stock FROM products WHERE id = ?', [$productId]);
        $db->insert('inventory_logs', ['product_id' => $productId, 'change_qty' => $change, 'balance' => $balance, 'reason' => $reason, 'source' => $source, 'created_at' => now()]);
    }

    /**
     * Balances pushed by an external system: [['sku' => 'A1', 'quantity' => 4], ...].
     * @return array{updated: int, unknown: string[]}
     */
    public static function applyPush(array $items, string $source = 'api'): array
    {
        $db = DB::tenant();
        $updated = 0;
        $unknown = [];
        foreach ($items as $item) {
            $sku = (string) ($item['sku'] ?? '');
            $qty = $item['quantity'] ?? $item['qty'] ?? null;
            if ($sku === '' || !is_numeric($qty)) {
                continue;
            }
            $product = $db->fetch('SELECT id, stock FROM products WHERE sku = ?', [$sku]);
            if (!$product) {
                $unknown[] = $sku;
                continue;
            }
            if (self::setBalance((int) $product['id'], (int) $qty, $source, 'Pushed by external system', (int) $product['stock'])) {
                $updated++;
            }
            $db->update('products', ['stock_synced_at' => now()], 'id = ?', [$product['id']]);
        }
        return ['updated' => $updated, 'unknown' => $unknown];
    }

    /**
     * Tell the external system about an order so it can reserve / deduct stock.
     * Signed with HMAC-SHA256 in the X-Matjar-Signature header.
     */
    public static function notifyOrder(array $order, array $items, string $event = 'order.created'): void
    {
        $url = (string) Settings::get('inventory.order_webhook_url', '');
        if ($url === '') {
            return;
        }
        $payload = json_encode([
            'event' => $event,
            'order' => [
                'number' => $order['number'], 'status' => $order['status'], 'total' => (float) $order['total'],
                'currency' => $order['currency'], 'created_at' => $order['created_at'] ?? now(),
            ],
            'items' => array_map(static fn ($i) => ['sku' => $i['sku'], 'quantity' => (int) $i['qty']], $items),
        ], JSON_UNESCAPED_UNICODE);
        $secret = (string) Settings::get('inventory.webhook_secret', '');
        try {
            Http::request('POST', $url, [
                'Content-Type' => 'application/json',
                'X-Matjar-Event' => $event,
                'X-Matjar-Signature' => hash_hmac('sha256', (string) $payload, $secret),
            ], (string) $payload, 8);
        } catch (\Throwable $e) {
            error_log('Order webhook failed: ' . $e->getMessage());
        }
    }
}
