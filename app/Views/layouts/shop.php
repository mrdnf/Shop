<?php
use App\Core\Auth;
use App\Core\DB;
use App\Core\View;
use App\Services\Cart;
use App\Services\Theme;

$layoutMode = Theme::menuLayout('shop');
$cartCount = Cart::count();
$navCategories = DB::tenant()->fetchAll('SELECT * FROM categories WHERE is_active = 1 AND parent_id IS NULL ORDER BY sort, id LIMIT 12');
$user = auth();
$userLinks = [];
if ($user) {
    if (Auth::is('admin', 'staff')) {
        $userLinks[] = [__('Admin panel'), url('/admin'), 'layout-dashboard'];
    }
    if (Auth::is('driver')) {
        $userLinks[] = [__('Driver app'), url('/driver'), 'truck'];
    }
    $userLinks[] = [__('My account'), url('/account'), 'user-round'];
    $userLinks[] = [__('My orders'), url('/account/orders'), 'package'];
}
$links = [
    [__('Home'), url('/'), 'house', is_active('/', true)],
    [__('Shop'), url('/products'), 'store', is_active('/products') || is_active('/category') || is_active('/product')],
    [__('Track order'), url('/track'), 'map-pinned', is_active('/track')],
];

ob_start(); ?>
<?php /* ---------- shared header pieces ---------- */ ?>
<?php $searchForm = static function (string $class = '') { ?>
    <form action="<?= e(url('/products')) ?>" class="<?= $class ?>">
        <label class="input input-sm md:input-md w-full rounded-full bg-base-200/70 border-transparent focus-within:border-primary/40">
            <?= icon('search', 'size-4 opacity-50') ?>
            <input type="search" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="<?= e(__('Search products…')) ?>" class="grow">
        </label>
    </form>
<?php }; ?>
<?php $actions = static function () use ($cartCount, $user, $userLinks) { ?>
    <?= View::partial('partials/controls') ?>
    <a href="<?= e(url('/cart')) ?>" class="btn btn-ghost btn-sm btn-circle relative" title="<?= e(__('Cart')) ?>">
        <?= icon('shopping-bag', 'size-5') ?>
        <span data-cart-count class="badge badge-primary badge-xs absolute -top-1 -end-1 font-bold <?= $cartCount ? '' : 'hidden' ?>"><?= $cartCount ?></span>
    </a>
    <?php if ($user): ?>
        <?= View::partial('partials/user_menu', ['links' => $userLinks]) ?>
    <?php else: ?>
        <a href="<?= e(url('/login')) ?>" class="btn btn-primary btn-sm rounded-full px-4 shadow-lg shadow-primary/25"><?= icon('log-in', 'size-4') ?> <span class="hidden sm:inline"><?= e(__('Sign in')) ?></span></a>
    <?php endif; ?>
<?php }; ?>

<?php $footer = static function () use ($navCategories) { ?>
<footer class="mt-16 border-t border-base-content/5 bg-base-100">
    <div class="max-w-7xl mx-auto px-4 py-12 grid gap-10 md:grid-cols-4">
        <div class="md:col-span-2 space-y-3">
            <?= View::partial('partials/brand', ['forceName' => true]) ?>
            <p class="opacity-70 max-w-md"><?= e(setting('company.tagline_' . locale()) ?: __('Quality products, live stock and fast delivery you can track.')) ?></p>
            <div class="flex gap-2"><?= View::partial('partials/controls') ?></div>
        </div>
        <div>
            <h4 class="font-bold mb-3"><?= e(__('Shop')) ?></h4>
            <ul class="space-y-2 opacity-75 text-sm">
                <?php foreach (array_slice($navCategories, 0, 5) as $c): ?>
                    <li><a class="link link-hover" href="<?= e(url('/category/' . $c['slug'])) ?>"><?= e(tr($c, 'name')) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div>
            <h4 class="font-bold mb-3"><?= e(__('Help')) ?></h4>
            <ul class="space-y-2 opacity-75 text-sm">
                <li><a class="link link-hover" href="<?= e(url('/track')) ?>"><?= e(__('Track order')) ?></a></li>
                <li><a class="link link-hover" href="<?= e(url('/account')) ?>"><?= e(__('My account')) ?></a></li>
                <?php if (setting('company.phone')): ?><li class="flex items-center gap-2"><?= icon('phone', 'size-4') ?><span dir="ltr"><?= e(setting('company.phone')) ?></span></li><?php endif; ?>
                <?php if (setting('company.email')): ?><li class="flex items-center gap-2"><?= icon('mail', 'size-4') ?><?= e(setting('company.email')) ?></li><?php endif; ?>
            </ul>
        </div>
    </div>
    <div class="border-t border-base-content/5 py-5 text-center text-xs opacity-60">
        © <?= date('Y') ?> <?= e(company_name()) ?> · <?= e(__('Powered by')) ?> <span class="font-semibold">Matjar</span>
    </div>
</footer>
<?php }; ?>

<?php if ($layoutMode === 'top'): ?>
    <header class="sticky top-0 z-40 glass border-b border-base-content/5">
        <div class="max-w-7xl mx-auto px-4 h-16 flex items-center gap-3">
            <div class="dropdown lg:hidden">
                <div tabindex="0" role="button" class="btn btn-ghost btn-sm btn-circle"><?= icon('menu') ?></div>
                <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-50 mt-3 w-64 p-2 shadow-2xl">
                    <?php foreach ($links as [$label, $href, $ic, $active]): ?>
                        <li><a href="<?= e($href) ?>" class="<?= $active ? 'menu-active' : '' ?>"><?= icon($ic, 'size-4') ?><?= e($label) ?></a></li>
                    <?php endforeach; ?>
                    <li class="menu-title"><?= e(__('Categories')) ?></li>
                    <?php foreach ($navCategories as $c): ?>
                        <li><a href="<?= e(url('/category/' . $c['slug'])) ?>"><?= icon($c['icon'] ?: 'tag', 'size-4') ?><?= e(tr($c, 'name')) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?= View::partial('partials/brand') ?>
            <nav class="hidden lg:flex items-center gap-1 ms-4">
                <?php foreach ($links as [$label, $href, $ic, $active]): ?>
                    <a href="<?= e($href) ?>" class="btn btn-ghost btn-sm rounded-full font-semibold <?= $active ? 'bg-primary/10 text-primary' : '' ?>"><?= e($label) ?></a>
                <?php endforeach; ?>
                <?php if ($navCategories): ?>
                    <div class="dropdown dropdown-hover">
                        <div tabindex="0" role="button" class="btn btn-ghost btn-sm rounded-full font-semibold"><?= e(__('Categories')) ?> <?= icon('chevron-down', 'size-4 opacity-60') ?></div>
                        <div tabindex="0" class="dropdown-content z-50 pt-2">
                            <div class="grid grid-cols-2 gap-1 w-[26rem] p-3 bg-base-100 rounded-box shadow-2xl border border-base-content/5">
                                <?php foreach ($navCategories as $c): ?>
                                    <a href="<?= e(url('/category/' . $c['slug'])) ?>" class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-base-200 transition">
                                        <span class="size-9 rounded-xl bg-primary/10 text-primary grid place-items-center"><?= icon($c['icon'] ?: 'tag', 'size-4') ?></span>
                                        <span class="font-medium text-sm"><?= e(tr($c, 'name')) ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </nav>
            <div class="flex-1 hidden md:block max-w-md ms-auto"><?php $searchForm(); ?></div>
            <div class="flex items-center gap-1 ms-auto md:ms-2"><?php $actions(); ?></div>
        </div>
        <div class="md:hidden px-4 pb-3"><?php $searchForm(); ?></div>
    </header>
    <main class="min-h-[60vh]"><?= $content ?></main>
    <?php $footer(); ?>
<?php else: ?>
    <div class="drawer lg:drawer-open">
        <input id="shop-drawer" type="checkbox" class="drawer-toggle">
        <div class="drawer-content flex flex-col min-h-screen">
            <header class="sticky top-0 z-30 glass border-b border-base-content/5">
                <div class="px-4 h-16 flex items-center gap-3">
                    <label for="shop-drawer" class="btn btn-ghost btn-sm btn-circle lg:hidden"><?= icon('menu') ?></label>
                    <div class="lg:hidden"><?= View::partial('partials/brand', ['showName' => false]) ?></div>
                    <div class="flex-1 max-w-xl"><?php $searchForm('hidden sm:block'); ?></div>
                    <div class="flex items-center gap-1 ms-auto"><?php $actions(); ?></div>
                </div>
            </header>
            <main class="flex-1"><?= $content ?></main>
            <?php $footer(); ?>
        </div>
        <div class="drawer-side z-40">
            <label for="shop-drawer" class="drawer-overlay"></label>
            <aside class="w-72 min-h-full bg-base-100 border-e border-base-content/5 flex flex-col">
                <div class="h-16 px-5 flex items-center border-b border-base-content/5"><?= View::partial('partials/brand') ?></div>
                <ul class="menu w-full p-3 gap-0.5">
                    <?php foreach ($links as [$label, $href, $ic, $active]): ?>
                        <li><a href="<?= e($href) ?>" class="<?= $active ? 'menu-active' : '' ?> py-2.5"><?= icon($ic, 'size-[18px]') ?><?= e($label) ?></a></li>
                    <?php endforeach; ?>
                    <li class="menu-title mt-3"><?= e(__('Categories')) ?></li>
                    <?php foreach ($navCategories as $c): ?>
                        <li><a href="<?= e(url('/category/' . $c['slug'])) ?>" class="<?= is_active('/category/' . $c['slug']) ? 'menu-active' : '' ?>">
                            <span class="size-7 rounded-lg bg-primary/10 text-primary grid place-items-center"><?= icon($c['icon'] ?: 'tag', 'size-4') ?></span><?= e(tr($c, 'name')) ?></a></li>
                    <?php endforeach; ?>
                </ul>
                <div class="mt-auto p-4">
                    <div class="rounded-box bg-brand p-4 shadow-xl shadow-primary/20">
                        <div class="font-bold flex items-center gap-2"><?= icon('truck', 'size-5') ?><?= e(__('Live delivery tracking')) ?></div>
                        <p class="text-sm opacity-90 mt-1"><?= e(__('Follow your order on the map in real time.')) ?></p>
                        <a href="<?= e(url('/track')) ?>" class="btn btn-sm mt-3 bg-white/20 border-0 text-inherit hover:bg-white/30"><?= e(__('Track order')) ?></a>
                    </div>
                </div>
            </aside>
        </div>
    </div>
<?php endif; ?>


<?php
$content = ob_get_clean();
echo View::partial('layouts/base', compact('content') + ['title' => $title ?? null, 'head' => $head ?? '', 'scripts' => $scripts ?? '']);
