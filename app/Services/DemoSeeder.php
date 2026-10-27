<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/** Optional sample catalogue so a fresh shop looks alive right away. */
final class DemoSeeder
{
    public static function run(Database $db): void
    {
        $categories = [
            ['Electronics', 'إلكترونيات', 'electronics', 'smartphone'],
            ['Fashion', 'أزياء', 'fashion', 'shirt'],
            ['Home & Living', 'المنزل', 'home-living', 'sofa'],
            ['Beauty', 'جمال وعناية', 'beauty', 'sparkles'],
            ['Sports', 'رياضة', 'sports', 'dumbbell'],
            ['Books', 'كتب', 'books', 'book-open'],
        ];
        $ids = [];
        foreach ($categories as $i => [$en, $ar, $slug, $icon]) {
            $ids[$slug] = $db->insert('categories', ['name_en' => $en, 'name_ar' => $ar, 'slug' => $slug, 'icon' => $icon, 'sort' => $i, 'created_at' => now()]);
        }

        $products = [
            ['electronics', 'EL-1001', 'Wireless Noise-Cancelling Headphones', 'سماعات لاسلكية عازلة للضوضاء', 149.00, 199.00, 34, 1],
            ['electronics', 'EL-1002', 'Smart Watch Series 5', 'ساعة ذكية الإصدار 5', 229.00, null, 18, 1],
            ['electronics', 'EL-1003', 'Portable Bluetooth Speaker', 'مكبر صوت بلوتوث محمول', 59.90, 79.00, 3, 0],
            ['fashion', 'FA-2001', 'Classic Denim Jacket', 'جاكيت جينز كلاسيكي', 89.00, null, 40, 1],
            ['fashion', 'FA-2002', 'Leather Sneakers', 'حذاء رياضي جلد', 120.00, 150.00, 22, 0],
            ['home-living', 'HO-3001', 'Ceramic Coffee Set', 'طقم قهوة سيراميك', 45.00, null, 60, 1],
            ['home-living', 'HO-3002', 'Scented Candle Trio', 'مجموعة شموع معطرة', 29.00, 35.00, 0, 0],
            ['beauty', 'BE-4001', 'Vitamin C Serum', 'سيروم فيتامين سي', 39.00, null, 75, 1],
            ['beauty', 'BE-4002', 'Oud Perfume 100ml', 'عطر عود ١٠٠ مل', 175.00, 210.00, 12, 0],
            ['sports', 'SP-5001', 'Yoga Mat Pro', 'سجادة يوغا احترافية', 49.00, null, 30, 0],
            ['sports', 'SP-5002', 'Adjustable Dumbbells', 'دمبلز قابلة للتعديل', 199.00, 249.00, 8, 1],
            ['books', 'BO-6001', 'The Art of Commerce', 'فن التجارة', 19.90, null, 100, 0],
        ];
        foreach ($products as [$cat, $sku, $en, $ar, $price, $compare, $stock, $featured]) {
            $db->insert('products', [
                'category_id' => $ids[$cat], 'sku' => $sku, 'name_en' => $en, 'name_ar' => $ar, 'slug' => slugify($en),
                'description_en' => "{$en} — carefully selected for quality and value. Ships fast with live tracking.",
                'description_ar' => "{$ar} — مختار بعناية للجودة والقيمة. شحن سريع مع تتبع مباشر.",
                'price' => $price, 'compare_price' => $compare, 'stock' => $stock, 'is_featured' => $featured, 'created_at' => now(),
            ]);
        }

        $db->insert('shipping_zones', ['name_en' => 'Capital city', 'name_ar' => 'العاصمة', 'cities' => 'Riyadh, الرياض, Cairo, القاهرة, Dubai, دبي', 'rate' => 5, 'free_over' => 100, 'eta_min_days' => 0, 'eta_max_days' => 1, 'sort' => 0]);
        $db->insert('shipping_zones', ['name_en' => 'Nationwide', 'name_ar' => 'جميع المدن', 'cities' => '', 'rate' => 12, 'free_over' => 250, 'eta_min_days' => 2, 'eta_max_days' => 4, 'sort' => 1]);

        $db->insert('couriers', ['name' => 'Express Fleet', 'driver' => 'inhouse', 'created_at' => now()]);
        $db->insert('couriers', ['name' => 'Aramex', 'driver' => 'manual', 'tracking_url_template' => 'https://www.aramex.com/track/results?ShipmentNumber={tracking}', 'created_at' => now()]);
    }
}
