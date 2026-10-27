<?php
declare(strict_types=1);

namespace App\Controllers\Shop;

use App\Core\Controller;
use App\Core\Request;
use App\Services\Cart;
use App\Services\Inventory\InventoryService;

final class CartController extends Controller
{
    public function show(): string
    {
        return $this->view('shop/cart', ['title' => __('Your cart'), 'cart' => Cart::detailed()]);
    }

    public function add(): never
    {
        $id = (int) Request::input('product_id');
        $qty = max(1, (int) Request::input('qty', 1));
        $product = $this->db()->fetch('SELECT * FROM products WHERE id = ? AND is_active = 1', [$id]);
        if (!$product) {
            $this->respond(false, __('Product not found.'));
        }
        $wanted = (Cart::items()[$id] ?? 0) + $qty;
        $available = InventoryService::available($product);
        if ($available < $wanted) {
            $this->respond(false, $available > 0
                ? __('Only :qty left of :name.', ['qty' => $available, 'name' => tr($product, 'name')])
                : __(':name is out of stock.', ['name' => tr($product, 'name')]));
        }
        Cart::add($id, $qty);
        $this->respond(true, __(':name added to your cart.', ['name' => tr($product, 'name')]));
    }

    public function update(): never
    {
        foreach ((array) Request::input('qty', []) as $id => $qty) {
            $product = $this->db()->fetch('SELECT * FROM products WHERE id = ?', [(int) $id]);
            if (!$product) {
                continue;
            }
            $qty = max(0, (int) $qty);
            $available = InventoryService::available($product);
            if ($qty > $available) {
                flash('warning', __('Only :qty left of :name.', ['qty' => $available, 'name' => tr($product, 'name')]));
                $qty = $available;
            }
            Cart::set((int) $id, $qty);
        }
        redirect(url('/cart'));
    }

    public function remove(): never
    {
        Cart::remove((int) Request::input('product_id'));
        redirect(url('/cart'));
    }

    private function respond(bool $ok, string $message): never
    {
        if (Request::wantsJson()) {
            json_response(['ok' => $ok, 'message' => $message, 'count' => Cart::count()], $ok ? 200 : 422);
        }
        flash($ok ? 'success' : 'error', $message);
        back();
    }
}
