<div class="max-w-2xl mx-auto px-4 py-16 text-center">
    <div class="relative mx-auto size-24">
        <span class="absolute inset-0 rounded-full bg-success/20 animate-ping"></span>
        <span class="relative size-24 rounded-full bg-success text-success-content grid place-items-center shadow-xl shadow-success/30"><?= icon('check', 'size-12') ?></span>
    </div>
    <h1 class="text-4xl font-black mt-8"><?= e(__('Thank you!')) ?></h1>
    <p class="opacity-70 mt-2 text-lg"><?= e(__('Your order has been placed successfully.')) ?></p>

    <div class="surface p-6 mt-8 text-start">
        <div class="flex flex-wrap justify-between gap-4">
            <div><div class="text-xs opacity-60"><?= e(__('Order number')) ?></div><div class="font-mono font-bold text-lg"><?= e($order['number']) ?></div></div>
            <div><div class="text-xs opacity-60"><?= e(__('Total')) ?></div><div class="font-bold text-lg text-brand"><?= e(money($order['total'])) ?></div></div>
            <div><div class="text-xs opacity-60"><?= e(__('Payment')) ?></div><div class="font-semibold"><?= e(__('payment.' . $order['payment_method'])) ?></div></div>
        </div>
        <div class="divider"></div>
        <ul class="space-y-2 text-sm">
            <?php foreach ($items as $item): ?>
                <li class="flex justify-between"><span><?= e($item['name']) ?> × <?= (int) $item['qty'] ?></span><span class="font-semibold"><?= e(money($item['total'])) ?></span></li>
            <?php endforeach; ?>
        </ul>
        <?php if ($bankDetails): ?>
            <div class="alert alert-info alert-soft mt-6 items-start">
                <?= icon('landmark', 'size-5') ?>
                <div><div class="font-bold"><?= e(__('Bank transfer details')) ?></div><div class="whitespace-pre-line text-sm mt-1"><?= e($bankDetails) ?></div></div>
            </div>
        <?php endif; ?>
        <div class="alert alert-soft mt-6">
            <?= icon('truck', 'size-5 text-primary') ?>
            <span><?= e(__('You will get a tracking number as soon as your order is shipped.')) ?></span>
        </div>
    </div>

    <div class="mt-8 flex flex-wrap justify-center gap-3">
        <?php if (auth()): ?>
            <a href="<?= e(url('/account/orders/' . $order['number'])) ?>" class="btn btn-primary rounded-full"><?= icon('package', 'size-4') ?> <?= e(__('View order')) ?></a>
        <?php endif; ?>
        <a href="<?= e(url('/products')) ?>" class="btn rounded-full"><?= e(__('Continue shopping')) ?></a>
    </div>
</div>
