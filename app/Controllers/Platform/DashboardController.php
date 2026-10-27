<?php
declare(strict_types=1);

namespace App\Controllers\Platform;

use App\Core\DB;
use App\Core\Tenant;

final class DashboardController extends PlatformController
{
    public function index(): string
    {
        $db = $this->db();
        $tenants = $db->fetchAll('SELECT t.*, p.name AS plan_name, p.price AS plan_price FROM tenants t LEFT JOIN plans p ON p.id = t.plan_id ORDER BY t.id DESC');

        // Light per-shop metrics (each shop has its own database).
        $orders = 0;
        $revenue = 0.0;
        foreach ($tenants as &$t) {
            $t['url'] = Tenant::publicUrl($t);
            $t['orders'] = null;
            try {
                $tdb = DB::forTenant($t);
                $row = $tdb->fetch("SELECT COUNT(*) c, COALESCE(SUM(total),0) r FROM orders WHERE status <> 'cancelled' AND created_at >= ?", [date('Y-m-01')]);
                $t['orders'] = (int) $row['c'];
                $t['revenue'] = (float) $row['r'];
                $t['products'] = (int) $tdb->value('SELECT COUNT(*) FROM products');
                $orders += $t['orders'];
                $revenue += $t['revenue'];
            } catch (\Throwable $e) {
                $t['error'] = $e->getMessage();
            }
        }
        unset($t);

        $mrr = 0.0;
        foreach ($tenants as $t) {
            if ($t['status'] === 'active' && $t['plan_price']) {
                $mrr += (float) $t['plan_price'];
            }
        }

        return $this->view('platform/dashboard', [
            'title' => __('Platform overview'),
            'tenants' => array_slice($tenants, 0, 8),
            'stats' => [
                'shops' => count($tenants),
                'active' => count(array_filter($tenants, static fn ($t) => $t['status'] === 'active')),
                'trial' => count(array_filter($tenants, static fn ($t) => $t['status'] === 'trial')),
                'suspended' => count(array_filter($tenants, static fn ($t) => $t['status'] === 'suspended')),
                'orders' => $orders,
                'gmv' => $revenue,
                'mrr' => $mrr,
            ],
            'byPlan' => $db->fetchAll('SELECT p.name, COUNT(t.id) c FROM plans p LEFT JOIN tenants t ON t.plan_id = p.id GROUP BY p.id ORDER BY p.sort'),
        ]);
    }
}
