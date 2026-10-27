<?php use App\Core\View; ?>
<div x-data="{ form: {} , open(a) { this.form = Object.assign({ id: '', label: '', full_name: '', phone: '', country: '', city: '', area: '', street: '', building: '', notes: '', is_default: false }, a || {}); $refs.dlg.showModal(); } }">
    <div class="flex justify-end mb-4">
        <button class="btn btn-primary btn-sm rounded-full" @click="open()"><?= icon('plus', 'size-4') ?> <?= e(__('Add address')) ?></button>
    </div>
    <?php if (!$addresses): ?>
        <div class="surface"><?= View::partial('partials/empty', ['icon' => 'map-pin', 'title' => __('No saved addresses'), 'text' => __('Save an address for faster checkout.')]) ?></div>
    <?php else: ?>
        <div class="grid md:grid-cols-2 gap-4">
            <?php foreach ($addresses as $a): ?>
                <div class="surface p-5 <?= $a['is_default'] ? 'ring-2 ring-primary' : '' ?>">
                    <div class="flex items-start justify-between">
                        <div class="font-bold flex items-center gap-2"><?= icon('map-pin', 'size-4 text-primary') ?><?= e($a['label'] ?: __('Address')) ?>
                            <?php if ($a['is_default']): ?><span class="badge badge-primary badge-sm"><?= e(__('Default')) ?></span><?php endif; ?></div>
                        <div class="flex gap-1">
                            <button class="btn btn-ghost btn-xs btn-circle" @click="open(<?= e(js(array_merge($a, ['is_default' => (bool) $a['is_default']]))) ?>)"><?= icon('pencil', 'size-3.5') ?></button>
                            <form method="post" action="<?= e(url('/account/addresses/' . $a['id'] . '/delete')) ?>" data-confirm="<?= e(__('Delete this address?')) ?>">
                                <?= csrf_field() ?><button class="btn btn-ghost btn-xs btn-circle text-error"><?= icon('trash-2', 'size-3.5') ?></button>
                            </form>
                        </div>
                    </div>
                    <div class="mt-2 text-sm space-y-0.5">
                        <div><?= e($a['full_name']) ?> · <span dir="ltr"><?= e($a['phone']) ?></span></div>
                        <div class="opacity-70"><?= e($a['street']) ?><?= $a['building'] ? ', ' . e($a['building']) : '' ?></div>
                        <div class="opacity-70"><?= e(trim($a['city'] . ($a['area'] ? ', ' . $a['area'] : ''))) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <dialog x-ref="dlg" class="modal">
        <div class="modal-box max-w-2xl">
            <h3 class="font-bold text-lg mb-4"><?= e(__('Address')) ?></h3>
            <form method="post" action="<?= e(url('/account/addresses')) ?>" class="grid sm:grid-cols-2 gap-4">
                <?= csrf_field() ?>
                <input type="hidden" name="id" :value="form.id">
                <label class="field"><span class="label-text"><?= e(__('Label')) ?></span><input name="label" x-model="form.label" class="input w-full" placeholder="<?= e(__('Home, Work…')) ?>"></label>
                <label class="field"><span class="label-text"><?= e(__('Full name')) ?></span><input name="full_name" x-model="form.full_name" class="input w-full" required></label>
                <label class="field"><span class="label-text"><?= e(__('Phone')) ?></span><input name="phone" x-model="form.phone" dir="ltr" class="input w-full" required></label>
                <label class="field"><span class="label-text"><?= e(__('City')) ?></span><input name="city" x-model="form.city" class="input w-full" required></label>
                <label class="field"><span class="label-text"><?= e(__('Area / district')) ?></span><input name="area" x-model="form.area" class="input w-full"></label>
                <label class="field"><span class="label-text"><?= e(__('Building / apartment')) ?></span><input name="building" x-model="form.building" class="input w-full"></label>
                <label class="field sm:col-span-2"><span class="label-text"><?= e(__('Street address')) ?></span><input name="street" x-model="form.street" class="input w-full" required></label>
                <label class="field sm:col-span-2"><span class="label-text"><?= e(__('Delivery notes')) ?></span><input name="notes" x-model="form.notes" class="input w-full"></label>
                <label class="flex items-center gap-2 sm:col-span-2"><input type="checkbox" name="is_default" value="1" x-model="form.is_default" class="checkbox checkbox-primary checkbox-sm"> <?= e(__('Make default')) ?></label>
                <div class="modal-action sm:col-span-2">
                    <button type="button" class="btn btn-ghost" onclick="this.closest('dialog').close()"><?= e(__('Cancel')) ?></button>
                    <button class="btn btn-primary"><?= e(__('Save')) ?></button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>
</div>
