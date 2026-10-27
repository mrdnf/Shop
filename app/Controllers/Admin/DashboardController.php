<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Services\Inventory\InventoryService;

final class DashboardController extends AdminController
{
    public function index(): string
    {
        $db = $this->db();
        $today = date('Y-m-d');
        $monthStart = date('Y-m-01');
        $lastMonthStart = date('Y-m-01', strtotime('first day of last month'));

        $revenue = static fn (string $from, ?string $to = null) => (float) $db->value(
            "SELECT COALESCE(SUM(total),0) FROM orders WHERE status <> 'cancelled' AND created_at >= ?" . ($to ? ' AND created_at < ?' : ''),
            $to ? [$from, $to] : [$from]
        );
        $month = $revenue($monthStart);
        $lastMonth = $revenue($lastMonthStart, $monthStart);

        // 30-day sales series
        $rows = $db->fetchAll(
            "SELECT DATE(created_at) d, SUM(total) revenue, COUNT(*) orders FROM orders
             WHERE status <> 'cancelled' AND created_at >= ? GROUP BY DATE(created_at)",
            [date('Y-m-d', strtotime('-29 days'))]
        );
        $byDay = array_column($rows, null, 'd');
        $series = ['labels' => [], 'revenue' => [], 'orders' => []];
        for ($i = 29; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $series['labels'][] = date('M j', strtotime($d));
            $series['revenue'][] = round((float) ($byDay[$d]['revenue'] ?? 0), 2);
            $series['orders'][] = (int) ($byDay[$d]['orders'] ?? 0);
        }

        $statusCounts = [];
        foreach ($db->fetchAll('SELECT status, COUNT(*) c FROM orders GROUP BY status') as $row) {
            $statusCounts[$row['status']] = (int) $row['c'];
        }

        return $this->view('admin/dashboard', [
            'title' => __('Dashboard'),
            'kpis' => [
                'today' => $revenue($today),
                'month' => $month,
                'growth' => $lastMonth > 0 ? round(($month - $lastMonth) / $lastMonth * 100, 1) : null,
                'orders_today' => (int) $db->value('SELECT COUNT(*) FROM orders WHERE created_at >= ?', [$today]),
                'pending' => (int) $db->value("SELECT COUNT(*) FROM orders WHERE status IN ('pending','confirmed')"),
                'customers' => (int) $db->value("SELECT COUNT(*) FROM users WHERE role = 'customer'"),
                'in_delivery' => (int) $db->value("SELECT COUNT(*) FROM shipments WHERE status IN ('picked_up','in_transit','out_for_delivery')"),
                'low_stock' => (int) $db->value('SELECT COUNT(*) FROM products WHERE track_stock = 1 AND is_active = 1 AND stock <= low_stock_threshold'),
            ],
            'series' => $series,
            'statusCounts' => $statusCounts,
            'recentOrders' => $db->fetchAll('SELECT * FROM orders ORDER BY id DESC LIMIT 7'),
            'lowStock' => $db->fetchAll('SELECT * FROM products WHERE track_stock = 1 AND is_active = 1 AND stock <= low_stock_threshold ORDER BY stock ASC LIMIT 6'),
            'topProducts' => $db->fetchAll(
                "SELECT oi.name, SUM(oi.qty) qty, SUM(oi.total) revenue FROM order_items oi JOIN orders o ON o.id = oi.order_id
                 WHERE o.status <> 'cancelled' AND o.created_at >= ? GROUP BY oi.name ORDER BY qty DESC LIMIT 5",
                [date('Y-m-d', strtotime('-30 days'))]
            ),
            'inventoryProvider' => InventoryService::providerKey(),
            'lastSync' => $db->fetch('SELECT * FROM inventory_sync_runs ORDER BY id DESC LIMIT 1'),
        ]);
    }
}
