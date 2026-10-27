<?php
declare(strict_types=1);

namespace App\Controllers\Platform;

use App\Core\Request;

final class PlanController extends PlatformController
{
    public function index(): string
    {
        return $this->view('platform/plans/index', [
            'title' => __('Plans'),
            'plans' => $this->db()->fetchAll('SELECT p.*, (SELECT COUNT(*) FROM tenants t WHERE t.plan_id = p.id) AS shops FROM plans p ORDER BY sort, price'),
        ]);
    }

    public function save(): never
    {
        $data = $this->validate([
            'name' => 'required|max:100',
            'name_ar' => 'nullable|max:100',
            'price' => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:monthly,yearly',
            'max_products' => 'nullable|integer|min:0',
            'max_staff' => 'nullable|integer|min:0',
            'features' => 'nullable|max:2000',
            'sort' => 'nullable|integer',
        ]);
        $row = [
            'name' => $data['name'],
            'name_ar' => $data['name_ar'] ?: null,
            'price' => (float) $data['price'],
            'billing_cycle' => $data['billing_cycle'],
            'max_products' => $data['max_products'] === null || $data['max_products'] === '' ? null : (int) $data['max_products'],
            'max_staff' => $data['max_staff'] === null || $data['max_staff'] === '' ? null : (int) $data['max_staff'],
            'features' => $data['features'] ?: null,
            'sort' => (int) ($data['sort'] ?? 0),
            'is_active' => Request::input('is_active') === '1' ? 1 : 0,
            'is_featured' => Request::input('is_featured') === '1' ? 1 : 0,
        ];
        $id = (int) Request::input('id', 0);
        $id ? $this->db()->update('plans', $row, 'id = ?', [$id]) : $this->db()->insert('plans', $row + ['created_at' => now()]);
        flash('success', __('Plan saved.'));
        redirect(base_url('/platform/plans'));
    }

    public function destroy(string $id): never
    {
        $this->db()->delete('plans', 'id = ?', [(int) $id]);
        flash('success', __('Plan deleted.'));
        redirect(base_url('/platform/plans'));
    }
}
