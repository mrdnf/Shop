<?php
use App\Core\View;

$s = $shipment;
$next = match ($s['status']) {
    'pending', 'ready' => ['picked_up', __('Picked up from store'), 'package-open'],
    'picked_up' => ['in_transit', __('Start trip'), 'truck'],
    'in_transit' => ['out_for_delivery', __('Arriving / out for delivery'), 'map-pin'],
    'out_for_delivery', 'failed' => ['delivered', __('Mark as delivered'), 'circle-check-big'],
    default => null,
};
$active = in_array($s['status'], ['picked_up', 'in_transit', 'out_for_delivery'], true);
$mapsUrl = $s['dest_lat'] !== null
    ? 'https://www.google.com/maps/dir/?api=1&destination=' . $s['dest_lat'] . ',' . $s['dest_lng']
    : 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($s['street'] . ' ' . $s['building'] . ' ' . $s['area'] . ' ' . $s['city']);
?>
<div class="max-w-xl mx-auto space-y-4" x-data="gps(<?= $active ? 'true' : 'false' ?>)">
    <a href="<?= e(url('/driver')) ?>" class="btn btn-ghost btn-sm rounded-full"><?= icon('arrow-left', 'size-4 flip-rtl') ?> <?= e(__('My deliveries')) ?></a>

    <div class="surface p-5">
        <div class="flex items-center justify-between">
            <div><div class="text-xs opacity-60"><?= e(__('Order')) ?> <?= e($s['number']) ?></div><div class="font-mono font-bold"><?= e($s['tracking_number']) ?></div></div>
            <?= status_badge($s['status']) ?>
        </div>
        <div class="mt-5"><?= View::partial('partials/stepper', ['status' => $s['status']]) ?></div>
    </div>

    <div class="surface p-5 space-y-3">
        <div class="flex items-center gap-3">
            <span class="avatar avatar-placeholder"><span class="w-12 rounded-full bg-brand font-bold"><?= e(initials($s['customer_name'])) ?></span></span>
            <div class="flex-1"><div class="font-bold"><?= e($s['customer_name']) ?></div><div class="text-sm opacity-60" dir="ltr"><?= e($s['phone']) ?></div></div>
            <a href="tel:<?= e($s['phone']) ?>" class="btn btn-success btn-circle"><?= icon('phone') ?></a>
            <a href="https://wa.me/<?= e(preg_replace('/\D/', '', (string) $s['phone'])) ?>" target="_blank" rel="noopener" class="btn btn-circle"><?= icon('message-circle') ?></a>
        </div>
        <div class="rounded-box bg-base-200 p-4 text-sm">
            <div class="font-semibold flex items-center gap-2"><?= icon('map-pin', 'size-4 text-secondary') ?><?= e($s['street']) ?><?= $s['building'] ? ', ' . e($s['building']) : '' ?></div>
            <div class="opacity-70 ms-6"><?= e(trim($s['city'] . ($s['area'] ? ', ' . $s['area'] : ''))) ?></div>
            <?php if ($s['address_notes']): ?><div class="ms-6 mt-1 italic opacity-80"><?= e($s['address_notes']) ?></div><?php endif; ?>
        </div>
        <a href="<?= e($mapsUrl) ?>" target="_blank" rel="noopener" class="btn btn-block rounded-full"><?= icon('navigation', 'size-4') ?> <?= e(__('Navigate')) ?></a>
        <?php if ($s['payment_method'] === 'cod' && $s['payment_status'] !== 'paid'): ?>
            <div class="alert alert-warning"><?= icon('banknote', 'size-5') ?><span class="font-bold"><?= e(__('Collect cash')) ?>: <?= e(money($s['total'])) ?></span></div>
        <?php endif; ?>
    </div>

    <?php if ($active): ?>
        <div class="surface p-4 flex items-center gap-3">
            <span class="live-dot" x-show="sharing"></span>
            <span class="flex-1 text-sm" x-text="sharing ? <?= e(js(__('Sharing live location with the customer'))) ?> : <?= e(js(__('Location sharing is off'))) ?>"></span>
            <span class="text-xs opacity-50" x-text="last"></span>
        </div>
    <?php endif; ?>

    <?php if ($next): ?>
        <form method="post" action="<?= e(url('/driver/shipments/' . $s['id'] . '/event')) ?>" @submit="attach($event)">
            <?= csrf_field() ?>
            <input type="hidden" name="status" value="<?= e($next[0]) ?>">
            <input type="hidden" name="lat"><input type="hidden" name="lng">
            <button class="btn btn-primary btn-lg btn-block rounded-full shadow-xl shadow-primary/30"><?= icon($next[2], 'size-5') ?> <?= e($next[1]) ?></button>
        </form>
    <?php endif; ?>

    <?php if (!in_array($s['status'], ['delivered', 'returned', 'cancelled'], true)): ?>
        <details class="collapse collapse-arrow surface">
            <summary class="collapse-title font-semibold"><?= icon('triangle-alert', 'size-4 inline text-warning') ?> <?= e(__('Report a problem')) ?></summary>
            <div class="collapse-content">
                <form method="post" action="<?= e(url('/driver/shipments/' . $s['id'] . '/event')) ?>" class="space-y-3" @submit="attach($event)">
                    <?= csrf_field() ?>
                    <input type="hidden" name="lat"><input type="hidden" name="lng">
                    <select name="status" class="select w-full">
                        <option value="failed"><?= e(__('Delivery attempt failed')) ?></option>
                        <option value="returned"><?= e(__('Returned to store')) ?></option>
                    </select>
                    <input name="note" class="input w-full" placeholder="<?= e(__('What happened? (customer not home, wrong address…)')) ?>" required>
                    <button class="btn btn-warning btn-block rounded-full"><?= e(__('Submit')) ?></button>
                </form>
            </div>
        </details>
    <?php endif; ?>

    <div class="surface p-5">
        <h2 class="font-bold mb-3"><?= e(__('Items')) ?></h2>
        <ul class="text-sm space-y-1"><?php foreach ($items as $i): ?><li class="flex justify-between"><span><?= e($i['name']) ?></span><span class="font-bold">× <?= (int) $i['qty'] ?></span></li><?php endforeach; ?></ul>
    </div>
    <div class="surface p-5"><h2 class="font-bold mb-4"><?= e(__('Tracking history')) ?></h2><?= View::partial('partials/timeline', ['events' => $events]) ?></div>
</div>

<?php push_start(); ?>
<script>
function gps(active) {
    return {
        sharing: false, last: '', pos: null, timer: null,
        init() {
            if (!active || !navigator.geolocation) return;
            navigator.geolocation.watchPosition(p => { this.pos = p.coords; this.sharing = true; }, () => { this.sharing = false; }, { enableHighAccuracy: true, maximumAge: 10000 });
            this.timer = setInterval(() => this.send(), 15000);
            setTimeout(() => this.send(), 3000);
        },
        async send() {
            if (!this.pos) return;
            const fd = new FormData();
            fd.append('_token', document.querySelector('meta[name=csrf-token]').content);
            fd.append('lat', this.pos.latitude); fd.append('lng', this.pos.longitude);
            try {
                const r = await fetch(<?= js(url('/driver/shipments/' . $s['id'] . '/location')) ?>, { method: 'POST', body: fd, headers: { Accept: 'application/json' } });
                if (r.ok) this.last = new Date().toLocaleTimeString();
            } catch (e) {}
        },
        attach(e) {
            if (!this.pos) return;
            e.target.querySelector('[name=lat]').value = this.pos.latitude;
            e.target.querySelector('[name=lng]').value = this.pos.longitude;
        },
    };
}
</script>
<?php push_end('scripts'); ?>
