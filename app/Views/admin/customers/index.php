<?php use App\Core\View; ?>
<form class="mb-4">
    <label class="input input-sm w-full max-w-sm"><?= icon('search', 'size-4 opacity-50') ?><input name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="<?= e(__('Name, email or phone')) ?>" class="grow"></label>
</form>
<div class="surface overflow-hidden">
    <?php if ($page['items']): ?>
    <div class="overflow-x-auto">
        <table class="table table-modern">
            <thead><tr><th><?= e(__('Customer')) ?></th><th><?= e(__('Phone')) ?></th><th><?= e(__('Orders')) ?></th><th><?= e(__('Total spent')) ?></th><th><?= e(__('Joined')) ?></th><th><?= e(__('Status')) ?></th></tr></thead>
            <tbody>
            <?php foreach ($page['items'] as $c): ?>
                <tr class="cursor-pointer" onclick="location='<?= e(url('/admin/customers/' . $c['id'])) ?>'">
                    <td><div class="flex items-center gap-3">
                        <span class="avatar avatar-placeholder"><span class="w-9 rounded-full bg-brand text-xs font-bold"><?= e(initials($c['name'])) ?></span></span>
                        <div><div class="font-semibold"><?= e($c['name']) ?></div><div class="text-xs opacity-60"><?= e($c['email']) ?></div></div>
                    </div></td>
                    <td dir="ltr" class="text-sm"><?= e($c['phone'] ?? '—') ?></td>
                    <td><?= (int) $c['orders_count'] ?></td>
                    <td class="font-semibold"><?= e(money($c['spent'])) ?></td>
                    <td class="text-sm opacity-60"><?= e(format_date($c['created_at'])) ?></td>
                    <td><?= status_badge($c['status']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <?= View::partial('partials/empty', ['icon' => 'users', 'title' => __('No customers yet')]) ?>
    <?php endif; ?>
</div>
<?= View::partial('partials/pagination', ['page' => $page]) ?>
