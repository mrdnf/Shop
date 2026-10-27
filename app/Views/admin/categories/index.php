<?php use App\Core\View; ?>
<div x-data="{ form: {}, action: '', open(c, action) { this.form = Object.assign({ name_en: '', name_ar: '', parent_id: '', icon: 'tag', sort: 0, is_active: true }, c || {}); this.action = action; $refs.dlg.showModal(); } }">
    <div class="flex justify-end mb-5">
        <button class="btn btn-primary btn-sm rounded-full shadow-lg shadow-primary/25" @click="open(null, <?= e(js(url('/admin/categories'))) ?>)"><?= icon('plus', 'size-4') ?> <?= e(__('New category')) ?></button>
    </div>

    <?php if ($categories): ?>
        <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4">
            <?php foreach ($categories as $c): ?>
                <div class="surface p-5 flex items-center gap-4 card-lift">
                    <span class="size-14 rounded-2xl <?= $c['is_active'] ? 'bg-brand' : 'bg-base-200' ?> grid place-items-center shrink-0 overflow-hidden">
                        <?php if ($c['image']): ?><img src="<?= e(upload_url($c['image'])) ?>" class="size-full object-cover" alt=""><?php else: ?><?= icon($c['icon'] ?: 'tag', 'size-7') ?><?php endif; ?>
                    </span>
                    <div class="flex-1 min-w-0">
                        <div class="font-bold truncate"><?= e($c['name_en']) ?> <?php if ($c['name_ar']): ?><span class="opacity-50 font-normal" dir="rtl">· <?= e($c['name_ar']) ?></span><?php endif; ?></div>
                        <div class="text-xs opacity-60"><?= e(__(':n products', ['n' => $c['products_count']])) ?><?= $c['parent_en'] ? ' · ' . e(__('in')) . ' ' . e($c['parent_en']) : '' ?></div>
                    </div>
                    <div class="flex">
                        <button class="btn btn-ghost btn-sm btn-circle" @click="open(<?= e(js(['name_en' => $c['name_en'], 'name_ar' => $c['name_ar'], 'parent_id' => (string) $c['parent_id'], 'icon' => $c['icon'], 'sort' => $c['sort'], 'is_active' => (bool) $c['is_active']])) ?>, <?= e(js(url('/admin/categories/' . $c['id']))) ?>)"><?= icon('pencil', 'size-4') ?></button>
                        <form method="post" action="<?= e(url('/admin/categories/' . $c['id'] . '/delete')) ?>" data-confirm="<?= e(__('Delete this category? Products will be kept.')) ?>">
                            <?= csrf_field() ?><button class="btn btn-ghost btn-sm btn-circle text-error"><?= icon('trash-2', 'size-4') ?></button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="surface"><?= View::partial('partials/empty', ['icon' => 'layers', 'title' => __('No categories yet')]) ?></div>
    <?php endif; ?>

    <dialog x-ref="dlg" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg mb-4"><?= e(__('Category')) ?></h3>
            <form method="post" :action="action" enctype="multipart/form-data" class="grid grid-cols-2 gap-4">
                <?= csrf_field() ?>
                <label class="field"><span class="label-text"><?= e(__('Name')) ?> (EN)</span><input name="name_en" x-model="form.name_en" class="input w-full" required></label>
                <label class="field"><span class="label-text"><?= e(__('Name')) ?> (AR)</span><input name="name_ar" x-model="form.name_ar" dir="rtl" class="input w-full"></label>
                <label class="field"><span class="label-text"><?= e(__('Parent')) ?></span>
                    <select name="parent_id" x-model="form.parent_id" class="select w-full">
                        <option value=""><?= e(__('None')) ?></option>
                        <?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>"><?= e($c['name_en']) ?></option><?php endforeach; ?>
                    </select></label>
                <label class="field"><span class="label-text"><?= e(__('Icon')) ?> <a class="link text-xs" href="https://lucide.dev/icons" target="_blank">lucide.dev</a></span>
                    <div class="flex gap-2 items-center"><input name="icon" x-model="form.icon" class="input w-full font-mono" placeholder="tag"></div></label>
                <label class="field"><span class="label-text"><?= e(__('Sort order')) ?></span><input type="number" name="sort" x-model="form.sort" class="input w-full"></label>
                <label class="field"><span class="label-text"><?= e(__('Image')) ?></span><input type="file" name="image" accept="image/*" class="file-input file-input-sm w-full"></label>
                <label class="flex items-center gap-2 col-span-2"><input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="toggle toggle-success toggle-sm"> <?= e(__('Visible in store')) ?></label>
                <div class="modal-action col-span-2">
                    <button type="button" class="btn btn-ghost" onclick="this.closest('dialog').close()"><?= e(__('Cancel')) ?></button>
                    <button class="btn btn-primary"><?= e(__('Save')) ?></button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>
</div>
