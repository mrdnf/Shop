<?php
use App\Core\View;
use App\Services\Delivery\DeliveryService;

if ($live) {
    View::push('head', '<link rel="stylesheet" href="' . asset('vendor/leaflet/leaflet.css') . '"><script src="' . asset('vendor/leaflet/leaflet.js') . '"></script>');
}
?>
<?php if ($live): ?>
    <section class="surface p-2 mb-6">
        <div class="flex items-center gap-2 px-3 py-2 font-bold"><span class="live-dot"></span><?= e(__('Live fleet map')) ?> <span class="badge badge-sm"><?= count($live) ?></span></div>
        <div id="fleet" class="h-80 w-full"></div>
    </section>
    <?php push_start(); ?>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const pts = <?= js(array_map(static fn ($s) => ['lat' => (float) $s['last_lat'], 'lng' => (float) $s['last_lng'], 'label' => $s['tracking_number'] . ' · ' . ($s['driver_name'] ?? ''), 'url' => url('/admin/shipments/' . $s['id'])], $live)) ?>;
        const map = L.map('fleet');
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map);
        const icon = L.divIcon({ className: '', html: '<span class="grid place-items-center size-9 rounded-full bg-primary text-primary-content shadow-xl ring-4 ring-base-100"><i data-lucide="truck" class="size-4"></i></span>', iconSize: [36, 36], iconAnchor: [18, 18] });
        const b = [];
        pts.forEach(p => { const m = L.marker([p.lat, p.lng], { icon }).addTo(map); const a = document.createElement('a'); a.href = p.url; a.textContent = p.label; m.bindPopup(a); b.push([p.lat, p.lng]); });
        map.fitBounds(b, { padding: [40, 40], maxZoom: 14 });
        setTimeout(() => window.matjar.icons(), 50);
    });
    </script>
    <?php push_end('scripts'); ?>
<?php endif; ?>

<div class="flex flex-wrap gap-2 mb-4">
    <?php foreach (['' => __('All'), 'active' => __('Active')] + array_combine(DeliveryService::statuses(), array_map(static fn ($s) => __('status.' . $s), DeliveryService::statuses())) as $k => $label): ?>
        <a href="<?= e(url('/admin/shipments', array_filter(['status' => $k, 'driver' => $_GET['driver'] ?? null]))) ?>" class="btn btn-xs rounded-full <?= $status === $k ? 'btn-primary' : 'btn-ghost bg-base-100' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</div>
<form class="flex flex-wrap gap-2 mb-4">
    <?php if ($status): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
    <label class="input input-sm w-72"><?= icon('search', 'size-4 opacity-50') ?><input name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="<?= e(__('Tracking #, order #, customer')) ?>" class="grow"></label>
    <select name="driver" class="select select-sm w-48" onchange="this.form.submit()">
        <option value=""><?= e(__('All drivers')) ?></option>
        <?php foreach ($drivers as $d): ?><option value="<?= (int) $d['id'] ?>" <?= ($_GET['driver'] ?? '') == $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option><?php endforeach; ?>
    </select>
</form>

<div class="surface overflow-hidden">
    <?php if ($page['items']): ?>
    <div class="overflow-x-auto">
        <table class="table table-modern">
            <thead><tr><th><?= e(__('Tracking')) ?></th><th><?= e(__('Order')) ?></th><th><?= e(__('Courier / driver')) ?></th><th><?= e(__('Progress')) ?></th><th><?= e(__('Status')) ?></th><th><?= e(__('Updated')) ?></th></tr></thead>
            <tbody>
            <?php foreach ($page['items'] as $s): ?>
                <tr class="cursor-pointer" onclick="location='<?= e(url('/admin/shipments/' . $s['id'])) ?>'">
                    <td class="font-mono font-semibold"><?= e($s['tracking_number']) ?></td>
                    <td><div class="font-mono text-sm"><?= e($s['number']) ?></div><div class="text-xs opacity-60"><?= e($s['customer_name']) ?> · <?= e($s['city']) ?></div></td>
                    <td class="text-sm"><?= e($s['courier_name'] ?? '—') ?><?php if ($s['driver_name']): ?><div class="text-xs opacity-60 flex items-center gap-1"><?= icon('user-round', 'size-3') ?><?= e($s['driver_name']) ?></div><?php endif; ?></td>
                    <td class="w-40"><progress class="progress progress-primary" value="<?= DeliveryService::progress($s['status']) ?>" max="100"></progress></td>
                    <td><?= status_badge($s['status']) ?></td>
                    <td class="text-xs opacity-60"><?= e(format_date($s['updated_at'] ?? $s['created_at'], true)) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <?= View::partial('partials/empty', ['icon' => 'truck', 'title' => __('No shipments'), 'text' => __('Create a shipment from an order to start tracking.')]) ?>
    <?php endif; ?>
</div>
<?= View::partial('partials/pagination', ['page' => $page]) ?>
