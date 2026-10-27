<?php
use App\Core\View;
use App\Services\Delivery\DeliveryService;

$s = $shipment;
$hasMap = $s['last_lat'] !== null || $order['lat'] !== null;
View::push('head', '<link rel="stylesheet" href="' . asset('vendor/leaflet/leaflet.css') . '"><script src="' . asset('vendor/leaflet/leaflet.js') . '"></script>');
?>
<div class="max-w-6xl mx-auto px-4 py-10" x-data="tracker()">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="text-sm opacity-60"><?= e(__('Tracking number')) ?></div>
            <h1 class="text-3xl font-black font-mono" dir="ltr"><?= e($s['tracking_number']) ?></h1>
        </div>
        <div class="flex items-center gap-2 text-sm">
            <span class="live-dot"></span><span class="opacity-70"><?= e(__('Live · refreshes automatically')) ?></span>
        </div>
    </div>

    <div class="mt-6 relative overflow-hidden rounded-box bg-brand p-6 md:p-8 shadow-2xl shadow-primary/20">
        <div class="blob size-72 bg-white/30 -top-24 -end-10"></div>
        <div class="relative flex flex-wrap items-center gap-6">
            <span class="size-16 rounded-2xl bg-white/20 grid place-items-center"><?= icon(DeliveryService::ICONS[$s['status']] ?? 'truck', 'size-8') ?></span>
            <div class="flex-1 min-w-48">
                <div class="text-sm opacity-80"><?= e(__('Current status')) ?></div>
                <div class="text-3xl font-black" x-text="label"><?= e(__('status.' . $s['status'])) ?></div>
                <?php if ($s['eta'] && $s['status'] !== 'delivered'): ?><div class="opacity-90 mt-1"><?= icon('calendar-clock', 'size-4 inline') ?> <?= e(__('Expected by :date', ['date' => format_date($s['eta'])])) ?></div><?php endif; ?>
            </div>
            <div class="w-full md:w-72">
                <div class="flex justify-between text-sm mb-1 opacity-90"><span><?= e(__('Progress')) ?></span><span x-text="progress + '%'"><?= DeliveryService::progress($s['status']) ?>%</span></div>
                <div class="h-3 rounded-full bg-white/25 overflow-hidden"><div class="h-full bg-white rounded-full transition-all duration-700" :style="'width:' + progress + '%'" style="width: <?= DeliveryService::progress($s['status']) ?>%"></div></div>
            </div>
        </div>
    </div>

    <div class="surface p-6 mt-6 overflow-x-auto"><?= View::partial('partials/stepper', ['status' => $s['status']]) ?></div>

    <div class="grid lg:grid-cols-[1fr_22rem] gap-6 mt-6">
        <div class="space-y-6">
            <?php if ($hasMap): ?>
                <div class="surface p-2"><div id="map" class="h-80 md:h-96 w-full"></div></div>
            <?php endif; ?>
            <div class="surface p-6">
                <h2 class="font-bold text-lg mb-5"><?= e(__('Tracking history')) ?></h2>
                <?= View::partial('partials/timeline', ['events' => $events]) ?>
            </div>
        </div>
        <aside class="space-y-4">
            <div class="surface p-5 space-y-3">
                <div class="flex justify-between text-sm"><span class="opacity-60"><?= e(__('Order')) ?></span><span class="font-mono font-semibold"><?= e($order['number']) ?></span></div>
                <div class="flex justify-between text-sm"><span class="opacity-60"><?= e(__('Destination')) ?></span><span class="font-semibold"><?= e(trim($order['city'] . ($order['area'] ? ', ' . $order['area'] : ''))) ?></span></div>
                <?php if ($s['courier_name']): ?><div class="flex justify-between text-sm"><span class="opacity-60"><?= e(__('Courier')) ?></span><span class="font-semibold"><?= e($s['courier_name']) ?></span></div><?php endif; ?>
                <div class="flex justify-between text-sm"><span class="opacity-60"><?= e(__('Shipped on')) ?></span><span><?= e(format_date($s['created_at'])) ?></span></div>
            </div>
            <?php if ($s['driver_name'] && in_array($s['status'], ['picked_up', 'in_transit', 'out_for_delivery'], true)): ?>
                <div class="surface p-5 flex items-center gap-3">
                    <span class="avatar avatar-placeholder"><span class="w-12 rounded-full bg-brand font-bold"><?= e(initials($s['driver_name'])) ?></span></span>
                    <div class="flex-1"><div class="text-xs opacity-60"><?= e(__('Your driver')) ?></div><div class="font-bold"><?= e($s['driver_name']) ?></div></div>
                    <?php if ($s['driver_phone'] && $s['status'] === 'out_for_delivery'): ?><a href="tel:<?= e($s['driver_phone']) ?>" class="btn btn-success btn-circle"><?= icon('phone', 'size-5') ?></a><?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if ($externalUrl): ?>
                <a href="<?= e($externalUrl) ?>" target="_blank" rel="noopener" class="btn w-full rounded-full"><?= icon('external-link', 'size-4') ?> <?= e(__('Track on courier website')) ?></a>
            <?php endif; ?>
        </aside>
    </div>
</div>
<?php push_start(); ?>
<script>
function tracker() {
    return {
        label: <?= js(__('status.' . $s['status'])) ?>,
        progress: <?= DeliveryService::progress($s['status']) ?>,
        status: <?= js($s['status']) ?>,
        map: null, driver: null,
        init() {
            <?php if ($hasMap): ?>
            const dest = <?= js($order['lat'] !== null ? [(float) $order['lat'], (float) $order['lng']] : null) ?>;
            const drv = <?= js($s['last_lat'] !== null ? [(float) $s['last_lat'], (float) $s['last_lng']] : null) ?>;
            this.map = L.map('map', { zoomControl: true, attributionControl: true }).setView(drv || dest, 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(this.map);
            const pin = (cls, ic) => L.divIcon({ className: '', html: `<span class="grid place-items-center size-10 rounded-full ${cls} shadow-xl ring-4 ring-base-100"><i data-lucide="${ic}" class="size-5"></i></span>`, iconSize: [40, 40], iconAnchor: [20, 20] });
            if (dest) L.marker(dest, { icon: pin('bg-secondary text-secondary-content', 'house') }).addTo(this.map);
            if (drv) this.driver = L.marker(drv, { icon: pin('bg-primary text-primary-content', 'truck') }).addTo(this.map);
            if (dest && drv) this.map.fitBounds([dest, drv], { padding: [50, 50] });
            setTimeout(() => window.matjar.icons(), 50);
            <?php endif; ?>
            if (!['delivered', 'cancelled', 'returned'].includes(this.status)) setInterval(() => this.refresh(), 20000);
        },
        async refresh() {
            const res = await fetch(<?= js(url('/track/' . rawurlencode($s['tracking_number']) . '/json')) ?>, { headers: { Accept: 'application/json' } });
            if (!res.ok) return;
            const d = await res.json();
            if (d.status !== this.status) { location.reload(); return; }
            this.label = d.status_label; this.progress = d.progress;
            if (d.location && this.map) {
                const ll = [d.location.lat, d.location.lng];
                if (this.driver) this.driver.setLatLng(ll); else location.reload();
            }
        },
    };
}
</script>
<?php push_end('scripts'); ?>
