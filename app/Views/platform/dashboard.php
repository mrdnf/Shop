<div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4">
    <?php foreach ([
        ['store', __('Shops'), $stats['shops'], __(':a active · :t trial', ['a' => $stats['active'], 't' => $stats['trial']]), 'from-primary to-secondary'],
        ['badge-dollar-sign', __('MRR'), '$' . number_format($stats['mrr'], 2), __('From active paid plans'), 'from-secondary to-accent'],
        ['shopping-cart', __('Orders this month'), number_format($stats['orders']), __('Across all shops'), 'from-accent to-primary'],
        ['trending-up', __('GMV this month'), number_format($stats['gmv'], 2), __('Gross merchandise value'), 'from-primary to-accent'],
    ] as [$ic, $label, $value, $sub, $grad]): ?>
        <div class="relative overflow-hidden surface p-5">
            <div class="absolute -top-10 -end-10 size-32 rounded-full bg-gradient-to-br <?= $grad ?> opacity-15 blur-xl"></div>
            <span class="kpi-icon bg-gradient-to-br <?= $grad ?> text-white shadow-lg"><?= icon($ic) ?></span>
            <div class="mt-4 text-3xl font-black"><?= e($value) ?></div>
            <div class="text-sm font-semibold"><?= e($label) ?></div>
            <div class="text-xs opacity-60"><?= e($sub) ?></div>
        </div>
    <?php endforeach; ?>
</div>

<div class="grid xl:grid-cols-[1fr_20rem] gap-4 mt-4">
    <section class="surface overflow-hidden">
        <div class="p-5 flex items-center justify-between">
            <h2 class="font-bold"><?= e(__('Latest shops')) ?></h2>
            <a href="<?= e(base_url('/platform/shops/create')) ?>" class="btn btn-primary btn-sm rounded-full"><?= icon('plus', 'size-4') ?> <?= e(__('New shop')) ?></a>
        </div>
        <div class="overflow-x-auto">
            <table class="table table-modern">
                <thead><tr><th><?= e(__('Shop')) ?></th><th><?= e(__('Plan')) ?></th><th><?= e(__('Products')) ?></th><th><?= e(__('Orders (month)')) ?></th><th><?= e(__('Status')) ?></th></tr></thead>
                <tbody>
                <?php foreach ($tenants as $t): ?>
                    <tr class="cursor-pointer" onclick="location='<?= e(base_url('/platform/shops/' . $t['id'] . '/edit')) ?>'">
                        <td><div class="flex items-center gap-3">
                            <span class="size-10 rounded-xl bg-brand grid place-items-center font-black text-xs"><?= e(initials($t['name'])) ?></span>
                            <div><div class="font-semibold"><?= e($t['name']) ?></div><div class="text-xs opacity-60 font-mono"><?= e($t['db_name']) ?></div></div>
                        </div></td>
                        <td><?= e($t['plan_name'] ?? '—') ?></td>
                        <td><?= isset($t['products']) ? (int) $t['products'] : '—' ?></td>
                        <td><?= $t['orders'] === null ? '<span class="badge badge-error badge-sm" title="' . e($t['error'] ?? '') . '">DB</span>' : (int) $t['orders'] ?></td>
                        <td><?= status_badge($t['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$tenants): ?><tr><td class="opacity-60 py-10 text-center"><?= e(__('No shops yet. Create the first one!')) ?></td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    <section class="surface p-5">
        <h2 class="font-bold mb-4"><?= e(__('Shops by plan')) ?></h2>
        <?php $max = max(1, ...array_map(static fn ($r) => (int) $r['c'], $byPlan ?: [['c' => 1]])); ?>
        <ul class="space-y-4">
            <?php foreach ($byPlan as $row): ?>
                <li><div class="flex justify-between text-sm"><span class="font-medium"><?= e($row['name']) ?></span><span class="font-bold"><?= (int) $row['c'] ?></span></div>
                    <progress class="progress progress-primary" value="<?= (int) $row['c'] ?>" max="<?= $max ?>"></progress></li>
            <?php endforeach; ?>
        </ul>
        <?php if ($stats['suspended']): ?><div class="alert alert-warning alert-soft mt-6 text-sm"><?= icon('pause-circle', 'size-4') ?><?= e(__(':n suspended shops', ['n' => $stats['suspended']])) ?></div><?php endif; ?>
    </section>
</div>
