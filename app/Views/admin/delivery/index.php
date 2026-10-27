<?php
use App\Core\View;

$driverIcons = ['inhouse' => 'bike', 'manual' => 'package-search', 'api' => 'cloud-cog'];
?>
<div x-data="delivery()">
    <!-- Zones -->
    <section>
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="font-bold text-lg flex items-center gap-2"><?= icon('map', 'size-5 text-primary') ?><?= e(__('Shipping zones & rates')) ?></h2>
                <p class="text-sm opacity-60"><?= e(__('Match the customer city to a zone. A zone without cities covers everywhere else.')) ?></p>
            </div>
            <button class="btn btn-primary btn-sm rounded-full" @click="openZone()"><?= icon('plus', 'size-4') ?> <?= e(__('Add zone')) ?></button>
        </div>
        <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
            <?php foreach ($zones as $z): ?>
                <div class="surface p-5 <?= $z['is_active'] ? '' : 'opacity-60' ?>">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="font-bold"><?= e(tr($z, 'name')) ?></div>
                            <div class="text-xs opacity-60 mt-1 line-clamp-2"><?= $z['cities'] ? e($z['cities']) : e(__('All other cities')) ?></div>
                        </div>
                        <div class="flex">
                            <button class="btn btn-ghost btn-xs btn-circle" @click="openZone(<?= e(js($z)) ?>)"><?= icon('pencil', 'size-3.5') ?></button>
                            <form method="post" action="<?= e(url('/admin/delivery/zones/' . $z['id'] . '/delete')) ?>" data-confirm="<?= e(__('Delete this zone?')) ?>"><?= csrf_field() ?><button class="btn btn-ghost btn-xs btn-circle text-error"><?= icon('trash-2', 'size-3.5') ?></button></form>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 mt-4">
                        <span class="badge badge-primary badge-soft"><?= e(money($z['rate'])) ?></span>
                        <?php if ($z['free_over'] !== null): ?><span class="badge badge-success badge-soft"><?= e(__('Free over :amount', ['amount' => money($z['free_over'])])) ?></span><?php endif; ?>
                        <span class="badge badge-ghost"><?= icon('clock', 'size-3') ?> <?= e(__(':min-:max days', ['min' => $z['eta_min_days'], 'max' => $z['eta_max_days']])) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (!$zones): ?><div class="surface"><?= View::partial('partials/empty', ['icon' => 'map', 'title' => __('No zones: shipping is free everywhere')]) ?></div><?php endif; ?>
    </section>

    <!-- Couriers -->
    <section class="mt-10">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="font-bold text-lg flex items-center gap-2"><?= icon('truck', 'size-5 text-primary') ?><?= e(__('Couriers')) ?></h2>
                <p class="text-sm opacity-60"><?= e(__('Your own fleet, third-party couriers, or any courier API.')) ?></p>
            </div>
            <button class="btn btn-primary btn-sm rounded-full" @click="openCourier()"><?= icon('plus', 'size-4') ?> <?= e(__('Add courier')) ?></button>
        </div>
        <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
            <?php foreach ($couriers as $c): ?>
                <div class="surface p-5 flex items-center gap-4 <?= $c['is_active'] ? '' : 'opacity-60' ?>">
                    <span class="size-12 rounded-2xl bg-brand grid place-items-center"><?= icon($driverIcons[$c['driver']] ?? 'truck', 'size-6') ?></span>
                    <div class="flex-1 min-w-0">
                        <div class="font-bold truncate"><?= e($c['name']) ?></div>
                        <div class="text-xs opacity-60"><?= e(__('driver.' . $c['driver'])) ?> · <?= e(__(':n active', ['n' => $c['active_count']])) ?></div>
                    </div>
                    <button class="btn btn-ghost btn-xs btn-circle" @click="openCourier(<?= e(js(['id' => $c['id'], 'name' => $c['name'], 'driver' => $c['driver'], 'tracking_url_template' => $c['tracking_url_template'], 'is_active' => (bool) $c['is_active'], 'cfg' => $c['config_values']])) ?>)"><?= icon('pencil', 'size-3.5') ?></button>
                    <form method="post" action="<?= e(url('/admin/delivery/couriers/' . $c['id'] . '/delete')) ?>" data-confirm="<?= e(__('Delete this courier?')) ?>"><?= csrf_field() ?><button class="btn btn-ghost btn-xs btn-circle text-error"><?= icon('trash-2', 'size-3.5') ?></button></form>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Drivers -->
    <section class="mt-10">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold text-lg flex items-center gap-2"><?= icon('bike', 'size-5 text-primary') ?><?= e(__('Drivers')) ?></h2>
            <a href="<?= e(url('/admin/staff')) ?>" class="btn btn-sm rounded-full"><?= icon('user-plus', 'size-4') ?> <?= e(__('Manage team')) ?></a>
        </div>
        <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <?php foreach ($driverUsers as $d): ?>
                <div class="surface p-4 flex items-center gap-3">
                    <span class="avatar avatar-placeholder"><span class="w-10 rounded-full bg-primary/10 text-primary font-bold text-sm"><?= e(initials($d['name'])) ?></span></span>
                    <div class="flex-1"><div class="font-semibold"><?= e($d['name']) ?></div><div class="text-xs opacity-60"><?= e(__(':n active deliveries', ['n' => $d['active_count']])) ?></div></div>
                    <?= status_badge($d['status']) ?>
                </div>
            <?php endforeach; ?>
            <?php if (!$driverUsers): ?><p class="text-sm opacity-60"><?= e(__('No drivers yet. Add a team member with the Driver role.')) ?></p><?php endif; ?>
        </div>
    </section>

    <!-- Zone modal -->
    <dialog x-ref="zone" class="modal">
        <div class="modal-box max-w-2xl">
            <h3 class="font-bold text-lg mb-4"><?= e(__('Shipping zone')) ?></h3>
            <form method="post" action="<?= e(url('/admin/delivery/zones')) ?>" class="grid sm:grid-cols-2 gap-4">
                <?= csrf_field() ?>
                <input type="hidden" name="id" :value="zone.id">
                <label class="field"><span class="label-text"><?= e(__('Name')) ?> (EN)</span><input name="name_en" x-model="zone.name_en" class="input w-full" required></label>
                <label class="field"><span class="label-text"><?= e(__('Name')) ?> (AR)</span><input name="name_ar" x-model="zone.name_ar" dir="rtl" class="input w-full"></label>
                <label class="field sm:col-span-2"><span class="label-text"><?= e(__('Cities (comma separated, both languages)')) ?></span><textarea name="cities" x-model="zone.cities" rows="2" class="textarea w-full" placeholder="Riyadh, الرياض, Jeddah, جدة"></textarea></label>
                <label class="field"><span class="label-text"><?= e(__('Rate')) ?></span><input type="number" step="0.01" min="0" name="rate" x-model="zone.rate" class="input w-full" required></label>
                <label class="field"><span class="label-text"><?= e(__('Free shipping over')) ?></span><input type="number" step="0.01" min="0" name="free_over" x-model="zone.free_over" class="input w-full"></label>
                <label class="field"><span class="label-text"><?= e(__('Min days')) ?></span><input type="number" min="0" name="eta_min_days" x-model="zone.eta_min_days" class="input w-full" required></label>
                <label class="field"><span class="label-text"><?= e(__('Max days')) ?></span><input type="number" min="0" name="eta_max_days" x-model="zone.eta_max_days" class="input w-full" required></label>
                <label class="field"><span class="label-text"><?= e(__('Sort order')) ?></span><input type="number" name="sort" x-model="zone.sort" class="input w-full"></label>
                <label class="flex items-center gap-2 mt-6"><input type="checkbox" name="is_active" value="1" x-model="zone.is_active" class="toggle toggle-success toggle-sm"> <?= e(__('Active')) ?></label>
                <div class="modal-action sm:col-span-2"><button type="button" class="btn btn-ghost" onclick="this.closest('dialog').close()"><?= e(__('Cancel')) ?></button><button class="btn btn-primary"><?= e(__('Save')) ?></button></div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>

    <!-- Courier modal -->
    <dialog x-ref="courier" class="modal">
        <div class="modal-box max-w-3xl">
            <h3 class="font-bold text-lg mb-4"><?= e(__('Courier')) ?></h3>
            <form method="post" action="<?= e(url('/admin/delivery/couriers')) ?>" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="id" :value="courier.id">
                <div class="grid sm:grid-cols-2 gap-4">
                    <label class="field"><span class="label-text"><?= e(__('Name')) ?></span><input name="name" x-model="courier.name" class="input w-full" required></label>
                    <label class="field"><span class="label-text"><?= e(__('Type')) ?></span>
                        <select name="driver" x-model="courier.driver" class="select w-full">
                            <?php foreach ($drivers as $key => $d): ?><option value="<?= e($key) ?>"><?= e(__($d['label'])) ?></option><?php endforeach; ?>
                        </select></label>
                    <label class="field sm:col-span-2"><span class="label-text"><?= e(__('Public tracking URL template')) ?></span>
                        <input name="tracking_url_template" x-model="courier.tracking_url_template" dir="ltr" class="input w-full" placeholder="https://courier.com/track?n={tracking}"></label>
                </div>
                <?php foreach ($drivers as $key => $d): ?>
                    <template x-if="courier.driver === '<?= $key ?>'">
                        <div>
                            <p class="text-sm opacity-60 mb-3"><?= e(__($d['description'])) ?></p>
                            <?php if ($d['fields']): ?>
                                <div x-effect="$nextTick(() => fill($el))"><?= View::partial('partials/config_fields', ['fields' => $d['fields'], 'values' => []]) ?></div>
                            <?php endif; ?>
                        </div>
                    </template>
                <?php endforeach; ?>
                <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" x-model="courier.is_active" class="toggle toggle-success toggle-sm"> <?= e(__('Active')) ?></label>
                <div class="modal-action"><button type="button" class="btn btn-ghost" onclick="this.closest('dialog').close()"><?= e(__('Cancel')) ?></button><button class="btn btn-primary"><?= e(__('Save')) ?></button></div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>
</div>

<?php push_start(); ?>
<script>
function delivery() {
    return {
        zone: {}, courier: { driver: 'inhouse', cfg: {} },
        openZone(z) {
            this.zone = Object.assign({ id: '', name_en: '', name_ar: '', cities: '', rate: 0, free_over: '', eta_min_days: 1, eta_max_days: 3, sort: 0, is_active: true }, z || {});
            this.zone.is_active = !!Number(this.zone.is_active ?? 1);
            this.$refs.zone.showModal();
        },
        openCourier(c) {
            this.courier = Object.assign({ id: '', name: '', driver: 'inhouse', tracking_url_template: '', is_active: true, cfg: {} }, c || {});
            this.$refs.courier.showModal();
        },
        // Prefill dynamic config fields with stored (non-secret) values.
        fill(el) {
            const cfg = this.courier.cfg || {};
            el.querySelectorAll('[name^="cfg["]').forEach(input => {
                const key = input.name.slice(4, -1);
                if (cfg[key] !== undefined && cfg[key] !== '' && input.type !== 'password') input.value = cfg[key];
            });
        },
    };
}
</script>
<?php push_end('scripts'); ?>
