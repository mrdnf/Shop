<?php
use App\Core\View;
use App\Services\Delivery\DeliveryService;
?>
<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <?php foreach ([
        ['package', __('Orders'), $stats['orders'], 'bg-primary/10 text-primary'],
        ['truck', __('In progress'), $stats['active'], 'bg-secondary/10 text-secondary'],
        ['wallet', __('Total spent'), money($stats['spent']), 'bg-accent/10 text-accent'],
        ['map-pin', __('Addresses'), $stats['addresses'], 'bg-success/10 text-success'],
    ] as [$ic, $label, $value, $cls]): ?>
        <div class="surface p-5">
            <span class="kpi-icon <?= $cls ?>"><?= icon($ic) ?></span>
            <div class="mt-3 text-2xl font-black"><?= e($value) ?></div>
            <div class="text-sm opacity-60"><?= e($label) ?></div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($deliveries): ?>
    <h2 class="font-bold text-lg mt-8 mb-3 flex items-center gap-2"><span class="live-dot"></span><?= e(__('On the way')) ?></h2>
    <div class="grid md:grid-cols-2 gap-4">
        <?php foreach ($deliveries as $d): ?>
            <a href="<?= e(url('/track/' . rawurlencode($d['tracking_number']))) ?>" class="surface p-5 card-lift block">
                <div class="flex items-center justify-between">
                    <span class="font-mono font-bold"><?= e($d['tracking_number']) ?></span>
                    <?= status_badge($d['status']) ?>
                </div>
                <div class="text-xs opacity-60 mt-1"><?= e(__('Order')) ?> <?= e($d['number']) ?></div>
                <progress class="progress progress-primary w-full mt-4" value="<?= DeliveryService::progress($d['status']) ?>" max="100"></progress>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="flex items-center justify-between mt-8 mb-3">
    <h2 class="font-bold text-lg"><?= e(__('Recent orders')) ?></h2>
    <a href="<?= e(url('/account/orders')) ?>" class="link link-primary text-sm"><?= e(__('View all')) ?></a>
</div>
<div class="surface overflow-hidden">
    <?php if ($orders): ?>
        <?= View::partial('shop/account/_orders_table', ['orders' => $orders]) ?>
    <?php else: ?>
        <?= View::partial('partials/empty', ['icon' => 'package', 'title' => __('No orders yet'), 'action' => '<a class="btn btn-primary btn-sm rounded-full" href="' . e(url('/products')) . '">' . e(__('Start shopping')) . '</a>']) ?>
    <?php endif; ?>
</div>
