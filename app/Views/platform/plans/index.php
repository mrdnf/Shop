<div x-data="{ p: {}, open(plan) { this.p = Object.assign({ id: '', name: '', name_ar: '', price: 0, billing_cycle: 'monthly', max_products: '', max_staff: '', features: '', sort: 0, is_active: true, is_featured: false }, plan || {}); $refs.dlg.showModal(); } }">
    <div class="flex justify-end mb-5">
        <button class="btn btn-primary btn-sm rounded-full" @click="open()"><?= icon('plus', 'size-4') ?> <?= e(__('New plan')) ?></button>
    </div>
    <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-5">
        <?php foreach ($plans as $p): ?>
            <div class="surface p-6 flex flex-col <?= $p['is_featured'] ? 'ring-2 ring-primary' : '' ?> <?= $p['is_active'] ? '' : 'opacity-60' ?>">
                <div class="flex items-start justify-between">
                    <div><div class="font-bold text-lg"><?= e($p['name']) ?></div><div class="text-xs opacity-60"><?= e(__(':n shops', ['n' => $p['shops']])) ?></div></div>
                    <button class="btn btn-ghost btn-xs btn-circle" @click="open(<?= e(js(array_merge($p, ['is_active' => (bool) $p['is_active'], 'is_featured' => (bool) $p['is_featured']]))) ?>)"><?= icon('pencil', 'size-4') ?></button>
                </div>
                <div class="text-4xl font-black mt-4">$<?= e((float) $p['price']) ?><span class="text-sm font-normal opacity-60"> / <?= e(__($p['billing_cycle'] === 'yearly' ? 'year' : 'month')) ?></span></div>
                <div class="flex gap-2 mt-3 text-xs">
                    <span class="badge badge-ghost"><?= icon('package', 'size-3') ?> <?= $p['max_products'] === null ? '∞' : (int) $p['max_products'] ?></span>
                    <span class="badge badge-ghost"><?= icon('users', 'size-3') ?> <?= $p['max_staff'] === null ? '∞' : (int) $p['max_staff'] ?></span>
                </div>
                <ul class="mt-4 space-y-1 text-sm flex-1">
                    <?php foreach (array_filter(preg_split('/\r?\n/', (string) $p['features']) ?: []) as $f): ?><li class="flex gap-2"><?= icon('check', 'size-4 text-success') ?><?= e($f) ?></li><?php endforeach; ?>
                </ul>
                <form method="post" action="<?= e(base_url('/platform/plans/' . $p['id'] . '/delete')) ?>" data-confirm="<?= e(__('Delete this plan? Shops on it will have no plan.')) ?>" class="mt-4">
                    <?= csrf_field() ?><button class="btn btn-ghost btn-xs text-error"><?= icon('trash-2', 'size-3.5') ?> <?= e(__('Delete')) ?></button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

    <dialog x-ref="dlg" class="modal">
        <div class="modal-box max-w-2xl">
            <h3 class="font-bold text-lg mb-4"><?= e(__('Plan')) ?></h3>
            <form method="post" action="<?= e(base_url('/platform/plans')) ?>" class="grid sm:grid-cols-2 gap-4">
                <?= csrf_field() ?>
                <input type="hidden" name="id" :value="p.id">
                <label class="field"><span class="label-text"><?= e(__('Name')) ?> (EN)</span><input name="name" x-model="p.name" class="input w-full" required></label>
                <label class="field"><span class="label-text"><?= e(__('Name')) ?> (AR)</span><input name="name_ar" x-model="p.name_ar" dir="rtl" class="input w-full"></label>
                <label class="field"><span class="label-text"><?= e(__('Price')) ?></span><input type="number" step="0.01" min="0" name="price" x-model="p.price" class="input w-full" required></label>
                <label class="field"><span class="label-text"><?= e(__('Billing')) ?></span><select name="billing_cycle" x-model="p.billing_cycle" class="select w-full"><option value="monthly"><?= e(__('Monthly')) ?></option><option value="yearly"><?= e(__('Yearly')) ?></option></select></label>
                <label class="field"><span class="label-text"><?= e(__('Max products (empty = unlimited)')) ?></span><input type="number" min="0" name="max_products" x-model="p.max_products" class="input w-full"></label>
                <label class="field"><span class="label-text"><?= e(__('Max team members (empty = unlimited)')) ?></span><input type="number" min="0" name="max_staff" x-model="p.max_staff" class="input w-full"></label>
                <label class="field sm:col-span-2"><span class="label-text"><?= e(__('Features (one per line)')) ?></span><textarea name="features" x-model="p.features" rows="4" class="textarea w-full"></textarea></label>
                <label class="field"><span class="label-text"><?= e(__('Sort order')) ?></span><input type="number" name="sort" x-model="p.sort" class="input w-full"></label>
                <div class="flex flex-col gap-2 justify-end">
                    <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" x-model="p.is_active" class="toggle toggle-success toggle-sm"> <?= e(__('Active')) ?></label>
                    <label class="flex items-center gap-2"><input type="checkbox" name="is_featured" value="1" x-model="p.is_featured" class="toggle toggle-primary toggle-sm"> <?= e(__('Highlight')) ?></label>
                </div>
                <div class="modal-action sm:col-span-2"><button type="button" class="btn btn-ghost" onclick="this.closest('dialog').close()"><?= e(__('Cancel')) ?></button><button class="btn btn-primary"><?= e(__('Save')) ?></button></div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>
</div>
