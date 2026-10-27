<?php
$user = auth();
$tax = round($cart['subtotal'] * $taxRate / 100, 2);
?>
<div class="max-w-6xl mx-auto px-4 py-10" x-data="checkout()">
    <h1 class="text-3xl font-black flex items-center gap-3"><?= icon('lock', 'size-7 text-primary') ?><?= e(__('Checkout')) ?></h1>
    <?php if (!$user): ?>
        <div class="alert alert-info alert-soft mt-6">
            <?= icon('user-round', 'size-5') ?>
            <span><?= e(__('Have an account?')) ?> <a class="link font-semibold" href="<?= e(url('/login')) ?>?next=/checkout"><?= e(__('Sign in')) ?></a> <?= e(__('to use saved addresses and track orders.')) ?></span>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('/checkout')) ?>" class="grid lg:grid-cols-[1fr_24rem] gap-8 mt-6">
        <?= csrf_field() ?>
        <div class="space-y-6">
            <section class="surface p-6">
                <h2 class="font-bold text-lg flex items-center gap-2"><span class="badge badge-primary">1</span> <?= e(__('Delivery address')) ?></h2>

                <?php if ($addresses): ?>
                    <div class="grid sm:grid-cols-2 gap-3 mt-4">
                        <?php foreach ($addresses as $a): ?>
                            <label class="cursor-pointer rounded-box border-2 p-4 transition" :class="addressId == '<?= (int) $a['id'] ?>' ? 'border-primary bg-primary/5' : 'border-base-content/10'">
                                <input type="radio" name="address_id" value="<?= (int) $a['id'] ?>" x-model="addressId" class="hidden" @change="quote(<?= e(js($a['city'])) ?>)">
                                <div class="font-semibold flex items-center gap-2"><?= icon('map-pin', 'size-4 text-primary') ?><?= e($a['label'] ?: $a['full_name']) ?></div>
                                <div class="text-sm opacity-70 mt-1"><?= e($a['street']) ?>, <?= e($a['city']) ?></div>
                                <div class="text-xs opacity-50" dir="ltr"><?= e($a['phone']) ?></div>
                            </label>
                        <?php endforeach; ?>
                        <label class="cursor-pointer rounded-box border-2 border-dashed p-4 grid place-items-center transition" :class="addressId == '0' ? 'border-primary bg-primary/5' : 'border-base-content/15'">
                            <input type="radio" name="address_id" value="0" x-model="addressId" class="hidden">
                            <span class="font-semibold flex items-center gap-2"><?= icon('plus', 'size-4') ?><?= e(__('New address')) ?></span>
                        </label>
                    </div>
                <?php endif; ?>

                <div class="grid sm:grid-cols-2 gap-4 mt-4" x-show="addressId == '0'" x-cloak>
                    <label class="field"><span class="label-text"><?= e(__('Full name')) ?></span><input name="name" class="input w-full" value="<?= e(old('name', $user['name'] ?? '')) ?>" :required="addressId == '0'"></label>
                    <label class="field"><span class="label-text"><?= e(__('Phone')) ?></span><input name="phone" dir="ltr" class="input w-full" value="<?= e(old('phone', $user['phone'] ?? '')) ?>" :required="addressId == '0'"></label>
                    <?php if (!$user): ?>
                        <label class="field sm:col-span-2"><span class="label-text"><?= e(__('Email')) ?></span><input name="email" type="email" class="input w-full" value="<?= e(old('email')) ?>" required></label>
                    <?php endif; ?>
                    <label class="field"><span class="label-text"><?= e(__('City')) ?></span>
                        <input name="city" class="input w-full" value="<?= e(old('city')) ?>" list="zone-cities" @change="quote($event.target.value)" @blur="quote($event.target.value)" :required="addressId == '0'">
                    </label>
                    <label class="field"><span class="label-text"><?= e(__('Area / district')) ?></span><input name="area" class="input w-full" value="<?= e(old('area')) ?>"></label>
                    <label class="field sm:col-span-2"><span class="label-text"><?= e(__('Street address')) ?></span><input name="street" class="input w-full" value="<?= e(old('street')) ?>" :required="addressId == '0'"></label>
                    <label class="field"><span class="label-text"><?= e(__('Building / apartment')) ?></span><input name="building" class="input w-full" value="<?= e(old('building')) ?>"></label>
                    <label class="field"><span class="label-text"><?= e(__('Delivery notes')) ?></span><input name="notes" class="input w-full" value="<?= e(old('notes')) ?>"></label>
                    <input type="hidden" name="lat" x-model="lat"><input type="hidden" name="lng" x-model="lng">
                    <div class="sm:col-span-2 flex flex-wrap items-center gap-3">
                        <button type="button" class="btn btn-sm rounded-full" @click="locate()"><?= icon('locate-fixed', 'size-4') ?> <?= e(__('Use my location')) ?></button>
                        <span class="text-xs text-success" x-show="lat" x-cloak><?= icon('check', 'size-3 inline') ?> <?= e(__('Location pinned for the driver')) ?></span>
                        <?php if ($user): ?>
                            <label class="flex items-center gap-2 ms-auto text-sm"><input type="checkbox" name="save_address" value="1" class="checkbox checkbox-sm checkbox-primary" checked> <?= e(__('Save this address')) ?></label>
                        <?php endif; ?>
                    </div>
                </div>
                <datalist id="zone-cities">
                    <?php foreach ($zones as $z): foreach (array_filter(array_map('trim', preg_split('/[,\n،]+/u', (string) $z['cities']) ?: [])) as $city): ?>
                        <option value="<?= e($city) ?>">
                    <?php endforeach; endforeach; ?>
                </datalist>
            </section>

            <section class="surface p-6">
                <h2 class="font-bold text-lg flex items-center gap-2"><span class="badge badge-primary">2</span> <?= e(__('Payment')) ?></h2>
                <div class="grid sm:grid-cols-2 gap-3 mt-4">
                    <?php $first = true; foreach ($methods as $key => $m): ?>
                        <label class="cursor-pointer rounded-box border-2 p-4 flex items-center gap-3 transition has-[:checked]:border-primary has-[:checked]:bg-primary/5 border-base-content/10">
                            <input type="radio" name="payment_method" value="<?= e($key) ?>" class="radio radio-primary radio-sm" <?= $first ? 'checked' : '' ?>>
                            <span class="size-10 rounded-xl bg-primary/10 text-primary grid place-items-center"><?= icon($m['icon']) ?></span>
                            <span class="font-semibold"><?= e($m['label']) ?></span>
                        </label>
                    <?php $first = false; endforeach; ?>
                </div>
            </section>
        </div>

        <aside class="surface p-6 h-fit lg:sticky lg:top-24">
            <h2 class="font-bold text-lg"><?= e(__('Order summary')) ?></h2>
            <ul class="mt-4 space-y-3 max-h-64 overflow-y-auto">
                <?php foreach ($cart['lines'] as $line): ?>
                    <li class="flex items-center gap-3">
                        <span class="relative size-12 rounded-lg placeholder-img grid place-items-center shrink-0 overflow-hidden">
                            <?php if ($line['product']['image']): ?><img src="<?= e(upload_url($line['product']['image'])) ?>" class="size-full object-cover" alt=""><?php else: ?><?= icon('package', 'size-5 text-primary/60') ?><?php endif; ?>
                            <span class="absolute -top-1 -end-1 badge badge-xs badge-neutral"><?= (int) $line['qty'] ?></span>
                        </span>
                        <span class="flex-1 text-sm line-clamp-2"><?= e(tr($line['product'], 'name')) ?></span>
                        <span class="text-sm font-semibold"><?= e(money($line['total'])) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="divider my-3"></div>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span class="opacity-70"><?= e(__('Subtotal')) ?></span><span><?= e(money($cart['subtotal'])) ?></span></div>
                <div class="flex justify-between"><span class="opacity-70"><?= e(__('Shipping')) ?> <span class="text-xs" x-text="zone ? '(' + zone + ')' : ''"></span></span><span x-text="shipping"><?= e(__('Enter city')) ?></span></div>
                <?php if ($taxRate > 0): ?><div class="flex justify-between"><span class="opacity-70"><?= e(__('Tax')) ?> (<?= e($taxRate) ?>%)</span><span><?= e(money($tax)) ?></span></div><?php endif; ?>
                <div class="text-xs text-success" x-show="eta" x-cloak><?= icon('clock', 'size-3 inline') ?> <?= e(__('Estimated delivery')) ?>: <span x-text="eta"></span></div>
                <div class="text-xs text-error" x-show="notFound" x-cloak><?= e(__('We do not deliver to this city yet.')) ?></div>
            </div>
            <div class="divider my-3"></div>
            <div class="flex justify-between text-xl font-black"><span><?= e(__('Total')) ?></span><span class="text-brand" x-text="total"><?= e(money($cart['subtotal'] + $tax)) ?></span></div>
            <button class="btn btn-primary w-full rounded-full mt-6 shadow-xl shadow-primary/30"><?= icon('circle-check', 'size-5') ?> <?= e(__('Place order')) ?></button>
            <p class="text-xs opacity-50 text-center mt-3 flex items-center justify-center gap-1"><?= icon('refresh-cw', 'size-3') ?> <?= e(__('Stock is verified live when you place the order.')) ?></p>
        </aside>
    </form>
</div>
<script>
function checkout() {
    return {
        addressId: '<?= $addresses ? (int) $addresses[0]['id'] : 0 ?>',
        zone: '', shipping: <?= js(__('Enter city')) ?>, total: <?= js(money($cart['subtotal'] + $tax)) ?>,
        eta: '', notFound: false, lat: '', lng: '',
        init() { <?php if ($addresses): ?>this.quote(<?= js($addresses[0]['city']) ?>);<?php endif; ?> },
        async quote(city) {
            if (!city) return;
            const res = await fetch(<?= js(url('/checkout/zone')) ?> + '?city=' + encodeURIComponent(city), { headers: { Accept: 'application/json' } });
            const d = await res.json();
            this.zone = d.zone || ''; this.shipping = d.shipping; this.total = d.total; this.eta = d.eta || ''; this.notFound = !d.found;
        },
        locate() {
            if (!navigator.geolocation) return toast(<?= js(__('Location is not available on this device.')) ?>, 'error');
            navigator.geolocation.getCurrentPosition(p => { this.lat = p.coords.latitude.toFixed(6); this.lng = p.coords.longitude.toFixed(6); },
                () => toast(<?= js(__('Could not get your location.')) ?>, 'warning'));
        },
    };
}
</script>
