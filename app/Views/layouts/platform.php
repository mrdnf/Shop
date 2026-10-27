<?php
use App\Core\View;
use App\Services\Theme;

$menu = [
    ['title' => null, 'items' => [
        [__('Overview'), base_url('/platform'), 'gauge', is_active('/platform', true)],
        [__('Shops'), base_url('/platform/shops'), 'store', is_active('/platform/shops')],
        [__('Plans'), base_url('/platform/plans'), 'badge-dollar-sign', is_active('/platform/plans')],
    ]],
    ['title' => __('Settings'), 'items' => [
        [__('Branding & settings'), base_url('/platform/settings'), 'palette', is_active('/platform/settings')],
    ]],
];

echo View::partial('layouts/panel', [
    'content' => $content,
    'title' => $title ?? '',
    'head' => $head ?? '',
    'scripts' => $scripts ?? '',
    'menu' => $menu,
    'layoutMode' => Theme::menuLayout('admin'),
    'panelName' => company_name() . ' · ' . __('SaaS platform'),
    'homeUrl' => base_url('/platform'),
    'storeUrl' => base_url('/'),
    'logoutUrl' => base_url('/platform/logout'),
    'langUrl' => base_url('/lang/' . (locale() === 'ar' ? 'en' : 'ar')),
    'userLinks' => [[__('Settings'), base_url('/platform/settings'), 'settings']],
    'sidebarFooter' => '<div class="rounded-box bg-brand p-4 text-sm shadow-lg shadow-primary/20"><div class="font-bold flex items-center gap-2">' . icon('server', 'size-4') . e(__('Multi-shop SaaS')) . '</div><p class="opacity-90 mt-1 text-xs">' . e(__('Every shop runs on its own database.')) . '</p></div>',
]);
