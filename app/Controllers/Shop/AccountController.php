<?php
declare(strict_types=1);

namespace App\Controllers\Shop;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Services\Delivery\DeliveryService;

/** The customer ("users") area. */
final class AccountController extends Controller
{
    protected string $layout = 'layouts/account';

    public function index(): string
    {
        $db = $this->db();
        $uid = Auth::id();
        return $this->view('shop/account/dashboard', [
            'title' => __('My account'),
            'stats' => [
                'orders' => (int) $db->value('SELECT COUNT(*) FROM orders WHERE user_id = ?', [$uid]),
                'active' => (int) $db->value("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status IN ('pending','confirmed','processing','shipped')", [$uid]),
                'spent' => (float) $db->value("SELECT COALESCE(SUM(total),0) FROM orders WHERE user_id = ? AND status <> 'cancelled'", [$uid]),
                'addresses' => (int) $db->value('SELECT COUNT(*) FROM addresses WHERE user_id = ?', [$uid]),
            ],
            'orders' => $db->fetchAll('SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 5', [$uid]),
            'deliveries' => $db->fetchAll(
                "SELECT s.*, o.number FROM shipments s JOIN orders o ON o.id = s.order_id
                 WHERE o.user_id = ? AND s.status NOT IN ('delivered','cancelled','returned') ORDER BY s.id DESC LIMIT 3",
                [$uid]
            ),
        ]);
    }

    public function orders(): string
    {
        return $this->view('shop/account/orders', [
            'title' => __('My orders'),
            'page' => $this->db()->paginate('SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC', [Auth::id()], 10),
        ]);
    }

    public function order(string $number): string
    {
        $db = $this->db();
        $order = $db->fetch('SELECT * FROM orders WHERE number = ? AND user_id = ?', [$number, Auth::id()]);
        if (!$order) {
            abort(404);
        }
        $shipments = $db->fetchAll('SELECT * FROM shipments WHERE order_id = ? ORDER BY id DESC', [$order['id']]);
        foreach ($shipments as &$s) {
            $s['events'] = $db->fetchAll('SELECT * FROM tracking_events WHERE shipment_id = ? ORDER BY created_at DESC, id DESC', [$s['id']]);
            $s['external_url'] = DeliveryService::externalTrackingUrl($s);
        }
        unset($s);
        return $this->view('shop/account/order', [
            'title' => __('Order :number', ['number' => $number]),
            'order' => $order,
            'items' => $db->fetchAll('SELECT oi.*, p.image, p.slug FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?', [$order['id']]),
            'history' => $db->fetchAll('SELECT * FROM order_status_history WHERE order_id = ? ORDER BY id', [$order['id']]),
            'shipments' => $shipments,
        ]);
    }

    public function addresses(): string
    {
        return $this->view('shop/account/addresses', [
            'title' => __('My addresses'),
            'addresses' => $this->db()->fetchAll('SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC', [Auth::id()]),
        ]);
    }

    public function saveAddress(): never
    {
        $data = $this->validate([
            'label' => 'nullable|max:60',
            'full_name' => 'required|max:120',
            'phone' => 'required|max:40',
            'country' => 'nullable|max:80',
            'city' => 'required|max:80',
            'area' => 'nullable|max:120',
            'street' => 'required|max:190',
            'building' => 'nullable|max:120',
            'notes' => 'nullable|max:255',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
        ]);
        $data = array_map(static fn ($v) => $v === '' ? null : $v, $data);
        $data['is_default'] = Request::input('is_default') === '1' ? 1 : 0;
        $db = $this->db();
        if ($data['is_default']) {
            $db->update('addresses', ['is_default' => 0], 'user_id = ?', [Auth::id()]);
        }
        $id = (int) Request::input('id', 0);
        if ($id) {
            $db->update('addresses', $data, 'id = ? AND user_id = ?', [$id, Auth::id()]);
        } else {
            $db->insert('addresses', $data + ['user_id' => Auth::id(), 'created_at' => now()]);
        }
        flash('success', __('Address saved.'));
        redirect(url('/account/addresses'));
    }

    public function deleteAddress(string $id): never
    {
        $this->db()->delete('addresses', 'id = ? AND user_id = ?', [(int) $id, Auth::id()]);
        flash('success', __('Address removed.'));
        redirect(url('/account/addresses'));
    }

    public function profile(): string
    {
        return $this->view('shop/account/profile', ['title' => __('Profile'), 'user' => auth()]);
    }

    public function saveProfile(): never
    {
        $data = $this->validate(['name' => 'required|max:120', 'email' => 'required|email|max:190', 'phone' => 'nullable|max:40', 'locale' => 'required|in:en,ar']);
        $email = strtolower((string) $data['email']);
        if ($this->db()->value('SELECT 1 FROM users WHERE email = ? AND id <> ?', [$email, Auth::id()])) {
            flash('error', __('An account with this email already exists.'));
            back();
        }
        $this->db()->update('users', ['name' => $data['name'], 'email' => $email, 'phone' => $data['phone'] ?: null, 'locale' => $data['locale'], 'updated_at' => now()], 'id = ?', [Auth::id()]);
        setcookie('lang', (string) $data['locale'], ['expires' => time() + 31536000, 'path' => '/', 'samesite' => 'Lax']);
        flash('success', __('Profile updated.'));
        back();
    }

    public function savePassword(): never
    {
        $this->validate(['current_password' => 'required', 'password' => 'required|min:8|confirmed']);
        if (!password_verify((string) $_POST['current_password'], (string) auth()['password'])) {
            flash('error', __('Your current password is incorrect.'));
            back();
        }
        $this->db()->update('users', ['password' => password_hash((string) $_POST['password'], PASSWORD_DEFAULT)], 'id = ?', [Auth::id()]);
        flash('success', __('Password changed.'));
        back();
    }
}
