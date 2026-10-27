<?php
declare(strict_types=1);

namespace App\Controllers\Driver;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Services\Delivery\DeliveryService;

/** Mobile-first app for the shop's own delivery drivers. */
final class DriverController extends Controller
{
    protected string $layout = 'layouts/driver';

    private function scope(): array
    {
        // Drivers see their own shipments; admins/staff can open any.
        return Auth::is('driver') ? ['s.driver_id = ?', [Auth::id()]] : ['1=1', []];
    }

    public function index(): string
    {
        [$where, $params] = $this->scope();
        $rows = $this->db()->fetchAll(
            "SELECT s.*, o.number, o.customer_name, o.phone, o.city, o.area, o.street, o.building, o.total, o.payment_method, o.payment_status
             FROM shipments s JOIN orders o ON o.id = s.order_id
             WHERE {$where} AND s.status NOT IN ('delivered','cancelled','returned') ORDER BY FIELD(s.status,'out_for_delivery','in_transit','picked_up','ready','pending','failed'), s.id",
            $params
        );
        $done = (int) $this->db()->value("SELECT COUNT(*) FROM shipments s WHERE {$where} AND s.status = 'delivered' AND s.delivered_at >= ?", array_merge($params, [date('Y-m-d')]));
        return $this->view('driver/index', ['title' => __('My deliveries'), 'shipments' => $rows, 'doneToday' => $done]);
    }

    public function show(string $id): string
    {
        $shipment = $this->find((int) $id);
        return $this->view('driver/show', [
            'title' => $shipment['tracking_number'],
            'shipment' => $shipment,
            'items' => $this->db()->fetchAll('SELECT * FROM order_items WHERE order_id = ?', [$shipment['order_id']]),
            'events' => $this->db()->fetchAll('SELECT * FROM tracking_events WHERE shipment_id = ? ORDER BY created_at DESC, id DESC', [$shipment['id']]),
        ]);
    }

    public function event(string $id): never
    {
        $shipment = $this->find((int) $id);
        $status = (string) Request::input('status');
        $allowed = ['picked_up', 'in_transit', 'out_for_delivery', 'delivered', 'failed', 'returned'];
        if (!in_array($status, $allowed, true)) {
            abort(422);
        }
        $lat = is_numeric(Request::input('lat')) ? (float) Request::input('lat') : null;
        $lng = is_numeric(Request::input('lng')) ? (float) Request::input('lng') : null;
        DeliveryService::addEvent((int) $shipment['id'], $status, (string) Request::input('note', '') ?: null, 'driver', $lat, $lng);
        flash('success', __('Status updated: :status', ['status' => __('status.' . $status)]));
        redirect($status === 'delivered' ? url('/driver') : url('/driver/shipments/' . $shipment['id']));
    }

    /** Live GPS ping from the driver's phone (JSON). */
    public function location(string $id): array
    {
        $shipment = $this->find((int) $id);
        $lat = Request::input('lat');
        $lng = Request::input('lng');
        if (!is_numeric($lat) || !is_numeric($lng) || abs((float) $lat) > 90 || abs((float) $lng) > 180) {
            abort(422, 'Invalid coordinates');
        }
        DeliveryService::updateLocation((int) $shipment['id'], (float) $lat, (float) $lng);
        return ['ok' => true, 'at' => now()];
    }

    private function find(int $id): array
    {
        [$where, $params] = $this->scope();
        return $this->db()->fetch(
            "SELECT s.*, o.number, o.customer_name, o.phone, o.city, o.area, o.street, o.building, o.address_notes,
                    o.lat AS dest_lat, o.lng AS dest_lng, o.total, o.payment_method, o.payment_status
             FROM shipments s JOIN orders o ON o.id = s.order_id WHERE s.id = ? AND {$where}",
            array_merge([$id], $params)
        ) ?? abort(404);
    }
}
