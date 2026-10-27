<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;

final class CustomerController extends AdminController
{
    public function index(): string
    {
        $where = ["u.role = 'customer'"];
        $params = [];
        if ($q = (string) Request::query('q', '')) {
            $where[] = '(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
            array_push($params, "%{$q}%", "%{$q}%", "%{$q}%");
        }
        return $this->view('admin/customers/index', [
            'title' => __('Customers'),
            'page' => $this->db()->paginate(
                "SELECT u.*, COUNT(o.id) AS orders_count, COALESCE(SUM(CASE WHEN o.status <> 'cancelled' THEN o.total END),0) AS spent
                 FROM users u LEFT JOIN orders o ON o.user_id = u.id WHERE " . implode(' AND ', $where) . ' GROUP BY u.id ORDER BY u.id DESC',
                $params,
                20
            ),
        ]);
    }

    public function show(string $id): string
    {
        $customer = $this->db()->fetch("SELECT * FROM users WHERE id = ? AND role = 'customer'", [(int) $id]) ?? abort(404);
        return $this->view('admin/customers/show', [
            'title' => $customer['name'],
            'customer' => $customer,
            'orders' => $this->db()->fetchAll('SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC', [$customer['id']]),
            'addresses' => $this->db()->fetchAll('SELECT * FROM addresses WHERE user_id = ?', [$customer['id']]),
        ]);
    }

    public function toggle(string $id): never
    {
        $customer = $this->db()->fetch("SELECT * FROM users WHERE id = ? AND role = 'customer'", [(int) $id]) ?? abort(404);
        $this->db()->update('users', ['status' => $customer['status'] === 'active' ? 'blocked' : 'active'], 'id = ?', [$customer['id']]);
        flash('success', __('Customer updated.'));
        back();
    }
}
