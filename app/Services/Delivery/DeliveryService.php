<?php
declare(strict_types=1);

namespace App\Services\Delivery;

use App\Core\Crypto;
use App\Core\DB;
use App\Core\Settings;
use App\Services\Delivery\Couriers\ApiCourier;
use App\Services\Delivery\Couriers\InHouseCourier;
use App\Services\Delivery\Couriers\ManualCourier;

final class DeliveryService
{
    public const DRIVERS = [
        'inhouse' => InHouseCourier::class,
        'manual' => ManualCourier::class,
        'api' => ApiCourier::class,
    ];

    /** Happy-path progression shown as a stepper. */
    public const FLOW = ['pending', 'ready', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered'];
    public const PROBLEMS = ['failed', 'returned', 'cancelled'];

    public const ICONS = [
        'pending' => 'clipboard-list',
        'ready' => 'package-check',
        'picked_up' => 'package-open',
        'in_transit' => 'truck',
        'out_for_delivery' => 'map-pin',
        'delivered' => 'circle-check-big',
        'failed' => 'circle-alert',
        'returned' => 'undo-2',
        'cancelled' => 'circle-x',
    ];

    public static function statuses(): array
    {
        return array_merge(self::FLOW, self::PROBLEMS);
    }

    public static function driverFor(array $courier): CourierDriver
    {
        $class = self::DRIVERS[$courier['driver']] ?? InHouseCourier::class;
        return new $class(Crypto::decryptArray($courier['config'] ?? ''), $courier);
    }

    public static function generateTrackingNumber(): string
    {
        $prefix = strtoupper(preg_replace('/[^A-Za-z]/', '', (string) Settings::get('delivery.tracking_prefix', 'MJ')) ?: 'MJ');
        do {
            $number = $prefix . date('ymd') . random_int(100000, 999999);
        } while (DB::tenant()->value('SELECT 1 FROM shipments WHERE tracking_number = ?', [$number]));
        return $number;
    }

    /** Create a shipment for an order (optionally registering it with a courier API). */
    public static function createShipment(array $order, ?int $courierId, ?int $driverId, ?string $trackingNumber = null, ?string $eta = null): int
    {
        $db = DB::tenant();
        $courier = $courierId ? $db->fetch('SELECT * FROM couriers WHERE id = ?', [$courierId]) : null;
        $items = $db->fetchAll('SELECT * FROM order_items WHERE order_id = ?', [$order['id']]);

        $shipment = [
            'order_id' => $order['id'],
            'courier_id' => $courier['id'] ?? null,
            'driver_id' => $driverId ?: null,
            'tracking_number' => $trackingNumber ?: self::generateTrackingNumber(),
            'status' => 'pending',
            'eta' => $eta ?: null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if ($courier) {
            $result = self::driverFor($courier)->create($shipment, $order, $items);
            if (!empty($result['tracking_number'])) {
                $shipment['tracking_number'] = $result['tracking_number'];
            }
            $shipment['external_id'] = $result['external_id'] ?? null;
            $shipment['label_url'] = $result['label_url'] ?? null;
        }

        $id = $db->insert('shipments', $shipment);
        self::addEvent($id, 'pending', __('Shipment created'), 'system');
        if (in_array($order['status'], ['pending', 'confirmed'], true)) {
            self::setOrderStatus((int) $order['id'], 'processing', __('Shipment created'));
        }
        return $id;
    }

    /**
     * Record a tracking event and move the shipment (and its order) forward.
     */
    public static function addEvent(
        int $shipmentId,
        string $status,
        ?string $note = null,
        string $source = 'admin',
        ?float $lat = null,
        ?float $lng = null,
        ?string $location = null,
        ?string $time = null,
        ?string $ref = null
    ): void {
        if (!in_array($status, self::statuses(), true)) {
            throw new \InvalidArgumentException('Unknown shipment status: ' . $status);
        }
        $db = DB::tenant();
        $shipment = $db->fetch('SELECT * FROM shipments WHERE id = ?', [$shipmentId]);
        if (!$shipment) {
            return;
        }
        $at = $time && strtotime($time) ? date('Y-m-d H:i:s', strtotime($time)) : now();
        $db->insert('tracking_events', [
            'shipment_id' => $shipmentId, 'status' => $status, 'note' => $note ? mb_substr($note, 0, 255) : null,
            'location' => $location ? mb_substr($location, 0, 190) : null, 'lat' => $lat, 'lng' => $lng,
            'source' => $source, 'external_ref' => $ref, 'created_at' => $at,
        ]);

        $update = ['status' => $status, 'updated_at' => now()];
        if ($lat !== null && $lng !== null) {
            $update += ['last_lat' => $lat, 'last_lng' => $lng, 'last_location_at' => now()];
        }
        if ($status === 'delivered') {
            $update['delivered_at'] = $at;
        }
        $db->update('shipments', $update, 'id = ?', [$shipmentId]);

        $order = $db->fetch('SELECT * FROM orders WHERE id = ?', [$shipment['order_id']]);
        if (!$order || $order['status'] === 'cancelled') {
            return;
        }
        if (in_array($status, ['picked_up', 'in_transit', 'out_for_delivery'], true) && $order['status'] !== 'shipped' && $order['status'] !== 'delivered') {
            self::setOrderStatus((int) $order['id'], 'shipped', __('status.' . $status));
        } elseif ($status === 'delivered' && $order['status'] !== 'delivered') {
            self::setOrderStatus((int) $order['id'], 'delivered', __('Delivered to customer'));
            if ($order['payment_method'] === 'cod') {
                $db->update('orders', ['payment_status' => 'paid'], 'id = ?', [$order['id']]);
            }
        }
    }

    public static function updateLocation(int $shipmentId, float $lat, float $lng): void
    {
        DB::tenant()->update('shipments', ['last_lat' => $lat, 'last_lng' => $lng, 'last_location_at' => now()], 'id = ?', [$shipmentId]);
    }

    public static function setOrderStatus(int $orderId, string $status, ?string $note = null, ?int $userId = null): void
    {
        $db = DB::tenant();
        $db->update('orders', ['status' => $status, 'updated_at' => now()], 'id = ?', [$orderId]);
        $db->insert('order_status_history', ['order_id' => $orderId, 'status' => $status, 'note' => $note, 'user_id' => $userId, 'created_at' => now()]);
    }

    /** Pull new events from courier APIs. Returns number of new events. */
    public static function syncTracking(array $shipment): int
    {
        $db = DB::tenant();
        $courier = $shipment['courier_id'] ? $db->fetch('SELECT * FROM couriers WHERE id = ?', [$shipment['courier_id']]) : null;
        if (!$courier || $courier['driver'] !== 'api') {
            return 0;
        }
        $new = 0;
        foreach (self::driverFor($courier)->track($shipment) as $event) {
            $ref = $event['ref'] ?? null;
            if ($ref && $db->value('SELECT 1 FROM tracking_events WHERE shipment_id = ? AND external_ref = ?', [$shipment['id'], $ref])) {
                continue;
            }
            self::addEvent((int) $shipment['id'], $event['status'], $event['note'] ?? null, 'courier', null, null, $event['location'] ?? null, $event['time'] ?? null, $ref);
            $new++;
        }
        return $new;
    }

    public static function syncAllActive(): int
    {
        $total = 0;
        $rows = DB::tenant()->fetchAll(
            "SELECT s.* FROM shipments s JOIN couriers c ON c.id = s.courier_id
             WHERE c.driver = 'api' AND s.status NOT IN ('delivered','returned','cancelled')"
        );
        foreach ($rows as $shipment) {
            try {
                $total += self::syncTracking($shipment);
            } catch (\Throwable $e) {
                error_log('Tracking sync failed for ' . $shipment['tracking_number'] . ': ' . $e->getMessage());
            }
        }
        return $total;
    }

    /** Find the shipping zone for a city; zones without cities act as "everywhere else". */
    public static function zoneForCity(string $city): ?array
    {
        $zones = DB::tenant()->fetchAll('SELECT * FROM shipping_zones WHERE is_active = 1 ORDER BY sort, id');
        $needle = mb_strtolower(trim($city));
        $fallback = null;
        foreach ($zones as $zone) {
            $cities = array_filter(array_map(static fn ($c) => mb_strtolower(trim($c)), preg_split('/[,\n،]+/u', (string) $zone['cities']) ?: []));
            if (!$cities) {
                $fallback ??= $zone;
                continue;
            }
            if (in_array($needle, $cities, true)) {
                return $zone;
            }
        }
        return $fallback;
    }

    public static function shippingCost(?array $zone, float $subtotal): float
    {
        if (!$zone) {
            return 0.0;
        }
        if ($zone['free_over'] !== null && $subtotal >= (float) $zone['free_over']) {
            return 0.0;
        }
        return (float) $zone['rate'];
    }

    public static function externalTrackingUrl(array $shipment): ?string
    {
        if (empty($shipment['courier_id'])) {
            return null;
        }
        $template = (string) DB::tenant()->value('SELECT tracking_url_template FROM couriers WHERE id = ?', [$shipment['courier_id']]);
        return $template !== '' ? str_replace('{tracking}', rawurlencode((string) $shipment['tracking_number']), $template) : null;
    }

    /** Index of a status in the happy-path flow (for progress bars). */
    public static function progress(string $status): int
    {
        $i = array_search($status, self::FLOW, true);
        return $i === false ? 0 : (int) round($i / (count(self::FLOW) - 1) * 100);
    }
}
