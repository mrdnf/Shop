<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Crypto;
use App\Core\Request;
use App\Core\Settings;
use App\Services\Inventory\InventoryService;

/** Connect the shop to the system that owns the stock balance. */
final class InventoryController extends AdminController
{
    public function index(): string
    {
        $providers = [];
        foreach (InventoryService::PROVIDERS as $key => $class) {
            $providers[$key] = ['label' => $class::label(), 'description' => $class::description(), 'fields' => $class::fields()];
        }
        return $this->view('admin/inventory/index', [
            'title' => __('Inventory integration'),
            'providers' => $providers,
            'current' => InventoryService::providerKey(),
            'config' => InventoryService::config(),
            'runs' => $this->db()->fetchAll('SELECT * FROM inventory_sync_runs ORDER BY id DESC LIMIT 10'),
            'logs' => $this->db()->fetchAll(
                'SELECT l.*, p.sku, p.name_en, p.name_ar FROM inventory_logs l JOIN products p ON p.id = l.product_id ORDER BY l.id DESC LIMIT 15'
            ),
            'hasKeys' => (bool) $this->db()->value('SELECT COUNT(*) FROM api_keys WHERE revoked_at IS NULL'),
            'stats' => [
                'tracked' => (int) $this->db()->value("SELECT COUNT(*) FROM products WHERE track_stock = 1 AND stock_source = 'auto'"),
                'local' => (int) $this->db()->value("SELECT COUNT(*) FROM products WHERE stock_source = 'local'"),
                'out' => (int) $this->db()->value('SELECT COUNT(*) FROM products WHERE track_stock = 1 AND stock <= 0'),
            ],
        ]);
    }

    public function save(): never
    {
        $key = (string) Request::input('provider', 'local');
        $class = InventoryService::PROVIDERS[$key] ?? null;
        $values = [
            'inventory.provider' => $class ? $key : 'local',
            'inventory.realtime' => Request::input('realtime') === '1' ? '1' : '0',
            'inventory.cache_ttl' => (string) max(0, (int) Request::input('cache_ttl', 300)),
            'inventory.sync_interval' => (string) max(1, (int) Request::input('sync_interval', 15)),
            'inventory.order_webhook_url' => filter_var(Request::input('order_webhook_url'), FILTER_VALIDATE_URL) ?: '',
        ];
        if ($class) {
            $previous = InventoryService::providerKey() === $key ? InventoryService::config() : [];
            $values['inventory.config'] = $this->encryptConfig($this->readConfigFields($class::fields(), $previous));
        }
        if (Request::input('regenerate_secret') === '1') {
            $values['inventory.webhook_secret'] = Crypto::token(24);
        }
        Settings::setMany($values);
        flash('success', __('Inventory settings saved.'));
        redirect(url('/admin/inventory'));
    }

    /** Try the connection without saving and report a preview of the balances. */
    public function test(): array
    {
        $key = (string) Request::input('provider');
        $class = InventoryService::PROVIDERS[$key] ?? null;
        if (!$class) {
            return ['ok' => true, 'message' => __('Local inventory needs no connection.')];
        }
        $previous = InventoryService::providerKey() === $key ? InventoryService::config() : [];
        try {
            $started = microtime(true);
            $balances = InventoryService::make($key, $this->readConfigFields($class::fields(), $previous))->fetchAll();
            $skus = array_column($this->db()->fetchAll('SELECT sku FROM products'), 'sku');
            return [
                'ok' => true,
                'message' => __(':n balances received in :ms ms, :m match your products.', [
                    'n' => count($balances), 'ms' => (int) ((microtime(true) - $started) * 1000),
                    'm' => count(array_intersect(array_keys($balances), $skus)),
                ]),
                'sample' => array_slice($balances, 0, 8, true),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    public function sync(): never
    {
        $run = InventoryService::sync('manual');
        flash(($run['status'] ?? '') === 'success' ? 'success' : 'error', (string) ($run['message'] ?? __('Sync finished.')));
        redirect(url('/admin/inventory'));
    }
}
