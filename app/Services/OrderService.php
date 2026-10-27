<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\DB;
use App\Core\HttpException;
use App\Core\Settings;
use App\Services\Delivery\DeliveryService;
use App\Services\Inventory\InventoryService;

final class OrderService
{
    /**
     * Turn the cart into an order. Stock is re-checked (in real time when the
     * shop is connected to an external inventory) and reserved atomically.
     */
    public static function place(array $customer, array $cartLines, ?int $userId, string $paymentMethod): array
    {
        if (!$cartLines) {
            throw new HttpException(422, __('Your cart is empty.'));
        }
        $db = DB::tenant();

        // Real-time availability (may call the external system) before locking rows.
        foreach ($cartLines as $line) {
            $available = InventoryService::available($line['product']);
            if ($available < $line['qty']) {
                throw new HttpException(422, __('Only :qty left of :name.', ['qty' => $available, 'name' => tr($line['product'], 'name')]));
            }
        }

        $zone = DeliveryService::zoneForCity((string) $customer['city']);
        if (!$zone && $db->value('SELECT COUNT(*) FROM shipping_zones WHERE is_active = 1')) {
            throw new HttpException(422, __('Sorry, we do not deliver to :city yet.', ['city' => $customer['city']]));
        }

        $order = $db->transaction(function ($db) use ($customer, $cartLines, $userId, $paymentMethod, $zone) {
            $subtotal = 0.0;
            $items = [];
            foreach ($cartLines as $line) {
                $product = $db->fetch('SELECT * FROM products WHERE id = ? FOR UPDATE', [$line['product']['id']]);
                if (!$product || !$product['is_active']) {
                    throw new HttpException(422, __('A product in your cart is no longer available.'));
                }
                if ($product['track_stock'] && (int) $product['stock'] < $line['qty']) {
                    throw new HttpException(422, __('Only :qty left of :name.', ['qty' => max(0, (int) $product['stock']), 'name' => tr($product, 'name')]));
                }
                $total = round((float) $product['price'] * $line['qty'], 2);
                $subtotal += $total;
                $items[] = [
                    'product_id' => $product['id'], 'sku' => $product['sku'], 'name' => tr($product, 'name'),
                    'price' => $product['price'], 'qty' => $line['qty'], 'total' => $total, 'track' => (int) $product['track_stock'],
                ];
            }

            $shipping = DeliveryService::shippingCost($zone, $subtotal);
            $taxRate = (float) Settings::get('store.tax_rate', 0);
            $tax = round($subtotal * $taxRate / 100, 2);

            $order = [
                'number' => self::nextNumber(),
                'user_id' => $userId,
                'customer_name' => $customer['name'],
                'email' => $customer['email'] ?: null,
                'phone' => $customer['phone'],
                'country' => $customer['country'] ?? null,
                'city' => $customer['city'],
                'area' => $customer['area'] ?? null,
                'street' => $customer['street'],
                'building' => $customer['building'] ?? null,
                'address_notes' => $customer['notes'] ?? null,
                'lat' => is_numeric($customer['lat'] ?? null) ? (float) $customer['lat'] : null,
                'lng' => is_numeric($customer['lng'] ?? null) ? (float) $customer['lng'] : null,
                'zone_id' => $zone['id'] ?? null,
                'subtotal' => $subtotal,
                'shipping' => $shipping,
                'tax' => $tax,
                'total' => round($subtotal + $shipping + $tax, 2),
                'currency' => (string) Settings::get('store.currency', 'USD'),
                'payment_method' => $paymentMethod,
                'status' => 'pending',
                'locale' => locale(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $order['id'] = $db->insert('orders', $order);

            foreach ($items as $item) {
                $track = $item['track'];
                unset($item['track']);
                $db->insert('order_items', $item + ['order_id' => $order['id']]);
                if ($track) {
                    InventoryService::adjust((int) $item['product_id'], -(int) $item['qty'], 'order', 'Order ' . $order['number']);
                }
            }
            $db->insert('order_status_history', ['order_id' => $order['id'], 'status' => 'pending', 'note' => __('Order placed'), 'created_at' => now()]);
            $order['items'] = $items;
            return $order;
        });

        InventoryService::notifyOrder($order, $order['items']);
        return $order;
    }

    /** Cancel and put the stock back. */
    public static function cancel(array $order, ?int $userId, string $note = ''): void
    {
        if (in_array($order['status'], ['cancelled', 'delivered'], true)) {
            return;
        }
        $db = DB::tenant();
        $items = $db->fetchAll('SELECT oi.*, p.track_stock FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?', [$order['id']]);
        foreach ($items as $item) {
            if ($item['product_id'] && $item['track_stock']) {
                InventoryService::adjust((int) $item['product_id'], (int) $item['qty'], 'order', 'Cancelled ' . $order['number']);
            }
        }
        DeliveryService::setOrderStatus((int) $order['id'], 'cancelled', $note ?: __('Order cancelled'), $userId);
        $db->query("UPDATE shipments SET status = 'cancelled', updated_at = ? WHERE order_id = ? AND status NOT IN ('delivered','returned')", [now(), $order['id']]);
        InventoryService::notifyOrder($order + ['status' => 'cancelled'], $items, 'order.cancelled');
    }

    private static function nextNumber(): string
    {
        $prefix = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) Settings::get('store.order_prefix', 'ORD')) ?: 'ORD');
        do {
            $number = $prefix . '-' . date('ymd') . '-' . random_int(1000, 9999);
        } while (DB::tenant()->value('SELECT 1 FROM orders WHERE number = ?', [$number]));
        return $number;
    }
}
