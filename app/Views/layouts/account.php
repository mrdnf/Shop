<?php
use App\Core\View;
use App\Services\Theme;

$user = auth();
$items = [
    [__('Overview'), url('/account'), 'layout-grid', is_active('/account', true)],
    [__('My orders'), url('/account/orders'), 'package', is_active('/account/orders')],
    [__('Addresses'), url('/account/addresses'), 'map-pin', is_active('/account/addresses')],
    [__('Profile'), url('/account/profile'), 'user-round', is_active('/account/profile')],
    [__('Track order'), url('/track'), 'map-pinned', false],
];
$sidebar = Theme::menuLayout('shop') === 'sidebar';
ob_start(); ?>
<section class="relative overflow-hidden bg-brand">
    <div class="blob size-72 bg-white/30 -top-24 -start-10"></div>
    <div class="blob size-72 bg-white/20 -bottom-32 end-10" style="animation-delay:-3s"></div>
    <div class="relative max-w-6xl mx-auto px-4 py-10 flex items-center gap-4">
        <div class="avatar avatar-placeholder">
            <div class="w-16 rounded-2xl bg-white/20 backdrop-blur text-2xl font-black ring-2 ring-white/40"><?= e(initials($user['name'])) ?></div>
        </div>
        <div>
            <p class="opacity-80 text-sm"><?= e(__('Welcome back')) ?></p>
            <h1 class="text-2xl md:text-3xl font-extrabold"><?= e($user['name']) ?></h1>
        </div>
    </div>
</section>
<div class="max-w-6xl mx-auto px-4 -mt-6 relative">
    <?php if ($sidebar): ?>
        <div class="grid lg:grid-cols-[15rem_1fr] gap-6">
            <aside class="surface p-2 h-fit lg:sticky lg:top-20">
                <ul class="menu w-full gap-0.5">
                    <?php foreach ($items as [$label, $href, $ic, $active]): ?>
                        <li><a href="<?= e($href) ?>" class="<?= $active ? 'menu-active' : '' ?> py-2.5"><?= icon($ic, 'size-[18px]') ?><?= e($label) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </aside>
            <div class="min-w-0 animate-fade-up"><?= $content ?></div>
        </div>
    <?php else: ?>
        <nav class="surface p-1.5 flex gap-1 overflow-x-auto mb-6">
            <?php foreach ($items as [$label, $href, $ic, $active]): ?>
                <a href="<?= e($href) ?>" class="btn btn-sm rounded-full whitespace-nowrap <?= $active ? 'btn-primary' : 'btn-ghost' ?>"><?= icon($ic, 'size-4') ?><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="animate-fade-up"><?= $content ?></div>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
echo View::partial('layouts/shop', ['content' => $content, 'title' => $title ?? '', 'head' => $head ?? '', 'scripts' => $scripts ?? '']);
