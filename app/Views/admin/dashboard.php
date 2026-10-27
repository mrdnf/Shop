<?php
use App\Core\View;

View::push('head', '<script src="' . asset('vendor/chart.umd.min.js') . '"></script>');
$symbol = (string) setting('store.currency_symbol', '$');
$kpiCards = [
    ['wallet', __('Revenue this month'), $kpis['month'], 'from-primary to-secondary', true, $kpis['growth']],
    ['receipt', __('Revenue today'), $kpis['today'], 'from-secondary to-accent', true, null],
    ['shopping-cart', __('Orders today'), $kpis['orders_today'], 'from-accent to-primary', false, null],
    ['users', __('Customers'), $kpis['customers'], 'from-primary to-accent', false, null],
];
?>
<div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4">
    <?php foreach ($kpiCards as $i => [$ic, $label, $value, $grad, $isMoney, $growth]): ?>
        <div class="relative overflow-hidden surface p-5 animate-fade-up" style="animation-delay: <?= $i * 0.06 ?>s">
            <div class="absolute -top-10 -end-10 size-32 rounded-full bg-gradient-to-br <?= $grad ?> opacity-15 blur-xl"></div>
            <div class="flex items-start justify-between">
                <span class="kpi-icon bg-gradient-to-br <?= $grad ?> text-white shadow-lg"><?= icon($ic) ?></span>
                <?php if ($growth !== null): ?>
                    <span class="badge badge-sm <?= $growth >= 0 ? 'badge-success' : 'badge-error' ?> badge-soft gap-1">
                        <?= icon($growth >= 0 ? 'trending-up' : 'trending-down', 'size-3') ?><?= e(abs($growth)) ?>%
                    </span>
                <?php endif; ?>
            </div>
            <div class="mt-4 text-3xl font-black tracking-tight" data-count-up="<?= e((float) $value) ?>" data-decimals="<?= $isMoney ? 2 : 0 ?>"
                <?= $isMoney ? (is_rtl() ? 'data-suffix=" ' . e($symbol) . '"' : 'data-prefix="' . e($symbol) . '"') : '' ?>>
                <?= e($isMoney ? money($value) : $value) ?>
            </div>
            <div class="text-sm opacity-60 mt-1"><?= e($label) ?></div>
        </div>
    <?php endforeach; ?>
</div>

<div class="grid sm:grid-cols-3 gap-4 mt-4">
    <a href="<?= e(url('/admin/orders?status=pending')) ?>" class="surface p-4 flex items-center gap-4 card-lift">
        <span class="kpi-icon bg-warning/15 text-warning"><?= icon('hourglass') ?></span>
        <div><div class="text-2xl font-black"><?= (int) $kpis['pending'] ?></div><div class="text-sm opacity-60"><?= e(__('Orders to process')) ?></div></div>
    </a>
    <a href="<?= e(url('/admin/shipments?status=active')) ?>" class="surface p-4 flex items-center gap-4 card-lift">
        <span class="kpi-icon bg-info/15 text-info"><?= icon('truck') ?></span>
        <div><div class="text-2xl font-black"><?= (int) $kpis['in_delivery'] ?></div><div class="text-sm opacity-60"><?= e(__('Out for delivery')) ?></div></div>
    </a>
    <a href="<?= e(url('/admin/products?stock=low')) ?>" class="surface p-4 flex items-center gap-4 card-lift">
        <span class="kpi-icon bg-error/15 text-error"><?= icon('triangle-alert') ?></span>
        <div><div class="text-2xl font-black"><?= (int) $kpis['low_stock'] ?></div><div class="text-sm opacity-60"><?= e(__('Low stock products')) ?></div></div>
    </a>
</div>

<div class="grid xl:grid-cols-3 gap-4 mt-4">
    <div class="surface p-5 xl:col-span-2">
        <div class="flex items-center justify-between">
            <h2 class="font-bold"><?= e(__('Sales · last 30 days')) ?></h2>
            <span class="badge badge-ghost"><?= e(money(array_sum($series['revenue']))) ?></span>
        </div>
        <div class="h-72 mt-4"><canvas id="salesChart"></canvas></div>
    </div>
    <div class="surface p-5">
        <h2 class="font-bold"><?= e(__('Orders by status')) ?></h2>
        <div class="h-56 mt-4 grid place-items-center">
            <?php if ($statusCounts): ?><canvas id="statusChart"></canvas><?php else: ?><span class="opacity-50 text-sm"><?= e(__('No orders yet')) ?></span><?php endif; ?>
        </div>
        <div class="mt-4 flex flex-wrap gap-2">
            <?php foreach ($statusCounts as $s => $c): ?><span class="badge badge-sm badge-soft <?= status_class($s) ?>"><?= e(__('status.' . $s)) ?> · <?= $c ?></span><?php endforeach; ?>
        </div>
    </div>
</div>

<div class="grid xl:grid-cols-3 gap-4 mt-4">
    <div class="surface xl:col-span-2 overflow-hidden">
        <div class="p-5 flex items-center justify-between">
            <h2 class="font-bold"><?= e(__('Recent orders')) ?></h2>
            <a href="<?= e(url('/admin/orders')) ?>" class="btn btn-ghost btn-xs rounded-full"><?= e(__('View all')) ?></a>
        </div>
        <div class="overflow-x-auto">
            <table class="table table-modern">
                <tbody>
                <?php foreach ($recentOrders as $o): ?>
                    <tr class="cursor-pointer" onclick="location='<?= e(url('/admin/orders/' . $o['id'])) ?>'">
                        <td>
                            <div class="flex items-center gap-3">
                                <span class="avatar avatar-placeholder"><span class="w-9 rounded-full bg-primary/10 text-primary text-xs font-bold"><?= e(initials($o['customer_name'])) ?></span></span>
                                <div><div class="font-semibold"><?= e($o['customer_name']) ?></div><div class="text-xs opacity-50 font-mono"><?= e($o['number']) ?></div></div>
                            </div>
                        </td>
                        <td class="opacity-70 text-sm"><?= e(format_date($o['created_at'], true)) ?></td>
                        <td><?= status_badge($o['status']) ?></td>
                        <td class="text-end font-bold"><?= e(money($o['total'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$recentOrders): ?><tr><td class="text-center opacity-50 py-10"><?= e(__('No orders yet')) ?></td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="space-y-4">
        <div class="surface p-5">
            <div class="flex items-center justify-between">
                <h2 class="font-bold flex items-center gap-2"><?= icon('refresh-cw', 'size-4 text-primary') ?><?= e(__('Inventory sync')) ?></h2>
                <span class="badge badge-sm badge-primary badge-soft"><?= e(__('provider.' . $inventoryProvider)) ?></span>
            </div>
            <?php if ($lastSync): ?>
                <div class="mt-3 text-sm flex items-center gap-2"><?= status_badge($lastSync['status']) ?><span class="opacity-60"><?= e(format_date($lastSync['started_at'], true)) ?></span></div>
                <p class="text-xs opacity-60 mt-1"><?= e($lastSync['message']) ?></p>
            <?php else: ?>
                <p class="text-sm opacity-60 mt-3"><?= e(__('Connect your ERP / POS to pull live stock balances.')) ?></p>
            <?php endif; ?>
            <?php if (App\Core\Auth::is('admin')): ?><a href="<?= e(url('/admin/inventory')) ?>" class="btn btn-sm btn-block rounded-full mt-4"><?= e(__('Manage integration')) ?></a><?php endif; ?>
        </div>
        <div class="surface p-5">
            <h2 class="font-bold mb-3 flex items-center gap-2"><?= icon('triangle-alert', 'size-4 text-warning') ?><?= e(__('Low stock')) ?></h2>
            <ul class="space-y-3">
                <?php foreach ($lowStock as $p): ?>
                    <li class="flex items-center gap-3">
                        <a class="flex-1 text-sm font-medium truncate hover:text-primary" href="<?= e(url('/admin/products/' . $p['id'] . '/edit')) ?>"><?= e(tr($p, 'name')) ?></a>
                        <span class="badge badge-sm <?= $p['stock'] <= 0 ? 'badge-error' : 'badge-warning' ?>"><?= (int) $p['stock'] ?></span>
                    </li>
                <?php endforeach; ?>
                <?php if (!$lowStock): ?><li class="text-sm opacity-60"><?= e(__('All products are well stocked.')) ?></li><?php endif; ?>
            </ul>
        </div>
        <?php if ($topProducts): ?>
        <div class="surface p-5">
            <h2 class="font-bold mb-3 flex items-center gap-2"><?= icon('trophy', 'size-4 text-accent') ?><?= e(__('Top sellers')) ?></h2>
            <?php $max = max(array_column($topProducts, 'qty')); ?>
            <ul class="space-y-3">
                <?php foreach ($topProducts as $tp): ?>
                    <li>
                        <div class="flex justify-between text-sm"><span class="truncate"><?= e($tp['name']) ?></span><span class="font-semibold"><?= (int) $tp['qty'] ?></span></div>
                        <progress class="progress progress-primary h-1.5" value="<?= (int) $tp['qty'] ?>" max="<?= (int) $max ?>"></progress>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php push_start(); ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const css = getComputedStyle(document.documentElement);
    const c = (v) => css.getPropertyValue(v).trim();
    const primary = c('--color-primary'), secondary = c('--color-secondary');
    const grid = 'rgba(127,127,127,.12)';
    Chart.defaults.font.family = c('--font-sans');
    Chart.defaults.color = 'rgba(127,127,127,.9)';
    const ctx = document.getElementById('salesChart').getContext('2d');
    const g = ctx.createLinearGradient(0, 0, 0, 280);
    g.addColorStop(0, primary + '55'); g.addColorStop(1, primary + '00');
    new Chart(ctx, {
        type: 'line',
        data: { labels: <?= js($series['labels']) ?>, datasets: [
            { label: <?= js(__('Revenue')) ?>, data: <?= js($series['revenue']) ?>, borderColor: primary, backgroundColor: g, fill: true, tension: .4, borderWidth: 3, pointRadius: 0, pointHoverRadius: 6 },
            { label: <?= js(__('Orders')) ?>, data: <?= js($series['orders']) ?>, borderColor: secondary, borderDash: [6, 6], tension: .4, borderWidth: 2, pointRadius: 0, yAxisID: 'y1' },
        ] },
        options: { maintainAspectRatio: false, interaction: { intersect: false, mode: 'index' },
            plugins: { legend: { display: true, align: 'end', labels: { usePointStyle: true, boxWidth: 8 } } },
            scales: { x: { grid: { display: false }, reverse: document.dir === 'rtl', ticks: { maxTicksLimit: 8 } },
                      y: { grid: { color: grid }, beginAtZero: true, position: document.dir === 'rtl' ? 'right' : 'left' },
                      y1: { display: false, beginAtZero: true } } },
    });
    <?php if ($statusCounts): ?>
    const palette = { pending: '#f59e0b', confirmed: '#0ea5e9', processing: '#6366f1', shipped: primary, delivered: '#10b981', cancelled: '#ef4444' };
    const labels = <?= js(array_map(static fn ($s) => __('status.' . $s), array_keys($statusCounts))) ?>;
    const keys = <?= js(array_keys($statusCounts)) ?>;
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: { labels, datasets: [{ data: <?= js(array_values($statusCounts)) ?>, backgroundColor: keys.map(k => palette[k] || '#94a3b8'), borderWidth: 0, hoverOffset: 8 }] },
        options: { cutout: '72%', plugins: { legend: { display: false } } },
    });
    <?php endif; ?>
});
</script>
<?php push_end('scripts'); ?>
