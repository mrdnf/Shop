<?php
use App\Core\View;
use App\Services\Delivery\DeliveryService;
?>
<div class="grid grid-cols-2 gap-4 mb-6">
    <div class="surface p-5 bg-brand"><div class="text-3xl font-black"><?= count($shipments) ?></div><div class="text-sm opacity-90"><?= e(__('Active deliveries')) ?></div></div>
    <div class="surface p-5"><div class="text-3xl font-black text-success"><?= (int) $doneToday ?></div><div class="text-sm opacity-60"><?= e(__('Delivered today')) ?></div></div>
</div>

<?php if (!$shipments): ?>
    <div class="surface"><?= View::partial('partials/empty', ['icon' => 'coffee', 'title' => __('All caught up!'), 'text' => __('No deliveries assigned to you right now.')]) ?></div>
<?php endif; ?>

<div class="grid md:grid-cols-2 gap-4">
    <?php foreach ($shipments as $s): ?>
        <a href="<?= e(url('/driver/shipments/' . $s['id'])) ?>" class="surface p-5 card-lift block">
            <div class="flex items-center justify-between gap-3">
                <span class="kpi-icon bg-primary/10 text-primary"><?= icon(DeliveryService::ICONS[$s['status']] ?? 'truck') ?></span>
                <div class="flex-1 min-w-0">
                    <div class="font-bold truncate"><?= e($s['customer_name']) ?></div>
                    <div class="text-xs opacity-60 font-mono"><?= e($s['tracking_number']) ?></div>
                </div>
                <?= status_badge($s['status']) ?>
            </div>
            <div class="mt-4 text-sm flex items-start gap-2"><?= icon('map-pin', 'size-4 text-secondary shrink-0 mt-0.5') ?><span><?= e($s['street']) ?><?= $s['building'] ? ', ' . e($s['building']) : '' ?> — <?= e(trim($s['city'] . ($s['area'] ? ', ' . $s['area'] : ''))) ?></span></div>
            <div class="mt-3 flex items-center justify-between text-sm">
                <span class="flex items-center gap-1 opacity-70"><?= icon('phone', 'size-4') ?><span dir="ltr"><?= e($s['phone']) ?></span></span>
                <?php if ($s['payment_method'] === 'cod' && $s['payment_status'] !== 'paid'): ?>
                    <span class="badge badge-warning font-bold"><?= icon('banknote', 'size-3') ?> <?= e(__('Collect')) ?> <?= e(money($s['total'])) ?></span>
                <?php else: ?>
                    <span class="badge badge-success badge-soft"><?= e(__('Paid')) ?></span>
                <?php endif; ?>
            </div>
        </a>
    <?php endforeach; ?>
</div>
