<?php use App\Core\View; ?>
<div class="flex flex-wrap items-center gap-3 mb-5">
    <form class="flex flex-wrap gap-2 flex-1">
        <label class="input input-sm w-64"><?= icon('search', 'size-4 opacity-50') ?><input name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="<?= e(__('Search name or SKU')) ?>" class="grow"></label>
        <select name="category" class="select select-sm w-44" onchange="this.form.submit()">
            <option value=""><?= e(__('All categories')) ?></option>
            <?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>" <?= ($_GET['category'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= e(tr($c, 'name')) ?></option><?php endforeach; ?>
        </select>
        <div class="join">
            <?php foreach (['' => __('All'), 'low' => __('Low stock'), 'out' => __('Out of stock')] as $k => $label): ?>
                <a href="?<?= e(http_build_query(array_merge($_GET, ['stock' => $k, 'page' => 1]))) ?>" class="join-item btn btn-sm <?= ($_GET['stock'] ?? '') === $k ? 'btn-primary' : '' ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </div>
    </form>
    <a href="<?= e(url('/admin/products/create')) ?>" class="btn btn-primary btn-sm rounded-full shadow-lg shadow-primary/25"><?= icon('plus', 'size-4') ?> <?= e(__('New product')) ?></a>
</div>

<div class="surface overflow-hidden">
    <?php if ($page['items']): ?>
    <div class="overflow-x-auto">
        <table class="table table-modern">
            <thead><tr><th><?= e(__('Product')) ?></th><th><?= e(__('Category')) ?></th><th><?= e(__('Price')) ?></th><th><?= e(__('Stock')) ?></th><th><?= e(__('Status')) ?></th><th></th></tr></thead>
            <tbody>
            <?php foreach ($page['items'] as $p):
                $stockCls = !$p['track_stock'] ? 'badge-ghost' : ($p['stock'] <= 0 ? 'badge-error' : ($p['stock'] <= $p['low_stock_threshold'] ? 'badge-warning' : 'badge-success')); ?>
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <span class="size-11 rounded-xl placeholder-img overflow-hidden grid place-items-center shrink-0">
                                <?php if ($p['image']): ?><img src="<?= e(upload_url($p['image'])) ?>" class="size-full object-cover" alt=""><?php else: ?><?= icon('package', 'size-5 text-primary/60') ?><?php endif; ?>
                            </span>
                            <div class="min-w-0">
                                <a href="<?= e(url('/admin/products/' . $p['id'] . '/edit')) ?>" class="font-semibold hover:text-primary line-clamp-1"><?= e(tr($p, 'name')) ?></a>
                                <div class="text-xs opacity-50 font-mono"><?= e($p['sku']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="text-sm opacity-70"><?= e(locale() === 'ar' && $p['category_ar'] ? $p['category_ar'] : ($p['category_en'] ?? '—')) ?></td>
                    <td class="font-semibold"><?= e(money($p['price'])) ?></td>
                    <td>
                        <div class="flex items-center gap-2">
                            <span class="badge badge-sm badge-soft <?= $stockCls ?> font-bold"><?= $p['track_stock'] ? (int) $p['stock'] : '∞' ?></span>
                            <?php if ($p['track_stock'] && $p['stock_source'] === 'auto' && $provider !== 'local'): ?>
                                <span class="tooltip" data-tip="<?= e(__('Synced from :p', ['p' => __('provider.' . $provider)])) ?>"><?= icon('refresh-cw', 'size-3.5 text-primary') ?></span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <?= $p['is_active'] ? '<span class="badge badge-sm badge-success badge-soft">' . e(__('Active')) . '</span>' : '<span class="badge badge-sm badge-ghost">' . e(__('Hidden')) . '</span>' ?>
                        <?php if ($p['is_featured']): ?><span class="badge badge-sm badge-accent badge-soft"><?= icon('sparkles', 'size-3') ?></span><?php endif; ?>
                    </td>
                    <td class="text-end">
                        <a href="<?= e(url('/admin/products/' . $p['id'] . '/edit')) ?>" class="btn btn-ghost btn-xs btn-circle"><?= icon('pencil', 'size-4') ?></a>
                        <a href="<?= e(url('/product/' . $p['slug'])) ?>" target="_blank" class="btn btn-ghost btn-xs btn-circle"><?= icon('external-link', 'size-4') ?></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <?= View::partial('partials/empty', ['icon' => 'package', 'title' => __('No products found'), 'action' => '<a href="' . e(url('/admin/products/create')) . '" class="btn btn-primary btn-sm rounded-full">' . e(__('New product')) . '</a>']) ?>
    <?php endif; ?>
</div>
<?= View::partial('partials/pagination', ['page' => $page]) ?>
