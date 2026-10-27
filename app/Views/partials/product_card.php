<?php
/** @var array $p */
$out = $p['track_stock'] && (int) $p['stock'] <= 0;
$low = !$out && $p['track_stock'] && (int) $p['stock'] <= (int) $p['low_stock_threshold'];
$discount = $p['compare_price'] && $p['compare_price'] > $p['price'] ? (int) round(100 - $p['price'] / $p['compare_price'] * 100) : 0;
$href = url('/product/' . $p['slug']);
?>
<article class="group surface overflow-hidden card-lift flex flex-col">
    <a href="<?= e($href) ?>" class="relative block aspect-square overflow-hidden placeholder-img">
        <?php if ($p['image']): ?>
            <img src="<?= e(upload_url($p['image'])) ?>" alt="<?= e(tr($p, 'name')) ?>" loading="lazy" class="size-full object-cover transition duration-500 group-hover:scale-110">
        <?php else: ?>
            <span class="absolute inset-0 grid place-items-center text-primary/60"><?= icon('package', 'size-14 transition duration-500 group-hover:scale-110 group-hover:rotate-6') ?></span>
        <?php endif; ?>
        <div class="absolute top-3 start-3 flex flex-col gap-1.5">
            <?php if ($discount): ?><span class="badge badge-secondary font-bold shadow">-<?= $discount ?>%</span><?php endif; ?>
            <?php if ($p['is_featured']): ?><span class="badge badge-accent badge-sm shadow"><?= icon('sparkles', 'size-3') ?> <?= e(__('Featured')) ?></span><?php endif; ?>
        </div>
        <?php if ($out): ?>
            <span class="absolute inset-0 bg-base-100/60 backdrop-blur-[2px] grid place-items-center"><span class="badge badge-neutral badge-lg"><?= e(__('Out of stock')) ?></span></span>
        <?php endif; ?>
    </a>
    <div class="p-4 flex flex-col gap-2 flex-1">
        <a href="<?= e($href) ?>" class="font-semibold leading-snug line-clamp-2 hover:text-primary transition"><?= e(tr($p, 'name')) ?></a>
        <div class="flex items-center gap-2 text-xs">
            <?php if ($low): ?>
                <span class="text-warning font-semibold flex items-center gap-1"><?= icon('flame', 'size-3.5') ?><?= e(__('Only :qty left', ['qty' => $p['stock']])) ?></span>
            <?php elseif (!$out): ?>
                <span class="text-success flex items-center gap-1"><span class="live-dot size-1.5"></span><?= e(__('In stock')) ?></span>
            <?php endif; ?>
        </div>
        <div class="mt-auto flex items-end justify-between gap-2 pt-2">
            <div>
                <div class="text-lg font-extrabold"><?= e(money($p['price'])) ?></div>
                <?php if ($discount): ?><div class="text-xs line-through opacity-50"><?= e(money($p['compare_price'])) ?></div><?php endif; ?>
            </div>
            <?php if (!$out): ?>
                <form method="post" action="<?= e(url('/cart/add')) ?>" data-ajax-cart>
                    <?= csrf_field() ?>
                    <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
                    <button class="btn btn-primary btn-sm btn-circle shadow-lg shadow-primary/30 group-hover:scale-110 transition" title="<?= e(__('Add to cart')) ?>"><?= icon('plus', 'size-4') ?></button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</article>
