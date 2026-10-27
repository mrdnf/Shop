<?php use App\Core\View; ?>
<div class="flex flex-wrap items-center gap-3 mb-5">
    <form class="flex gap-2 flex-1">
        <label class="input input-sm w-72"><?= icon('search', 'size-4 opacity-50') ?><input name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="<?= e(__('Name, address, domain, owner')) ?>" class="grow"></label>
        <select name="status" class="select select-sm w-40" onchange="this.form.submit()">
            <option value=""><?= e(__('All statuses')) ?></option>
            <?php foreach (['active', 'trial', 'suspended'] as $s): ?><option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(__('status.' . $s)) ?></option><?php endforeach; ?>
        </select>
    </form>
    <a href="<?= e(base_url('/platform/shops/create')) ?>" class="btn btn-primary btn-sm rounded-full shadow-lg shadow-primary/25"><?= icon('plus', 'size-4') ?> <?= e(__('New shop')) ?></a>
</div>
<div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
    <?php foreach ($page['items'] as $t): ?>
        <div class="surface p-5 card-lift">
            <div class="flex items-start gap-3">
                <span class="size-12 rounded-2xl bg-brand grid place-items-center font-black"><?= e(initials($t['name'])) ?></span>
                <div class="flex-1 min-w-0">
                    <div class="font-bold truncate"><?= e($t['name']) ?></div>
                    <a href="<?= e($t['url']) ?>" target="_blank" class="text-xs link opacity-70 truncate block" dir="ltr"><?= e(preg_replace('#^https?://#', '', $t['url'])) ?></a>
                </div>
                <?= status_badge($t['status']) ?>
            </div>
            <div class="grid grid-cols-2 gap-2 mt-4 text-xs">
                <div class="rounded-lg bg-base-200 p-2"><div class="opacity-60"><?= e(__('Plan')) ?></div><div class="font-semibold"><?= e($t['plan_name'] ?? '—') ?></div></div>
                <div class="rounded-lg bg-base-200 p-2"><div class="opacity-60"><?= e(__('Database')) ?></div><div class="font-mono truncate"><?= e($t['db_name']) ?></div></div>
            </div>
            <div class="flex items-center justify-between mt-4">
                <span class="text-xs opacity-60"><?= e($t['owner_email']) ?></span>
                <div class="flex gap-1">
                    <a href="<?= e($t['url'] . '/admin') ?>" target="_blank" class="btn btn-ghost btn-xs rounded-full"><?= icon('layout-dashboard', 'size-3.5') ?> <?= e(__('Admin')) ?></a>
                    <a href="<?= e(base_url('/platform/shops/' . $t['id'] . '/edit')) ?>" class="btn btn-xs rounded-full"><?= icon('settings', 'size-3.5') ?></a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php if (!$page['items']): ?><div class="surface"><?= View::partial('partials/empty', ['icon' => 'store', 'title' => __('No shops found')]) ?></div><?php endif; ?>
<?= View::partial('partials/pagination', ['page' => $page]) ?>
