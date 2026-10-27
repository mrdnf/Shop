<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Request;
use App\Services\Delivery\DeliveryService;
use App\Services\Inventory\InventoryService;

/**
 * REST API for integrations (ERP, POS, WMS, courier webhooks).
 * Authenticate with `Authorization: Bearer mk_...` (keys are created in Admin → API & webhooks).
 */
final class ApiController extends Controller
{
    /** GET /api/v1/products?updated_since=2025-01-01 */
    public function products(): array
    {
        $params = [];
        $where = '1=1';
        if ($since = Request::query('updated_since')) {
            $where = 'COALESCE(updated_at, created_at) >= ?';
            $params[] = date('Y-m-d H:i:s', strtotime((string) $since) ?: 0);
        }
        $rows = $this->db()->fetchAll(
            "SELECT id, sku, name_en, name_ar, price, compare_price, stock, track_stock, stock_source, is_active, stock_synced_at, updated_at
             FROM products WHERE {$where} ORDER BY id LIMIT 5000",
            $params
        );
        return ['data' => $rows, 'count' => count($rows)];
    }

    /**
     * POST /api/v1/inventory
     * {"items": [{"sku": "EL-1001", "quantity": 12}, ...]}  or  {"sku": "EL-1001", "quantity": 12}
     */
    public function inventory(): array
    {
        $body = Request::json() ?: $_POST;
        $items = isset($body['items']) && is_array($body['items']) ? $body['items'] : [$body];
        if (count($items) > 10000) {
            abort(422, 'Too many items in one request (max 10000).');
        }
        $result = InventoryService::applyPush($items, 'api');
        return ['ok' => true, 'updated' => $result['updated'], 'unknown_skus' => $result['unknown']];
    }

    /** GET /api/v1/orders?status=pending&since=2025-01-01 */
    public function orders(): array
    {
        $where = ['1=1'];
        $params = [];
        if ($status = Request::query('status')) {
            $where[] = 'status = ?';
            $params[] = $status;
        }
        if ($since = Request::query('since')) {
            $where[] = 'created_at >= ?';
            $params[] = date('Y-m-d H:i:s', strtotime((string) $since) ?: 0);
        }
        $orders = $this->db()->fetchAll('SELECT * FROM orders WHERE ' . implode(' AND ', $where) . ' ORDER BY id DESC LIMIT 500', $params);
        foreach ($orders as &$order) {
            $order['items'] = $this->db()->fetchAll('SELECT sku, name, price, qty, total FROM order_items WHERE order_id = ?', [$order['id']]);
        }
        return ['data' => $orders, 'count' => count($orders)];
    }

    public function order(string $number): array
    {
        $order = $this->db()->fetch('SELECT * FROM orders WHERE number = ?', [$number]) ?? abort(404, 'Order not found');
        $order['items'] = $this->db()->fetchAll('SELECT sku, name, price, qty, total FROM order_items WHERE order_id = ?', [$order['id']]);
        $order['shipments'] = $this->db()->fetchAll('SELECT tracking_number, status, eta, delivered_at FROM shipments WHERE order_id = ?', [$order['id']]);
        return ['data' => $order];
    }

    /**
     * POST /api/v1/shipments/{tracking}/events  — courier push updates
     * {"status": "in_transit", "note": "Arrived at hub", "location": "Riyadh", "lat": 24.7, "lng": 46.6}
     */
    public function shipmentEvent(string $tracking): array
    {
        $shipment = $this->db()->fetch('SELECT * FROM shipments WHERE tracking_number = ?', [$tracking]) ?? abort(404, 'Shipment not found');
        $status = (string) Request::input('status', '');
        if (!in_array($status, DeliveryService::statuses(), true)) {
            abort(422, 'status must be one of: ' . implode(', ', DeliveryService::statuses()));
        }
        $lat = Request::input('lat');
        $lng = Request::input('lng');
        DeliveryService::addEvent(
            (int) $shipment['id'],
            $status,
            (string) Request::input('note', '') ?: null,
            'courier',
            is_numeric($lat) ? (float) $lat : null,
            is_numeric($lng) ? (float) $lng : null,
            (string) Request::input('location', '') ?: null,
            (string) Request::input('time', '') ?: null
        );
        return ['ok' => true];
    }
}
