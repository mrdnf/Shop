<?php
declare(strict_types=1);

namespace App\Services\Delivery\Couriers;

use App\Core\Http;
use App\Services\Delivery\CourierDriver;

/**
 * Generic courier API connector: create shipments with a JSON template and
 * poll tracking events, all mapped with dot-paths. Couriers that push updates
 * can instead call POST /api/v1/shipments/{tracking}/events.
 */
final class ApiCourier implements CourierDriver
{
    public function __construct(private array $config, private array $courier)
    {
    }

    public static function label(): string
    {
        return 'Courier API (generic)';
    }

    public static function description(): string
    {
        return 'Create shipments and pull tracking from any courier REST API using field mapping.';
    }

    public static function fields(): array
    {
        return [
            ['name' => 'create_url', 'label' => 'Create shipment URL', 'type' => 'text', 'placeholder' => 'https://api.courier.com/v1/shipments'],
            ['name' => 'create_body', 'label' => 'Create body (JSON template)', 'type' => 'textarea',
                'default' => "{\n  \"reference\": \"{{order.number}}\",\n  \"receiver\": {\n    \"name\": \"{{order.customer_name}}\",\n    \"phone\": \"{{order.phone}}\",\n    \"city\": \"{{order.city}}\",\n    \"address\": \"{{order.street}} {{order.building}}\"\n  },\n  \"cod_amount\": {{order.cod_amount}},\n  \"pieces\": {{items.count}}\n}",
                'help' => 'Placeholders: {{order.number}}, {{order.customer_name}}, {{order.phone}}, {{order.city}}, {{order.area}}, {{order.street}}, {{order.building}}, {{order.total}}, {{order.cod_amount}}, {{items.count}}'],
            ['name' => 'tracking_number_path', 'label' => 'Tracking number path', 'type' => 'text', 'default' => 'tracking_number'],
            ['name' => 'external_id_path', 'label' => 'External ID path', 'type' => 'text', 'default' => 'id'],
            ['name' => 'label_url_path', 'label' => 'Label URL path', 'type' => 'text', 'placeholder' => 'label.url'],
            ['name' => 'track_url', 'label' => 'Tracking URL', 'type' => 'text', 'placeholder' => 'https://api.courier.com/v1/track/{tracking}', 'help' => 'Placeholders: {tracking}, {external_id}'],
            ['name' => 'events_path', 'label' => 'Events path', 'type' => 'text', 'default' => 'events'],
            ['name' => 'event_status_field', 'label' => 'Event status field', 'type' => 'text', 'default' => 'status'],
            ['name' => 'event_note_field', 'label' => 'Event description field', 'type' => 'text', 'default' => 'description'],
            ['name' => 'event_location_field', 'label' => 'Event location field', 'type' => 'text', 'default' => 'location'],
            ['name' => 'event_time_field', 'label' => 'Event time field', 'type' => 'text', 'default' => 'time'],
            ['name' => 'status_map', 'label' => 'Status mapping', 'type' => 'textarea',
                'default' => "CREATED=ready\nPICKED_UP=picked_up\nIN_TRANSIT=in_transit\nOUT_FOR_DELIVERY=out_for_delivery\nDELIVERED=delivered\nFAILED=failed\nRETURNED=returned",
                'help' => 'COURIER_STATUS=our_status, one per line.'],
            ['name' => 'auth_type', 'label' => 'Authentication', 'type' => 'select', 'options' => ['none' => 'None', 'bearer' => 'Bearer token', 'basic' => 'Basic (user:password)', 'header' => 'Custom header'], 'default' => 'bearer'],
            ['name' => 'auth_header', 'label' => 'Header name', 'type' => 'text', 'placeholder' => 'X-API-Key'],
            ['name' => 'auth_value', 'label' => 'Token / credentials', 'type' => 'password'],
        ];
    }

    public function create(array $shipment, array $order, array $items): array
    {
        $url = (string) ($this->config['create_url'] ?? '');
        if ($url === '') {
            return [];
        }
        $vars = [];
        foreach ($order as $k => $v) {
            if (is_scalar($v) || $v === null) {
                $vars['order.' . $k] = (string) $v;
            }
        }
        $vars['order.cod_amount'] = $order['payment_method'] === 'cod' && $order['payment_status'] !== 'paid' ? (string) (float) $order['total'] : '0';
        $vars['items.count'] = (string) array_sum(array_column($items, 'qty'));

        $body = preg_replace_callback('/\{\{\s*([\w.]+)\s*\}\}/', static function ($m) use ($vars) {
            // JSON-escape the value without surrounding quotes (template supplies them).
            return substr((string) json_encode($vars[$m[1]] ?? '', JSON_UNESCAPED_UNICODE), 1, -1);
        }, (string) ($this->config['create_body'] ?? '{}'));

        $res = Http::request('POST', $url, Http::authHeaders($this->config) + ['Content-Type' => 'application/json'], (string) $body);
        if ($res['status'] >= 400 || !is_array($res['json'])) {
            throw new \RuntimeException('Courier API error (HTTP ' . $res['status'] . '): ' . mb_substr($res['body'], 0, 300));
        }
        return array_filter([
            'tracking_number' => (string) data_get($res['json'], (string) ($this->config['tracking_number_path'] ?? 'tracking_number'), ''),
            'external_id' => (string) data_get($res['json'], (string) ($this->config['external_id_path'] ?? 'id'), ''),
            'label_url' => (string) data_get($res['json'], (string) ($this->config['label_url_path'] ?? ''), ''),
        ]);
    }

    public function track(array $shipment): array
    {
        $url = (string) ($this->config['track_url'] ?? '');
        if ($url === '') {
            return [];
        }
        $url = strtr($url, ['{tracking}' => rawurlencode((string) $shipment['tracking_number']), '{external_id}' => rawurlencode((string) $shipment['external_id'])]);
        $res = Http::request('GET', $url, Http::authHeaders($this->config));
        if ($res['status'] >= 400 || !is_array($res['json'])) {
            throw new \RuntimeException('Courier tracking error (HTTP ' . $res['status'] . ')');
        }

        $map = [];
        foreach (preg_split('/\r?\n/', (string) ($this->config['status_map'] ?? '')) ?: [] as $line) {
            if (str_contains($line, '=')) {
                [$from, $to] = array_map('trim', explode('=', $line, 2));
                $map[strtoupper($from)] = $to;
            }
        }

        $events = [];
        foreach ((array) data_get($res['json'], (string) ($this->config['events_path'] ?? 'events'), []) as $event) {
            if (!is_array($event)) {
                continue;
            }
            $raw = (string) data_get($event, (string) ($this->config['event_status_field'] ?? 'status'), '');
            $time = (string) data_get($event, (string) ($this->config['event_time_field'] ?? 'time'), '');
            $events[] = [
                'status' => $map[strtoupper($raw)] ?? 'in_transit',
                'note' => (string) data_get($event, (string) ($this->config['event_note_field'] ?? 'description'), $raw),
                'location' => (string) data_get($event, (string) ($this->config['event_location_field'] ?? 'location'), ''),
                'time' => $time,
                'ref' => substr(sha1($raw . '|' . $time), 0, 20),
            ];
        }
        return $events;
    }
}
