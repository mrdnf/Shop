<div class="overflow-x-auto">
    <table class="table table-modern">
        <thead><tr><th><?= e(__('Order')) ?></th><th><?= e(__('Date')) ?></th><th><?= e(__('Status')) ?></th><th class="text-end"><?= e(__('Total')) ?></th><th></th></tr></thead>
        <tbody>
        <?php foreach ($orders as $o): ?>
            <tr>
                <td class="font-mono font-semibold"><?= e($o['number']) ?></td>
                <td class="opacity-70"><?= e(format_date($o['created_at'])) ?></td>
                <td><?= status_badge($o['status']) ?></td>
                <td class="text-end font-semibold"><?= e(money($o['total'])) ?></td>
                <td class="text-end"><a href="<?= e(url('/account/orders/' . $o['number'])) ?>" class="btn btn-ghost btn-xs rounded-full"><?= e(__('Details')) ?> <?= icon('chevron-right', 'size-3 flip-rtl') ?></a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
