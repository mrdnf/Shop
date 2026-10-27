<?php
use App\Core\View;
use App\Services\Delivery\DeliveryService;

$s = $shipment;
$hasMap = $s['last_lat'] !== null || $order['lat'] !== null;
if ($hasMap) {
    View::push('head', '<link rel="stylesheet" href="' . asset('vendor/leaflet/leaflet.css') . '"><script src="' . asset('vendor/leaflet/leaflet.js') . '"></script>');
}
?>
<div class="flex flex-wrap items-center gap-3 mb-6">
    <a href="<?= e(url('/admin/orders/' . $order['id'])) ?>" class="btn btn-ghost btn-sm rounded-full"><?= icon('arrow-left', 'size-4 flip-rtl') ?> <?= e($order['number']) ?></a>
    <?= status_badge($s['status']) ?>
    <div class="ms-auto flex gap-2">
        <a href="<?= e(url('/track/' . rawurlencode($s['tracking_number']))) ?>" target="_blank" class="btn btn-sm rounded-full"><?= icon('eye', 'size-4') ?> <?= e(__('Customer view')) ?></a>
        <?php if ($externalUrl): ?><a href="<?= e($externalUrl) ?>" target="_blank" rel="noopener" class="btn btn-sm rounded-full"><?= icon('external-link', 'size-4') ?> <?= e(__('Courier site')) ?></a><?php endif; ?>
        <?php if ($s['label_url']): ?><a href="<?= e($s['label_url']) ?>" target="_blank" rel="noopener" class="btn btn-sm rounded-full"><?= icon('tag', 'size-4') ?> <?= e(__('Label')) ?></a><?php endif; ?>
        <?php if ($s['courier_driver'] === 'api'): ?>
            <form method="post" action="<?= e(url('/admin/shipments/' . $s['id'] . '/sync')) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-primary rounded-full"><?= icon('refresh-cw', 'size-4') ?> <?= e(__('Pull tracking')) ?></button></form>
        <?php endif; ?>
    </div>
</div>

<div class="surface p-6 overflow-x-auto"><?= View::partial('partials/stepper', ['status' => $s['status']]) ?></div>

<div class="grid xl:grid-cols-[1fr_22rem] gap-6 mt-6">
    <div class="space-y-6">
        <?php if ($hasMap): ?>
            <div class="surface p-2"><div id="map" class="h-80 w-full"></div></div>
        <?php endif; ?>
        <section class="surface p-6">
            <h2 class="font-bold mb-5"><?= e(__('Tracking history')) ?></h2>
            <?= View::partial('partials/timeline', ['events' => $events, 'showSource' => true]) ?>
        </section>
    </div>
    <aside class="space-y-4">
        <form method="post" action="<?= e(url('/admin/shipments/' . $s['id'] . '/event')) ?>" class="surface p-5 space-y-3">
            <?= csrf_field() ?>
            <h2 class="font-bold"><?= e(__('Add tracking update')) ?></h2>
            <select name="status" class="select w-full">
                <?php foreach (DeliveryService::statuses() as $st): ?><option value="<?= $st ?>" <?= $st === $s['status'] ? 'selected' : '' ?>><?= e(__('status.' . $st)) ?></option><?php endforeach; ?>
            </select>
            <input name="location" class="input w-full" placeholder="<?= e(__('Location (optional)')) ?>">
            <input name="note" class="input w-full" placeholder="<?= e(__('Note for the customer (optional)')) ?>">
            <button class="btn btn-primary btn-sm w-full rounded-full"><?= icon('send', 'size-4') ?> <?= e(__('Publish update')) ?></button>
        </form>
        <form method="post" action="<?= e(url('/admin/shipments/' . $s['id'] . '/driver')) ?>" class="surface p-5 space-y-3">
            <?= csrf_field() ?>
            <h2 class="font-bold"><?= e(__('Driver')) ?></h2>
            <select name="driver_id" class="select w-full">
                <option value=""><?= e(__('Unassigned')) ?></option>
                <?php foreach ($drivers as $d): ?><option value="<?= (int) $d['id'] ?>" <?= (int) $s['driver_id'] === (int) $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option><?php endforeach; ?>
            </select>
            <button class="btn btn-sm w-full rounded-full"><?= e(__('Assign')) ?></button>
            <?php if (!$drivers): ?><p class="text-xs opacity-60"><?= e(__('Add drivers from Team.')) ?></p><?php endif; ?>
        </form>
        <div class="surface p-5 text-sm space-y-2">
            <div class="flex justify-between"><span class="opacity-60"><?= e(__('Courier')) ?></span><span class="font-semibold"><?= e($s['courier_name'] ?? '—') ?></span></div>
            <div class="flex justify-between"><span class="opacity-60"><?= e(__('Expected delivery')) ?></span><span><?= e(format_date($s['eta'])) ?></span></div>
            <div class="flex justify-between"><span class="opacity-60"><?= e(__('Last GPS')) ?></span><span><?= e(format_date($s['last_location_at'], true)) ?></span></div>
            <?php if ($s['external_id']): ?><div class="flex justify-between"><span class="opacity-60"><?= e(__('External ID')) ?></span><span class="font-mono"><?= e($s['external_id']) ?></span></div><?php endif; ?>
            <div class="divider my-1"></div>
            <div class="font-semibold"><?= e($order['customer_name']) ?></div>
            <div class="opacity-70"><?= e($order['street']) ?>, <?= e($order['city']) ?></div>
            <a class="link" href="tel:<?= e($order['phone']) ?>" dir="ltr"><?= e($order['phone']) ?></a>
        </div>
    </aside>
</div>
<?php if ($hasMap): push_start(); ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const dest = <?= js($order['lat'] !== null ? [(float) $order['lat'], (float) $order['lng']] : null) ?>;
    const drv = <?= js($s['last_lat'] !== null ? [(float) $s['last_lat'], (float) $s['last_lng']] : null) ?>;
    const map = L.map('map').setView(drv || dest, 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map);
    const pin = (cls, ic) => L.divIcon({ className: '', html: `<span class="grid place-items-center size-10 rounded-full ${cls} shadow-xl ring-4 ring-base-100"><i data-lucide="${ic}" class="size-5"></i></span>`, iconSize: [40, 40], iconAnchor: [20, 20] });
    if (dest) L.marker(dest, { icon: pin('bg-secondary text-secondary-content', 'house') }).addTo(map);
    if (drv) L.marker(drv, { icon: pin('bg-primary text-primary-content', 'truck') }).addTo(map);
    if (dest && drv) map.fitBounds([dest, drv], { padding: [50, 50] });
    setTimeout(() => window.matjar.icons(), 50);
});
</script>
<?php push_end('scripts'); endif; ?>
