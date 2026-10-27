<?php
use App\Core\View;

$tracks = (int) $product['track_stock'] === 1;
$out = $tracks && $available <= 0;
$discount = $product['compare_price'] && $product['compare_price'] > $product['price'] ? (int) round(100 - $product['price'] / $product['compare_price'] * 100) : 0;
$maxQty = $tracks ? max(1, min(99, $available)) : 99;
?>
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="breadcrumbs text-sm opacity-70 mb-4">
        <ul>
            <li><a href="<?= e(url('/')) ?>"><?= e(__('Home')) ?></a></li>
            <?php if ($category): ?><li><a href="<?= e(url('/category/' . $category['slug'])) ?>"><?= e(tr($category, 'name')) ?></a></li><?php endif; ?>
            <li class="truncate max-w-48"><?= e(tr($product, 'name')) ?></li>
        </ul>
    </div>

    <div class="grid lg:grid-cols-2 gap-10">
        <div class="relative surface overflow-hidden aspect-square placeholder-img animate-fade-up">
            <?php if ($product['image']): ?>
                <img src="<?= e(upload_url($product['image'])) ?>" alt="<?= e(tr($product, 'name')) ?>" class="size-full object-cover">
            <?php else: ?>
                <span class="absolute inset-0 grid place-items-center text-primary/50"><?= icon('package', 'size-32') ?></span>
            <?php endif; ?>
            <?php if ($discount): ?><span class="absolute top-5 start-5 badge badge-secondary badge-lg font-bold shadow-lg">-<?= $discount ?>%</span><?php endif; ?>
        </div>

        <div class="animate-fade-up" style="animation-delay:.1s">
            <?php if ($category): ?><a href="<?= e(url('/category/' . $category['slug'])) ?>" class="badge badge-primary badge-soft"><?= e(tr($category, 'name')) ?></a><?php endif; ?>
            <h1 class="text-3xl md:text-4xl font-black mt-3 leading-tight"><?= e(tr($product, 'name')) ?></h1>
            <p class="text-sm opacity-50 mt-1 font-mono">SKU: <?= e($product['sku']) ?></p>

            <div class="flex items-end gap-3 mt-6">
                <span class="text-4xl font-black text-brand"><?= e(money($product['price'])) ?></span>
                <?php if ($discount): ?><span class="text-lg line-through opacity-40 mb-1"><?= e(money($product['compare_price'])) ?></span><?php endif; ?>
            </div>

            <div class="mt-5 surface p-4 flex items-center gap-3 bg-base-100/60">
                <?php if ($out): ?>
                    <span class="size-10 rounded-xl bg-error/15 text-error grid place-items-center"><?= icon('package-x') ?></span>
                    <div><div class="font-bold text-error"><?= e(__('Out of stock')) ?></div><div class="text-xs opacity-60"><?= e(__('Check back soon, stock is synced automatically.')) ?></div></div>
                <?php else: ?>
                    <span class="size-10 rounded-xl bg-success/15 text-success grid place-items-center"><?= icon('package-check') ?></span>
                    <div>
                        <div class="font-bold text-success flex items-center gap-2"><span class="live-dot"></span>
                            <?= $tracks ? e(__(':qty available', ['qty' => $available])) : e(__('In stock')) ?></div>
                        <div class="text-xs opacity-60">
                            <?= $product['stock_synced_at'] ? e(__('Live balance, updated :time', ['time' => format_date($product['stock_synced_at'], true)])) : e(__('Ready to ship')) ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!$out): ?>
                <form method="post" action="<?= e(url('/cart/add')) ?>" data-ajax-cart class="mt-6 flex gap-3" x-data="{ qty: 1 }">
                    <?= csrf_field() ?>
                    <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                    <div class="join rounded-full border border-base-content/10">
                        <button type="button" class="btn btn-ghost join-item rounded-s-full" @click="qty = Math.max(1, qty - 1)"><?= icon('minus', 'size-4') ?></button>
                        <input name="qty" x-model.number="qty" type="number" min="1" max="<?= $maxQty ?>" class="input join-item w-16 text-center border-0 focus:outline-none">
                        <button type="button" class="btn btn-ghost join-item rounded-e-full" @click="qty = Math.min(<?= $maxQty ?>, qty + 1)"><?= icon('plus', 'size-4') ?></button>
                    </div>
                    <button class="btn btn-primary flex-1 rounded-full shadow-xl shadow-primary/30"><?= icon('shopping-bag', 'size-5') ?> <?= e(__('Add to cart')) ?></button>
                </form>
            <?php endif; ?>

            <div class="grid grid-cols-3 gap-3 mt-6">
                <?php foreach ([['truck', __('Fast delivery')], ['map-pinned', __('Live tracking')], ['shield-check', __('Secure checkout')]] as [$ic, $label]): ?>
                    <div class="surface p-3 text-center text-xs font-semibold"><span class="text-primary inline-block mb-1"><?= icon($ic) ?></span><div><?= e($label) ?></div></div>
                <?php endforeach; ?>
            </div>

            <?php if (tr($product, 'description')): ?>
                <div class="mt-8">
                    <h2 class="font-bold text-lg mb-2"><?= e(__('Description')) ?></h2>
                    <div class="opacity-80 leading-relaxed whitespace-pre-line"><?= e(tr($product, 'description')) ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($related): ?>
        <section class="mt-16">
            <h2 class="section-title mb-6"><?= e(__('You may also like')) ?></h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
                <?php foreach ($related as $p): ?><?= View::partial('partials/product_card', ['p' => $p]) ?><?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>
