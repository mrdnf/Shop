<?php
$p = $product ?? [];
$v = static fn (string $k, mixed $d = '') => e(old($k, $p[$k] ?? $d));
$checked = static fn (string $k, bool $d) => (old($k, isset($p[$k]) ? (string) $p[$k] : ($d ? '1' : '0')) === '1') ? 'checked' : '';
$action = $product ? url('/admin/products/' . $product['id']) : url('/admin/products');
?>
<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="grid xl:grid-cols-[1fr_22rem] gap-6">
    <?= csrf_field() ?>
    <div class="space-y-6">
        <section class="surface p-6" x-data="{ tab: 'en' }">
            <div class="flex items-center justify-between">
                <h2 class="font-bold"><?= e(__('Product details')) ?></h2>
                <div role="tablist" class="tabs tabs-box tabs-sm">
                    <button type="button" role="tab" class="tab" :class="tab==='en' && 'tab-active'" @click="tab='en'">English</button>
                    <button type="button" role="tab" class="tab" :class="tab==='ar' && 'tab-active'" @click="tab='ar'">العربية</button>
                </div>
            </div>
            <div class="mt-5 space-y-4" x-show="tab==='en'">
                <label class="field"><span class="label-text"><?= e(__('Name')) ?> (EN)</span><input name="name_en" value="<?= $v('name_en') ?>" class="input w-full" required></label>
                <label class="field"><span class="label-text"><?= e(__('Description')) ?> (EN)</span><textarea name="description_en" rows="6" class="textarea w-full"><?= $v('description_en') ?></textarea></label>
            </div>
            <div class="mt-5 space-y-4" x-show="tab==='ar'" x-cloak dir="rtl">
                <label class="field"><span class="label-text"><?= e(__('Name')) ?> (AR)</span><input name="name_ar" value="<?= $v('name_ar') ?>" class="input w-full"></label>
                <label class="field"><span class="label-text"><?= e(__('Description')) ?> (AR)</span><textarea name="description_ar" rows="6" class="textarea w-full"><?= $v('description_ar') ?></textarea></label>
            </div>
        </section>

        <section class="surface p-6">
            <h2 class="font-bold mb-4"><?= e(__('Pricing')) ?></h2>
            <div class="grid sm:grid-cols-3 gap-4">
                <label class="field"><span class="label-text"><?= e(__('Price')) ?></span><input type="number" step="0.01" min="0" name="price" value="<?= $v('price') ?>" class="input w-full" required></label>
                <label class="field"><span class="label-text"><?= e(__('Compare-at price')) ?></span><input type="number" step="0.01" min="0" name="compare_price" value="<?= $v('compare_price') ?>" class="input w-full"></label>
                <label class="field"><span class="label-text"><?= e(__('Cost')) ?></span><input type="number" step="0.01" min="0" name="cost" value="<?= $v('cost') ?>" class="input w-full"></label>
            </div>
        </section>

        <section class="surface p-6">
            <h2 class="font-bold mb-4 flex items-center gap-2"><?= icon('boxes', 'size-5 text-primary') ?><?= e(__('Inventory')) ?></h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <label class="field"><span class="label-text">SKU</span><input name="sku" value="<?= $v('sku') ?>" class="input w-full font-mono" required>
                    <span class="text-xs opacity-60"><?= e(__('Must match the item code in your ERP for stock sync.')) ?></span></label>
                <label class="field"><span class="label-text"><?= e(__('Stock source')) ?></span>
                    <select name="stock_source" class="select w-full">
                        <option value="auto" <?= old('stock_source', $p['stock_source'] ?? 'auto') === 'auto' ? 'selected' : '' ?>><?= e(__('Follow inventory integration')) ?></option>
                        <option value="local" <?= old('stock_source', $p['stock_source'] ?? '') === 'local' ? 'selected' : '' ?>><?= e(__('Manage manually here')) ?></option>
                    </select></label>
                <?php if (!$product): ?>
                    <label class="field"><span class="label-text"><?= e(__('Initial stock')) ?></span><input type="number" name="stock" value="<?= $v('stock', 0) ?>" class="input w-full"></label>
                <?php endif; ?>
                <label class="field"><span class="label-text"><?= e(__('Low stock alert at')) ?></span><input type="number" min="0" name="low_stock_threshold" value="<?= $v('low_stock_threshold', 5) ?>" class="input w-full"></label>
                <label class="field"><span class="label-text"><?= e(__('Weight (kg)')) ?></span><input type="number" step="0.001" min="0" name="weight" value="<?= $v('weight') ?>" class="input w-full"></label>
                <label class="flex items-center gap-3 sm:col-span-2"><input type="checkbox" name="track_stock" value="1" class="toggle toggle-primary" <?= $checked('track_stock', true) ?>> <span><?= e(__('Track stock for this product')) ?></span></label>
            </div>
        </section>
    </div>

    <aside class="space-y-6">
        <section class="surface p-6 space-y-4">
            <label class="flex items-center justify-between"><span class="font-medium"><?= e(__('Visible in store')) ?></span><input type="checkbox" name="is_active" value="1" class="toggle toggle-success" <?= $checked('is_active', true) ?>></label>
            <label class="flex items-center justify-between"><span class="font-medium"><?= e(__('Featured')) ?></span><input type="checkbox" name="is_featured" value="1" class="toggle toggle-accent" <?= $checked('is_featured', false) ?>></label>
            <label class="field"><span class="label-text"><?= e(__('Category')) ?></span>
                <select name="category_id" class="select w-full">
                    <option value=""><?= e(__('None')) ?></option>
                    <?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>" <?= (string) old('category_id', $p['category_id'] ?? '') === (string) $c['id'] ? 'selected' : '' ?>><?= e(tr($c, 'name')) ?></option><?php endforeach; ?>
                </select></label>
        </section>
        <section class="surface p-6">
            <h2 class="font-bold mb-3"><?= e(__('Image')) ?></h2>
            <div class="aspect-square rounded-box placeholder-img overflow-hidden grid place-items-center">
                <img id="img-preview" src="<?= e(upload_url($p['image'] ?? null) ?? '') ?>" class="size-full object-cover <?= empty($p['image']) ? 'hidden' : '' ?>" alt="">
                <?php if (empty($p['image'])): ?><?= icon('image-plus', 'size-10 text-primary/50') ?><?php endif; ?>
            </div>
            <input type="file" name="image" accept="image/*" data-preview="#img-preview" class="file-input file-input-sm w-full mt-3">
            <?php if (!empty($p['image'])): ?><label class="flex items-center gap-2 text-sm mt-2"><input type="checkbox" name="remove_image" value="1" class="checkbox checkbox-xs"> <?= e(__('Remove image')) ?></label><?php endif; ?>
        </section>
        <button class="btn btn-primary w-full rounded-full shadow-lg shadow-primary/25"><?= icon('save', 'size-4') ?> <?= e(__('Save product')) ?></button>
    </aside>
</form>

<?php if ($product): ?>
<div class="grid xl:grid-cols-[1fr_22rem] gap-6 mt-6">
    <section class="surface overflow-hidden">
        <div class="p-5 font-bold flex items-center gap-2"><?= icon('history', 'size-5 text-primary') ?><?= e(__('Stock movements')) ?></div>
        <table class="table table-modern table-sm">
            <tbody>
            <?php foreach ($logs as $l): ?>
                <tr>
                    <td><span class="font-bold <?= $l['change_qty'] >= 0 ? 'text-success' : 'text-error' ?>"><?= $l['change_qty'] >= 0 ? '+' : '' ?><?= (int) $l['change_qty'] ?></span></td>
                    <td><?= e(__('Balance')) ?>: <b><?= (int) $l['balance'] ?></b></td>
                    <td class="text-sm opacity-70"><?= e($l['reason']) ?></td>
                    <td><span class="badge badge-ghost badge-sm"><?= e($l['source']) ?></span></td>
                    <td class="text-xs opacity-50"><?= e(format_date($l['created_at'], true)) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$logs): ?><tr><td class="opacity-50"><?= e(__('No movements yet.')) ?></td></tr><?php endif; ?>
            </tbody>
        </table>
    </section>
    <aside class="space-y-4">
        <form method="post" action="<?= e(url('/admin/products/' . $product['id'] . '/stock')) ?>" class="surface p-6 space-y-3" x-data="{ mode: 'set' }">
            <?= csrf_field() ?>
            <div class="flex items-center justify-between">
                <h2 class="font-bold"><?= e(__('Adjust stock')) ?></h2>
                <span class="badge badge-lg badge-primary font-black"><?= (int) $product['stock'] ?></span>
            </div>
            <div class="join w-full">
                <label class="join-item btn btn-sm flex-1" :class="mode==='set' && 'btn-primary'"><input type="radio" name="mode" value="set" x-model="mode" class="hidden"><?= e(__('Set to')) ?></label>
                <label class="join-item btn btn-sm flex-1" :class="mode==='adjust' && 'btn-primary'"><input type="radio" name="mode" value="adjust" x-model="mode" class="hidden"><?= e(__('Add / remove')) ?></label>
            </div>
            <input type="number" name="qty" class="input w-full" required placeholder="0">
            <input name="reason" class="input w-full" placeholder="<?= e(__('Reason (optional)')) ?>">
            <button class="btn btn-sm w-full rounded-full"><?= e(__('Update stock')) ?></button>
        </form>
        <form method="post" action="<?= e(url('/admin/products/' . $product['id'] . '/delete')) ?>" data-confirm="<?= e(__('Delete this product permanently?')) ?>">
            <?= csrf_field() ?>
            <button class="btn btn-ghost btn-sm w-full text-error rounded-full"><?= icon('trash-2', 'size-4') ?> <?= e(__('Delete product')) ?></button>
        </form>
    </aside>
</div>
<?php endif; ?>
