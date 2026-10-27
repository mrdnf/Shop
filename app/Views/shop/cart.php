<?php use App\Core\View; ?>
<div class="max-w-6xl mx-auto px-4 py-10">
    <h1 class="text-3xl font-black flex items-center gap-3"><?= icon('shopping-bag', 'size-8 text-primary') ?><?= e(__('Your cart')) ?></h1>

    <?php if (!$cart['lines']): ?>
        <div class="surface mt-8"><?= View::partial('partials/empty', [
            'icon' => 'shopping-bag', 'title' => __('Your cart is empty'), 'text' => __('Discover products you will love.'),
            'action' => '<a href="' . e(url('/products')) . '" class="btn btn-primary rounded-full">' . e(__('Start shopping')) . '</a>',
        ]) ?></div>
    <?php else: ?>
        <div class="grid lg:grid-cols-[1fr_22rem] gap-8 mt-8">
            <form method="post" action="<?= e(url('/cart/update')) ?>" class="surface divide-y divide-base-content/5">
                <?= csrf_field() ?>
                <?php foreach ($cart['lines'] as $line): $p = $line['product']; ?>
                    <div class="flex gap-4 p-4 items-center">
                        <a href="<?= e(url('/product/' . $p['slug'])) ?>" class="size-20 rounded-xl overflow-hidden placeholder-img shrink-0 grid place-items-center">
                            <?php if ($p['image']): ?><img src="<?= e(upload_url($p['image'])) ?>" class="size-full object-cover" alt=""><?php else: ?><?= icon('package', 'size-8 text-primary/50') ?><?php endif; ?>
                        </a>
                        <div class="flex-1 min-w-0">
                            <a href="<?= e(url('/product/' . $p['slug'])) ?>" class="font-semibold hover:text-primary line-clamp-1"><?= e(tr($p, 'name')) ?></a>
                            <div class="text-sm opacity-60"><?= e(money($p['price'])) ?></div>
                        </div>
                        <input type="number" name="qty[<?= (int) $p['id'] ?>]" value="<?= (int) $line['qty'] ?>" min="0" max="999" class="input input-sm w-20 text-center" onchange="this.form.submit()">
                        <div class="w-24 text-end font-bold"><?= e(money($line['total'])) ?></div>
                        <button form="remove-<?= (int) $p['id'] ?>" class="btn btn-ghost btn-sm btn-circle text-error" title="<?= e(__('Remove')) ?>"><?= icon('trash-2', 'size-4') ?></button>
                    </div>
                <?php endforeach; ?>
                <div class="p-4 flex justify-between">
                    <a href="<?= e(url('/products')) ?>" class="btn btn-ghost btn-sm rounded-full"><?= icon('arrow-left', 'size-4 flip-rtl') ?> <?= e(__('Continue shopping')) ?></a>
                    <button class="btn btn-sm rounded-full"><?= icon('refresh-cw', 'size-4') ?> <?= e(__('Update cart')) ?></button>
                </div>
            </form>
            <?php foreach ($cart['lines'] as $line): ?>
                <form id="remove-<?= (int) $line['product']['id'] ?>" method="post" action="<?= e(url('/cart/remove')) ?>" class="hidden">
                    <?= csrf_field() ?><input type="hidden" name="product_id" value="<?= (int) $line['product']['id'] ?>">
                </form>
            <?php endforeach; ?>

            <aside class="surface p-6 h-fit lg:sticky lg:top-24">
                <h2 class="font-bold text-lg"><?= e(__('Order summary')) ?></h2>
                <div class="flex justify-between mt-4"><span class="opacity-70"><?= e(__('Subtotal')) ?> (<?= (int) $cart['count'] ?>)</span><span class="font-semibold"><?= e(money($cart['subtotal'])) ?></span></div>
                <div class="flex justify-between mt-2 text-sm opacity-60"><span><?= e(__('Shipping')) ?></span><span><?= e(__('Calculated at checkout')) ?></span></div>
                <div class="divider my-3"></div>
                <div class="flex justify-between text-xl font-black"><span><?= e(__('Total')) ?></span><span class="text-brand"><?= e(money($cart['subtotal'])) ?></span></div>
                <a href="<?= e(url('/checkout')) ?>" class="btn btn-primary w-full rounded-full mt-6 shadow-xl shadow-primary/30"><?= icon('lock', 'size-4') ?> <?= e(__('Checkout')) ?></a>
            </aside>
        </div>
    <?php endif; ?>
</div>
