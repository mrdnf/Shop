<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Services\Delivery\DeliveryService;

final class ShipmentController extends AdminController
{
    public function index(): string
    {
        $where = ['1=1'];
        $params = [];
        $status = (string) Request::query('status', '');
        if ($status === 'active') {
            $where[] = "s.status NOT IN ('delivered','cancelled','returned')";
        } elseif (in_array($status, DeliveryService::statuses(), true)) {
            $where[] = 's.status = ?';
            $params[] = $status;
        }
        if ($q = (string) Request::query('q', '')) {
            $where[] = '(s.tracking_number LIKE ? OR o.number LIKE ? OR o.customer_name LIKE ?)';
            array_push($params, "%{$q}%", "%{$q}%", "%{$q}%");
        }
        if ($driver = (int) Request::query('driver', 0)) {
            $where[] = 's.driver_id = ?';
            $params[] = $driver;
        }
        return $this->view('admin/shipments/index', [
            'title' => __('Shipments'),
            'page' => $this->db()->paginate(
                'SELECT s.*, o.number, o.customer_name, o.city, c.name AS courier_name, u.name AS driver_name
                 FROM shipments s JOIN orders o ON o.id = s.order_id
                 LEFT JOIN couriers c ON c.id = s.courier_id LEFT JOIN users u ON u.id = s.driver_id
                 WHERE ' . implode(' AND ', $where) . ' ORDER BY s.id DESC',
                $params,
                20
            ),
            'status' => $status,
            'drivers' => $this->db()->fetchAll("SELECT id, name FROM users WHERE role = 'driver' ORDER BY name"),
            'live' => $this->db()->fetchAll(
                "SELECT s.id, s.tracking_number, s.status, s.last_lat, s.last_lng, s.last_location_at, u.name AS driver_name
                 FROM shipments s LEFT JOIN users u ON u.id = s.driver_id
                 WHERE s.last_lat IS NOT NULL AND s.status IN ('picked_up','in_transit','out_for_delivery')"
            ),
        ]);
    }

    public function show(string $id): string
    {
        $shipment = $this->find((int) $id);
        $db = $this->db();
        return $this->view('admin/shipments/show', [
            'title' => $shipment['tracking_number'],
            'shipment' => $shipment,
            'order' => $db->fetch('SELECT * FROM orders WHERE id = ?', [$shipment['order_id']]),
            'events' => $db->fetchAll('SELECT * FROM tracking_events WHERE shipment_id = ? ORDER BY created_at DESC, id DESC', [$shipment['id']]),
            'drivers' => $db->fetchAll("SELECT id, name FROM users WHERE role = 'driver' AND status = 'active' ORDER BY name"),
            'externalUrl' => DeliveryService::externalTrackingUrl($shipment),
        ]);
    }

    public function event(string $id): never
    {
        $shipment = $this->find((int) $id);
        $status = (string) Request::input('status');
        if (!in_array($status, DeliveryService::statuses(), true)) {
            abort(422);
        }
        DeliveryService::addEvent(
            (int) $shipment['id'],
            $status,
            (string) Request::input('note', '') ?: null,
            'admin',
            null,
            null,
            (string) Request::input('location', '') ?: null
        );
        flash('success', __('Tracking updated.'));
        back();
    }

    public function driver(string $id): never
    {
        $shipment = $this->find((int) $id);
        $driverId = (int) Request::input('driver_id', 0) ?: null;
        $this->db()->update('shipments', ['driver_id' => $driverId, 'updated_at' => now()], 'id = ?', [$shipment['id']]);
        if ($driverId && $shipment['status'] === 'pending') {
            DeliveryService::addEvent((int) $shipment['id'], 'ready', __('Assigned to driver'), 'admin');
        }
        flash('success', __('Driver assigned.'));
        back();
    }

    public function sync(string $id): never
    {
        $shipment = $this->find((int) $id);
        try {
            $n = DeliveryService::syncTracking($shipment);
            flash('success', __(':n new tracking events.', ['n' => $n]));
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        back();
    }

    private function find(int $id): array
    {
        return $this->db()->fetch(
            'SELECT s.*, c.name AS courier_name, c.driver AS courier_driver, u.name AS driver_name, u.phone AS driver_phone
             FROM shipments s LEFT JOIN couriers c ON c.id = s.courier_id LEFT JOIN users u ON u.id = s.driver_id WHERE s.id = ?',
            [$id]
        ) ?? abort(404);
    }
}
