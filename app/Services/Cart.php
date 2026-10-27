<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\DB;
use App\Core\Tenant;

/** Session cart, one per shop. */
final class Cart
{
    private static function key(): string
    {
        return 'cart_' . Tenant::id();
    }

    public static function items(): array
    {
        return $_SESSION[self::key()] ?? [];
    }

    public static function set(int $productId, int $qty): void
    {
        if ($qty <= 0) {
            self::remove($productId);
            return;
        }
        $_SESSION[self::key()][$productId] = min($qty, 999);
    }

    public static function add(int $productId, int $qty = 1): void
    {
        self::set($productId, (self::items()[$productId] ?? 0) + max(1, $qty));
    }

    public static function remove(int $productId): void
    {
        unset($_SESSION[self::key()][$productId]);
    }

    public static function clear(): void
    {
        unset($_SESSION[self::key()]);
    }

    public static function count(): int
    {
        return array_sum(self::items());
    }

    /** @return array{lines: array, subtotal: float, count: int} */
    public static function detailed(): array
    {
        $items = self::items();
        if (!$items) {
            return ['lines' => [], 'subtotal' => 0.0, 'count' => 0];
        }
        $ids = array_map('intval', array_keys($items));
        $marks = implode(',', array_fill(0, count($ids), '?'));
        $products = DB::tenant()->fetchAll("SELECT * FROM products WHERE is_active = 1 AND id IN ({$marks})", $ids);

        $lines = [];
        $subtotal = 0.0;
        foreach ($products as $product) {
            $qty = (int) $items[$product['id']];
            $total = (float) $product['price'] * $qty;
            $subtotal += $total;
            $lines[] = ['product' => $product, 'qty' => $qty, 'total' => $total];
        }
        // Drop products that disappeared or were deactivated.
        foreach (array_diff($ids, array_map('intval', array_column($products, 'id'))) as $gone) {
            self::remove($gone);
        }
        return ['lines' => $lines, 'subtotal' => $subtotal, 'count' => array_sum(array_column($lines, 'qty'))];
    }
}
