<?php
use App\Core\View;

$sort = $_GET['sort'] ?? 'newest';
$base = $category ? url('/category/' . $category['slug']) : url('/products');
?>
<section class="bg-brand-soft border-b border-base-content/5">
    <div class="max-w-7xl mx-auto px-4 py-10">
        <div class="breadcrumbs text-sm opacity-70">
            <ul>
                <li><a href="<?= e(url('/')) ?>"><?= e(__('Home')) ?></a></li>
                <li><a href="<?= e(url('/products')) ?>"><?= e(__('Shop')) ?></a></li>
                <?php if ($category): ?><li><?= e(tr($category, 'name')) ?></li><?php endif; ?>
            </ul>
        </div>
        <h1 class="text-3xl md:text-4xl font-black mt-2"><?= e($q !== '' ? __('Results for “:q”', ['q' => $q]) : $title) ?></h1>
        <p class="opacity-60 mt-1"><?= e(__(':total results', ['total' => $page['total']])) ?></p>
    </div>
</section>

<div class="max-w-7xl mx-auto px-4 py-8 grid lg:grid-cols-[16rem_1fr] gap-8">
    <aside class="space-y-6">
        <form method="get" action="<?= e($base) ?>" class="surface p-5 space-y-5">
            <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= e($q) ?>"><?php endif; ?>
            <div>
                <h3 class="font-bold mb-3"><?= e(__('Categories')) ?></h3>
                <ul class="menu menu-sm p-0 w-full">
                    <li><a href="<?= e(url('/products')) ?>" class="<?= !$category ? 'menu-active' : '' ?>"><?= e(__('All products')) ?></a></li>
                    <?php foreach ($categories as $c): ?>
                        <li><a href="<?= e(url('/category/' . $c['slug'])) ?>" class="<?= $category && $category['id'] == $c['id'] ? 'menu-active' : '' ?>"><?= icon($c['icon'] ?: 'tag', 'size-4') ?><?= e(tr($c, 'name')) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div>
                <h3 class="font-bold mb-3"><?= e(__('Price')) ?></h3>
                <div class="flex gap-2">
                    <input type="number" step="0.01" name="min" value="<?= e($_GET['min'] ?? '') ?>" placeholder="<?= e(__('Min')) ?>" class="input input-sm w-full">
                    <input type="number" step="0.01" name="max" value="<?= e($_GET['max'] ?? '') ?>" placeholder="<?= e(__('Max')) ?>" class="input input-sm w-full">
                </div>
            </div>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="in_stock" value="1" class="checkbox checkbox-primary checkbox-sm" <?= ($_GET['in_stock'] ?? '') === '1' ? 'checked' : '' ?>>
                <span class="text-sm font-medium"><?= e(__('In stock only')) ?></span>
            </label>
            <input type="hidden" name="sort" value="<?= e($sort) ?>">
            <button class="btn btn-primary btn-sm w-full rounded-full"><?= icon('sliders-horizontal', 'size-4') ?> <?= e(__('Apply filters')) ?></button>
        </form>
    </aside>

    <div>
        <div class="flex items-center justify-end gap-2 mb-4">
            <span class="text-sm opacity-60"><?= e(__('Sort by')) ?></span>
            <select class="select select-sm w-auto" onchange="const u=new URL(location);u.searchParams.set('sort',this.value);u.searchParams.delete('page');location=u">
                <?php foreach (['newest' => __('Newest'), 'featured' => __('Featured'), 'price_asc' => __('Price: low to high'), 'price_desc' => __('Price: high to low'), 'name' => __('Name')] as $k => $label): ?>
                    <option value="<?= $k ?>" <?= $sort === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if ($page['items']): ?>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 md:gap-6">
                <?php foreach ($page['items'] as $p): ?><?= View::partial('partials/product_card', ['p' => $p]) ?><?php endforeach; ?>
            </div>
            <?= View::partial('partials/pagination', ['page' => $page]) ?>
        <?php else: ?>
            <div class="surface"><?= View::partial('partials/empty', ['icon' => 'search-x', 'title' => __('No products found'), 'text' => __('Try a different search or filter.')]) ?></div>
        <?php endif; ?>
    </div>
</div>
