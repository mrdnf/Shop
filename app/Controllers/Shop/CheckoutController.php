<?php
declare(strict_types=1);

namespace App\Controllers\Shop;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Session;
use App\Core\Settings;
use App\Services\Cart;
use App\Services\Delivery\DeliveryService;
use App\Services\OrderService;

final class CheckoutController extends Controller
{
    public static function paymentMethods(): array
    {
        $methods = [];
        if (Settings::get('payment.cod', '1') === '1') {
            $methods['cod'] = ['label' => __('Cash on delivery'), 'icon' => 'banknote'];
        }
        if (Settings::get('payment.bank', '0') === '1') {
            $methods['bank'] = ['label' => __('Bank transfer'), 'icon' => 'landmark'];
        }
        return $methods ?: ['cod' => ['label' => __('Cash on delivery'), 'icon' => 'banknote']];
    }

    private function guard(): void
    {
        if (!Auth::check() && Settings::get('store.allow_guest_checkout', '1') !== '1') {
            flash('warning', __('Please sign in to checkout.'));
            redirect(url('/login') . '?next=' . rawurlencode('/checkout'));
        }
    }

    public function show(): string
    {
        $this->guard();
        $cart = Cart::detailed();
        if (!$cart['lines']) {
            redirect(url('/cart'));
        }
        $addresses = Auth::check()
            ? $this->db()->fetchAll('SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC', [Auth::id()])
            : [];
        return $this->view('shop/checkout', [
            'title' => __('Checkout'),
            'cart' => $cart,
            'addresses' => $addresses,
            'zones' => $this->db()->fetchAll('SELECT * FROM shipping_zones WHERE is_active = 1 ORDER BY sort, id'),
            'methods' => self::paymentMethods(),
            'taxRate' => (float) Settings::get('store.tax_rate', 0),
        ]);
    }

    /** Live shipping quote while the customer types the city. */
    public function zone(): array
    {
        $cart = Cart::detailed();
        $zone = DeliveryService::zoneForCity((string) Request::query('city', ''));
        $shipping = DeliveryService::shippingCost($zone, $cart['subtotal']);
        $tax = round($cart['subtotal'] * (float) Settings::get('store.tax_rate', 0) / 100, 2);
        return [
            'found' => (bool) $zone,
            'zone' => $zone ? tr($zone, 'name') : null,
            'eta' => $zone ? __(':min-:max days', ['min' => $zone['eta_min_days'], 'max' => $zone['eta_max_days']]) : null,
            'shipping' => money($shipping),
            'tax' => money($tax),
            'total' => money($cart['subtotal'] + $shipping + $tax),
        ];
    }

    public function place(): never
    {
        $this->guard();
        $methods = self::paymentMethods();
        $addressId = (int) Request::input('address_id', 0);

        if ($addressId && Auth::check()) {
            $address = $this->db()->fetch('SELECT * FROM addresses WHERE id = ? AND user_id = ?', [$addressId, Auth::id()]);
            if (!$address) {
                abort(404);
            }
            $customer = [
                'name' => $address['full_name'], 'phone' => $address['phone'], 'email' => auth()['email'],
                'country' => $address['country'], 'city' => $address['city'], 'area' => $address['area'],
                'street' => $address['street'], 'building' => $address['building'], 'notes' => $address['notes'],
                'lat' => $address['lat'], 'lng' => $address['lng'],
            ];
        } else {
            $customer = $this->validate([
                'name' => 'required|max:120',
                'phone' => 'required|max:40',
                'email' => (Auth::check() ? 'nullable' : 'required') . '|email|max:190',
                'country' => 'nullable|max:80',
                'city' => 'required|max:80',
                'area' => 'nullable|max:120',
                'street' => 'required|max:190',
                'building' => 'nullable|max:120',
                'notes' => 'nullable|max:255',
                'lat' => 'nullable|numeric',
                'lng' => 'nullable|numeric',
            ]);
            if (Auth::check() && !$customer['email']) {
                $customer['email'] = auth()['email'];
            }
        }

        $payment = (string) Request::input('payment_method', array_key_first($methods));
        if (!isset($methods[$payment])) {
            $payment = (string) array_key_first($methods);
        }

        try {
            $order = OrderService::place($customer, Cart::detailed()['lines'], Auth::id(), $payment);
        } catch (HttpException $e) {
            flash('error', $e->getMessage());
            flash('old', Request::all());
            redirect(url('/checkout'));
        }

        if (Auth::check() && !$addressId && Request::input('save_address') === '1') {
            $this->db()->insert('addresses', [
                'user_id' => Auth::id(), 'label' => __('Home'), 'full_name' => $customer['name'], 'phone' => $customer['phone'],
                'country' => $customer['country'], 'city' => $customer['city'], 'area' => $customer['area'],
                'street' => $customer['street'], 'building' => $customer['building'], 'notes' => $customer['notes'],
                'lat' => $order['lat'], 'lng' => $order['lng'],
                'is_default' => (int) !$this->db()->value('SELECT COUNT(*) FROM addresses WHERE user_id = ?', [Auth::id()]),
                'created_at' => now(),
            ]);
        }

        Cart::clear();
        $recent = Session::get('recent_orders', []);
        $recent[] = $order['number'];
        Session::set('recent_orders', array_slice($recent, -10));
        redirect(url('/order/' . $order['number'] . '/thanks'));
    }

    public function thanks(string $number): string
    {
        $order = $this->db()->fetch('SELECT * FROM orders WHERE number = ?', [$number]);
        $mine = $order && (in_array($number, Session::get('recent_orders', []), true) || ($order['user_id'] && (int) $order['user_id'] === Auth::id()));
        if (!$mine) {
            abort(404);
        }
        return $this->view('shop/thanks', [
            'title' => __('Thank you!'),
            'order' => $order,
            'items' => $this->db()->fetchAll('SELECT * FROM order_items WHERE order_id = ?', [$order['id']]),
            'bankDetails' => $order['payment_method'] === 'bank' ? (string) Settings::get('payment.bank_details', '') : '',
        ]);
    }
}
