<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Settings;
use App\Core\View;
use App\Services\Delivery\DeliveryService;
use App\Services\OrderService;

final class OrderController extends AdminController
{
    public const STATUSES = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];

    public function index(): string
    {
        $where = ['1=1'];
        $params = [];
        if ($q = (string) Request::query('q', '')) {
            $where[] = '(o.number LIKE ? OR o.customer_name LIKE ? OR o.phone LIKE ? OR o.email LIKE ?)';
            array_push($params, "%{$q}%", "%{$q}%", "%{$q}%", "%{$q}%");
        }
        $status = (string) Request::query('status', '');
        if (in_array($status, self::STATUSES, true)) {
            $where[] = 'o.status = ?';
            $params[] = $status;
        }
        $counts = [];
        foreach ($this->db()->fetchAll('SELECT status, COUNT(*) c FROM orders GROUP BY status') as $row) {
            $counts[$row['status']] = (int) $row['c'];
        }
        return $this->view('admin/orders/index', [
            'title' => __('Orders'),
            'page' => $this->db()->paginate(
                'SELECT o.*, (SELECT SUM(qty) FROM order_items WHERE order_id = o.id) AS items_count FROM orders o WHERE '
                . implode(' AND ', $where) . ' ORDER BY o.id DESC',
                $params,
                20
            ),
            'status' => $status,
            'counts' => $counts,
        ]);
    }

    public function show(string $id): string
    {
        $order = $this->find((int) $id);
        $db = $this->db();
        $shipments = $db->fetchAll(
            'SELECT s.*, c.name AS courier_name, u.name AS driver_name FROM shipments s
             LEFT JOIN couriers c ON c.id = s.courier_id LEFT JOIN users u ON u.id = s.driver_id
             WHERE s.order_id = ? ORDER BY s.id DESC',
            [$order['id']]
        );
        return $this->view('admin/orders/show', [
            'title' => __('Order :number', ['number' => $order['number']]),
            'order' => $order,
            'items' => $db->fetchAll('SELECT oi.*, p.image FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?', [$order['id']]),
            'history' => $db->fetchAll('SELECT h.*, u.name AS user_name FROM order_status_history h LEFT JOIN users u ON u.id = h.user_id WHERE h.order_id = ? ORDER BY h.id DESC', [$order['id']]),
            'shipments' => $shipments,
            'couriers' => $db->fetchAll('SELECT * FROM couriers WHERE is_active = 1 ORDER BY name'),
            'drivers' => $db->fetchAll("SELECT id, name FROM users WHERE role = 'driver' AND status = 'active' ORDER BY name"),
            'zone' => $order['zone_id'] ? $db->fetch('SELECT * FROM shipping_zones WHERE id = ?', [$order['zone_id']]) : null,
        ]);
    }

    public function invoice(string $id): string
    {
        $order = $this->find((int) $id);
        return View::render('admin/orders/invoice', [
            'title' => __('Invoice') . ' ' . $order['number'],
            'order' => $order,
            'items' => $this->db()->fetchAll('SELECT * FROM order_items WHERE order_id = ?', [$order['id']]),
            'company' => [
                'email' => Settings::get('company.email'), 'phone' => Settings::get('company.phone'),
                'address' => Settings::get('company.address'), 'tax_number' => Settings::get('company.tax_number'),
            ],
        ], 'layouts/print');
    }

    public function status(string $id): never
    {
        $order = $this->find((int) $id);
        $status = (string) Request::input('status');
        $note = (string) Request::input('note', '');
        if (!in_array($status, self::STATUSES, true)) {
            abort(422);
        }
        if ($status === 'cancelled') {
            OrderService::cancel($order, Auth::id(), $note);
        } elseif ($status !== $order['status']) {
            DeliveryService::setOrderStatus((int) $order['id'], $status, $note ?: null, Auth::id());
        }
        flash('success', __('Order updated.'));
        back();
    }

    public function payment(string $id): never
    {
        $order = $this->find((int) $id);
        $status = (string) Request::input('payment_status');
        if (in_array($status, ['pending', 'paid', 'refunded'], true)) {
            $this->db()->update('orders', ['payment_status' => $status, 'updated_at' => now()], 'id = ?', [$order['id']]);
            $this->db()->insert('order_status_history', ['order_id' => $order['id'], 'status' => $order['status'], 'note' => __('Payment') . ': ' . __('status.' . $status), 'user_id' => Auth::id(), 'created_at' => now()]);
        }
        flash('success', __('Order updated.'));
        back();
    }

    public function ship(string $id): never
    {
        $order = $this->find((int) $id);
        if ($order['status'] === 'cancelled') {
            abort(422, __('Cannot ship a cancelled order.'));
        }
        $tracking = trim((string) Request::input('tracking_number', ''));
        if ($tracking !== '' && $this->db()->value('SELECT 1 FROM shipments WHERE tracking_number = ?', [$tracking])) {
            flash('error', __('This tracking number is already used.'));
            back();
        }
        try {
            $shipmentId = DeliveryService::createShipment(
                $order,
                ((int) Request::input('courier_id')) ?: null,
                ((int) Request::input('driver_id')) ?: null,
                $tracking ?: null,
                (string) Request::input('eta', '') ?: null
            );
        } catch (\Throwable $e) {
            flash('error', __('Could not create shipment: :msg', ['msg' => $e->getMessage()]));
            back();
        }
        flash('success', __('Shipment created.'));
        redirect(url('/admin/shipments/' . $shipmentId));
    }

    private function find(int $id): array
    {
        return $this->db()->fetch('SELECT * FROM orders WHERE id = ?', [$id]) ?? abort(404);
    }
}
