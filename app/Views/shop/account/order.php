<?php
use App\Core\View;

$orderFlow = ['pending', 'confirmed', 'processing', 'shipped', 'delivered'];
$idx = array_search($order['status'], $orderFlow, true);
?>
<div class="flex flex-wrap items-center justify-between gap-3">
    <div>
        <a href="<?= e(url('/account/orders')) ?>" class="link text-sm opacity-60"><?= icon('arrow-left', 'size-3 inline flip-rtl') ?> <?= e(__('My orders')) ?></a>
        <h2 class="text-2xl font-black font-mono mt-1"><?= e($order['number']) ?></h2>
        <div class="text-sm opacity-60"><?= e(format_date($order['created_at'], true)) ?></div>
    </div>
    <div class="flex gap-2"><?= status_badge($order['status']) ?> <?= status_badge($order['payment_status']) ?></div>
</div>

<?php if ($order['status'] !== 'cancelled'): ?>
<div class="surface p-6 mt-6 overflow-x-auto">
    <ul class="steps w-full">
        <?php foreach ($orderFlow as $i => $s): ?>
            <li class="step <?= $idx !== false && $i <= $idx ? 'step-primary' : '' ?>"><span class="text-xs"><?= e(__('status.' . $s)) ?></span></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php foreach ($shipments as $s): ?>
    <div class="surface p-6 mt-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="kpi-icon bg-primary/10 text-primary"><?= icon('truck') ?></span>
                <div><div class="text-xs opacity-60"><?= e(__('Tracking number')) ?></div><div class="font-mono font-bold"><?= e($s['tracking_number']) ?></div></div>
            </div>
            <div class="flex gap-2">
                <a href="<?= e(url('/track/' . rawurlencode($s['tracking_number']))) ?>" class="btn btn-primary btn-sm rounded-full"><?= icon('map-pinned', 'size-4') ?> <?= e(__('Live tracking')) ?></a>
                <?php if ($s['external_url']): ?><a href="<?= e($s['external_url']) ?>" target="_blank" rel="noopener" class="btn btn-sm rounded-full"><?= icon('external-link', 'size-4') ?></a><?php endif; ?>
            </div>
        </div>
        <div class="mt-6"><?= View::partial('partials/stepper', ['status' => $s['status']]) ?></div>
        <div class="mt-6"><?= View::partial('partials/timeline', ['events' => array_slice($s['events'], 0, 4)]) ?></div>
    </div>
<?php endforeach; ?>

<div class="grid md:grid-cols-[1fr_18rem] gap-6 mt-6">
    <div class="surface overflow-hidden">
        <div class="p-5 font-bold"><?= e(__('Items')) ?></div>
        <ul class="divide-y divide-base-content/5">
            <?php foreach ($items as $item): ?>
                <li class="flex items-center gap-4 px-5 py-3">
                    <span class="size-12 rounded-lg placeholder-img grid place-items-center overflow-hidden shrink-0">
                        <?php if ($item['image']): ?><img src="<?= e(upload_url($item['image'])) ?>" class="size-full object-cover" alt=""><?php else: ?><?= icon('package', 'size-5 text-primary/60') ?><?php endif; ?>
                    </span>
                    <div class="flex-1"><div class="font-medium"><?= e($item['name']) ?></div><div class="text-xs opacity-60"><?= e(money($item['price'])) ?> × <?= (int) $item['qty'] ?></div></div>
                    <div class="font-semibold"><?= e(money($item['total'])) ?></div>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="p-5 space-y-1 text-sm border-t border-base-content/5">
            <div class="flex justify-between"><span class="opacity-60"><?= e(__('Subtotal')) ?></span><span><?= e(money($order['subtotal'])) ?></span></div>
            <div class="flex justify-between"><span class="opacity-60"><?= e(__('Shipping')) ?></span><span><?= e(money($order['shipping'])) ?></span></div>
            <?php if ((float) $order['tax'] > 0): ?><div class="flex justify-between"><span class="opacity-60"><?= e(__('Tax')) ?></span><span><?= e(money($order['tax'])) ?></span></div><?php endif; ?>
            <div class="flex justify-between text-lg font-black pt-2"><span><?= e(__('Total')) ?></span><span class="text-brand"><?= e(money($order['total'])) ?></span></div>
        </div>
    </div>
    <div class="space-y-4">
        <div class="surface p-5 text-sm">
            <div class="font-bold mb-2 flex items-center gap-2"><?= icon('map-pin', 'size-4 text-primary') ?><?= e(__('Delivery address')) ?></div>
            <div><?= e($order['customer_name']) ?></div>
            <div class="opacity-70"><?= e($order['street']) ?><?= $order['building'] ? ', ' . e($order['building']) : '' ?></div>
            <div class="opacity-70"><?= e(trim($order['city'] . ($order['area'] ? ', ' . $order['area'] : ''))) ?></div>
            <div class="opacity-70" dir="ltr"><?= e($order['phone']) ?></div>
        </div>
        <div class="surface p-5 text-sm">
            <div class="font-bold mb-3"><?= e(__('Order history')) ?></div>
            <ul class="space-y-2">
                <?php foreach ($history as $h): ?>
                    <li class="flex justify-between gap-2"><span><?= e(__('status.' . $h['status'])) ?></span><span class="opacity-50 text-xs"><?= e(format_date($h['created_at'], true)) ?></span></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
