<?php
use App\Core\Auth;
use App\Core\View;

$menu = [['title' => null, 'items' => [
    [__('My deliveries'), url('/driver'), 'truck', is_active('/driver', true)],
]]];
if (Auth::is('admin', 'staff')) {
    $menu[0]['items'][] = [__('Admin panel'), url('/admin'), 'layout-dashboard', false];
}

echo View::partial('layouts/panel', [
    'content' => $content,
    'title' => $title ?? '',
    'head' => $head ?? '',
    'scripts' => $scripts ?? '',
    'menu' => $menu,
    'layoutMode' => 'top',
    'panelName' => __('Driver app'),
    'homeUrl' => url('/driver'),
    'logoutUrl' => url('/logout'),
    'userLinks' => [],
]);
