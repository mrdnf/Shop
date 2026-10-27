<?php
use App\Core\Auth;
use App\Core\DB;
use App\Core\Tenant;
use App\Core\View;
use App\Services\Theme;

$db = DB::tenant();
$pending = (int) $db->value("SELECT COUNT(*) FROM orders WHERE status IN ('pending','confirmed')");
$owner = Auth::is('admin');
$plan = Tenant::plan();

$menu = [
    ['title' => null, 'items' => [
        [__('Dashboard'), url('/admin'), 'layout-dashboard', is_active('/admin', true)],
        [__('Orders'), url('/admin/orders'), 'shopping-cart', is_active('/admin/orders'), $pending ?: null],
        [__('Shipments'), url('/admin/shipments'), 'truck', is_active('/admin/shipments')],
        [__('Products'), url('/admin/products'), 'package', is_active('/admin/products')],
        [__('Categories'), url('/admin/categories'), 'layers', is_active('/admin/categories')],
        [__('Customers'), url('/admin/customers'), 'users', is_active('/admin/customers')],
    ]],
];
if ($owner) {
    $menu[] = ['title' => __('Integrations'), 'items' => [
        [__('Inventory sync'), url('/admin/inventory'), 'refresh-cw', is_active('/admin/inventory')],
        [__('Delivery'), url('/admin/delivery'), 'map', is_active('/admin/delivery')],
        [__('API & webhooks'), url('/admin/api-keys'), 'webhook', is_active('/admin/api-keys')],
    ]];
    $menu[] = ['title' => __('Settings'), 'items' => [
        [__('Branding & theme'), url('/admin/theme'), 'palette', is_active('/admin/theme')],
        [__('Store settings'), url('/admin/settings'), 'settings', is_active('/admin/settings')],
        [__('Team'), url('/admin/staff'), 'user-cog', is_active('/admin/staff')],
    ]];
}
$menu[] = ['title' => __('Apps'), 'items' => [
    [__('Driver app'), url('/driver'), 'smartphone', false],
]];

$sidebarFooter = '<div class="rounded-box border border-base-content/10 p-4 bg-base-200/50">'
    . '<div class="flex items-center gap-2 text-sm font-semibold">' . icon('database', 'size-4 text-primary') . e(__('Dedicated database')) . '</div>'
    . '<div class="text-xs opacity-60 mt-1 font-mono truncate">' . e(Tenant::current()['db_name'] ?? '') . '</div>'
    . ($plan ? '<div class="badge badge-primary badge-soft badge-sm mt-2">' . e(__('Plan')) . ': ' . e($plan['name']) . '</div>' : '')
    . '</div>';

echo View::partial('layouts/panel', [
    'content' => $content,
    'title' => $title ?? '',
    'head' => $head ?? '',
    'scripts' => $scripts ?? '',
    'menu' => $menu,
    'layoutMode' => Theme::menuLayout('admin'),
    'panelName' => company_name() . ' · ' . __('Admin'),
    'homeUrl' => url('/admin'),
    'storeUrl' => url('/'),
    'logoutUrl' => url('/logout'),
    'userLinks' => [[__('View store'), url('/'), 'store'], [__('My profile'), url('/account/profile'), 'user-round']],
    'sidebarFooter' => $sidebarFooter,
]);
