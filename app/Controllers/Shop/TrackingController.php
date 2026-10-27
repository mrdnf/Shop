<?php
declare(strict_types=1);

namespace App\Controllers\Shop;

use App\Core\Controller;
use App\Core\Request;
use App\Services\Delivery\DeliveryService;

/** Public shipment tracking by tracking number (no personal data exposed). */
final class TrackingController extends Controller
{
    public function form(): string
    {
        $number = trim((string) Request::query('number', ''));
        if ($number !== '') {
            redirect(url('/track/' . rawurlencode($number)));
        }
        return $this->view('shop/track_form', ['title' => __('Track your order')]);
    }

    public function show(string $number): string
    {
        $data = $this->load($number);
        if (!$data) {
            flash('error', __('We could not find a shipment with that tracking number.'));
            redirect(url('/track'));
        }
        return $this->view('shop/track_show', ['title' => __('Tracking :number', ['number' => $number])] + $data);
    }

    public function json(string $number): array
    {
        $data = $this->load($number);
        if (!$data) {
            abort(404, 'Not found');
        }
        $s = $data['shipment'];
        return [
            'tracking_number' => $s['tracking_number'],
            'status' => $s['status'],
            'status_label' => __('status.' . $s['status']),
            'progress' => DeliveryService::progress($s['status']),
            'eta' => $s['eta'],
            'location' => $s['last_lat'] !== null ? ['lat' => (float) $s['last_lat'], 'lng' => (float) $s['last_lng'], 'at' => $s['last_location_at']] : null,
            'events' => array_map(static fn ($e) => [
                'status' => $e['status'], 'label' => __('status.' . $e['status']), 'note' => $e['note'],
                'location' => $e['location'], 'at' => format_date($e['created_at'], true),
            ], $data['events']),
        ];
    }

    private function load(string $number): ?array
    {
        $shipment = $this->db()->fetch(
            'SELECT s.*, c.name AS courier_name, c.driver AS courier_driver, u.name AS driver_name, u.phone AS driver_phone
             FROM shipments s
             LEFT JOIN couriers c ON c.id = s.courier_id
             LEFT JOIN users u ON u.id = s.driver_id
             WHERE s.tracking_number = ?',
            [$number]
        );
        if (!$shipment) {
            return null;
        }
        $order = $this->db()->fetch('SELECT number, city, area, created_at, lat, lng FROM orders WHERE id = ?', [$shipment['order_id']]);
        return [
            'shipment' => $shipment,
            'order' => $order,
            'events' => $this->db()->fetchAll('SELECT * FROM tracking_events WHERE shipment_id = ? ORDER BY created_at DESC, id DESC', [$shipment['id']]),
            'externalUrl' => DeliveryService::externalTrackingUrl($shipment),
        ];
    }
}
