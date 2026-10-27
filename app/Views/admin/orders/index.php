<?php
use App\Controllers\Admin\OrderController;
use App\Core\View;
?>
<div class="flex gap-2 overflow-x-auto pb-1 mb-4">
    <a href="<?= e(url('/admin/orders')) ?>" class="btn btn-sm rounded-full <?= $status === '' ? 'btn-primary' : 'btn-ghost bg-base-100' ?>"><?= e(__('All')) ?> <span class="badge badge-sm"><?= array_sum($counts) ?></span></a>
    <?php foreach (OrderController::STATUSES as $s): ?>
        <a href="<?= e(url('/admin/orders', ['status' => $s])) ?>" class="btn btn-sm rounded-full whitespace-nowrap <?= $status === $s ? 'btn-primary' : 'btn-ghost bg-base-100' ?>">
            <?= e(__('status.' . $s)) ?> <span class="badge badge-sm"><?= $counts[$s] ?? 0 ?></span>
        </a>
    <?php endforeach; ?>
</div>
<form class="mb-4">
    <?php if ($status): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
    <label class="input input-sm w-full max-w-sm"><?= icon('search', 'size-4 opacity-50') ?><input name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="<?= e(__('Order #, customer, phone…')) ?>" class="grow"></label>
</form>

<div class="surface overflow-hidden">
    <?php if ($page['items']): ?>
    <div class="overflow-x-auto">
        <table class="table table-modern">
            <thead><tr><th><?= e(__('Order')) ?></th><th><?= e(__('Customer')) ?></th><th><?= e(__('City')) ?></th><th><?= e(__('Items')) ?></th><th><?= e(__('Payment')) ?></th><th><?= e(__('Status')) ?></th><th class="text-end"><?= e(__('Total')) ?></th></tr></thead>
            <tbody>
            <?php foreach ($page['items'] as $o): ?>
                <tr class="cursor-pointer" onclick="location='<?= e(url('/admin/orders/' . $o['id'])) ?>'">
                    <td><div class="font-mono font-semibold"><?= e($o['number']) ?></div><div class="text-xs opacity-50"><?= e(format_date($o['created_at'], true)) ?></div></td>
                    <td><div class="font-medium"><?= e($o['customer_name']) ?></div><div class="text-xs opacity-50" dir="ltr"><?= e($o['phone']) ?></div></td>
                    <td class="text-sm"><?= e($o['city']) ?></td>
                    <td><?= (int) $o['items_count'] ?></td>
                    <td><span class="text-xs opacity-70"><?= e(__('payment.' . $o['payment_method'])) ?></span><br><?= status_badge($o['payment_status']) ?></td>
                    <td><?= status_badge($o['status']) ?></td>
                    <td class="text-end font-bold"><?= e(money($o['total'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <?= View::partial('partials/empty', ['icon' => 'shopping-cart', 'title' => __('No orders found')]) ?>
    <?php endif; ?>
</div>
<?= View::partial('partials/pagination', ['page' => $page]) ?>
