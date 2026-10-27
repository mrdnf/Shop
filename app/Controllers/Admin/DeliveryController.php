<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Crypto;
use App\Core\Request;
use App\Services\Delivery\DeliveryService;

/** Shipping zones & rates, couriers (in-house fleet, 3rd-party, courier APIs). */
final class DeliveryController extends AdminController
{
    public function index(): string
    {
        $drivers = [];
        foreach (DeliveryService::DRIVERS as $key => $class) {
            $drivers[$key] = ['label' => $class::label(), 'description' => $class::description(), 'fields' => $class::fields()];
        }
        $couriers = $this->db()->fetchAll(
            "SELECT c.*, (SELECT COUNT(*) FROM shipments s WHERE s.courier_id = c.id AND s.status NOT IN ('delivered','cancelled','returned')) AS active_count
             FROM couriers c ORDER BY c.id"
        );
        foreach ($couriers as &$c) {
            $c['config_values'] = Crypto::decryptArray($c['config']);
            // Never send secrets back to the browser.
            foreach ($drivers[$c['driver']]['fields'] ?? [] as $f) {
                if ($f['type'] === 'password') {
                    $c['config_values'][$f['name']] = '';
                }
            }
        }
        unset($c);
        return $this->view('admin/delivery/index', [
            'title' => __('Delivery'),
            'zones' => $this->db()->fetchAll('SELECT * FROM shipping_zones ORDER BY sort, id'),
            'couriers' => $couriers,
            'drivers' => $drivers,
            'driverUsers' => $this->db()->fetchAll("SELECT u.*, (SELECT COUNT(*) FROM shipments s WHERE s.driver_id = u.id AND s.status NOT IN ('delivered','cancelled','returned')) AS active_count FROM users u WHERE role = 'driver' ORDER BY name"),
        ]);
    }

    public function saveZone(): never
    {
        $data = $this->validate([
            'name_en' => 'required|max:120',
            'name_ar' => 'nullable|max:120',
            'cities' => 'nullable|max:5000',
            'rate' => 'required|numeric|min:0',
            'free_over' => 'nullable|numeric|min:0',
            'eta_min_days' => 'required|integer|min:0',
            'eta_max_days' => 'required|integer|min:0',
            'sort' => 'nullable|integer',
        ]);
        $data['free_over'] = $data['free_over'] === '' || $data['free_over'] === null ? null : (float) $data['free_over'];
        $data['name_ar'] = $data['name_ar'] ?: null;
        $data['sort'] = (int) ($data['sort'] ?? 0);
        $data['is_active'] = Request::input('is_active') === '1' ? 1 : 0;
        $id = (int) Request::input('id', 0);
        $id ? $this->db()->update('shipping_zones', $data, 'id = ?', [$id]) : $this->db()->insert('shipping_zones', $data);
        flash('success', __('Shipping zone saved.'));
        redirect(url('/admin/delivery'));
    }

    public function deleteZone(string $id): never
    {
        $this->db()->delete('shipping_zones', 'id = ?', [(int) $id]);
        flash('success', __('Shipping zone deleted.'));
        redirect(url('/admin/delivery'));
    }

    public function saveCourier(): never
    {
        $data = $this->validate([
            'name' => 'required|max:120',
            'driver' => 'required|in:' . implode(',', array_keys(DeliveryService::DRIVERS)),
            'tracking_url_template' => 'nullable|max:255',
        ]);
        $id = (int) Request::input('id', 0);
        $previous = $id ? Crypto::decryptArray((string) $this->db()->value('SELECT config FROM couriers WHERE id = ?', [$id])) : [];
        $class = DeliveryService::DRIVERS[$data['driver']];
        $row = [
            'name' => $data['name'],
            'driver' => $data['driver'],
            'tracking_url_template' => $data['tracking_url_template'] ?: null,
            'config' => $this->encryptConfig($this->readConfigFields($class::fields(), $previous)),
            'is_active' => Request::input('is_active') === '1' ? 1 : 0,
        ];
        $id ? $this->db()->update('couriers', $row, 'id = ?', [$id]) : $this->db()->insert('couriers', $row + ['created_at' => now()]);
        flash('success', __('Courier saved.'));
        redirect(url('/admin/delivery'));
    }

    public function deleteCourier(string $id): never
    {
        $this->db()->delete('couriers', 'id = ?', [(int) $id]);
        flash('success', __('Courier deleted.'));
        redirect(url('/admin/delivery'));
    }
}
