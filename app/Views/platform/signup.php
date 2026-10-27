<?php $base = App\Core\Config::get('base_domain') ?: (preg_replace('/:\d+$/', '', App\Core\Request::host()) . App\Core\Request::baseUrl() . '/s'); ?>
<div class="max-w-2xl mx-auto px-4 py-14" x-data="{ name: <?= e(js(old('shop_name'))) ?>, slug: <?= e(js(old('slug'))) ?>, touched: false,
    sync() { if (!this.touched) this.slug = this.name.toLowerCase().normalize('NFKD').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 40); } }">
    <div class="text-center">
        <span class="mx-auto size-16 rounded-2xl bg-brand grid place-items-center shadow-xl shadow-primary/30"><?= icon('store', 'size-8') ?></span>
        <h1 class="text-4xl font-black mt-5"><?= e(__('Create your shop')) ?></h1>
        <p class="opacity-70 mt-2"><?= e(__('Your shop gets its own database, admin panel and storefront.')) ?></p>
    </div>
    <form method="post" action="<?= e(base_url('/start')) ?>" class="surface p-8 mt-8 space-y-5 shadow-2xl shadow-primary/10">
        <?= csrf_field() ?>
        <label class="field"><span class="label-text"><?= e(__('Shop name')) ?></span><input name="shop_name" x-model="name" @input="sync()" class="input w-full" required></label>
        <label class="field"><span class="label-text"><?= e(__('Shop address')) ?></span>
            <label class="input w-full" dir="ltr"><span class="opacity-50 text-sm"><?= e(App\Core\Config::get('base_domain') ? '' : $base . '/') ?></span><input name="slug" x-model="slug" @input="touched = true" class="grow font-mono" required pattern="[a-z0-9]+(-[a-z0-9]+)*" minlength="3" maxlength="40"><?php if (App\Core\Config::get('base_domain')): ?><span class="opacity-50 text-sm">.<?= e($base) ?></span><?php endif; ?></label></label>
        <div class="grid sm:grid-cols-2 gap-4">
            <label class="field"><span class="label-text"><?= e(__('Your name')) ?></span><input name="owner_name" value="<?= e(old('owner_name')) ?>" class="input w-full" required></label>
            <label class="field"><span class="label-text"><?= e(__('Email')) ?></span><input type="email" name="owner_email" value="<?= e(old('owner_email')) ?>" class="input w-full" required></label>
            <label class="field"><span class="label-text"><?= e(__('Password')) ?></span><input type="password" name="password" minlength="8" class="input w-full" required autocomplete="new-password"></label>
            <label class="field"><span class="label-text"><?= e(__('Confirm password')) ?></span><input type="password" name="password_confirmation" minlength="8" class="input w-full" required autocomplete="new-password"></label>
        </div>
        <?php if ($plans): ?>
            <div class="grid sm:grid-cols-3 gap-3">
                <?php foreach ($plans as $p): ?>
                    <label class="cursor-pointer rounded-box border-2 p-4 transition has-[:checked]:border-primary has-[:checked]:bg-primary/5 border-base-content/10">
                        <input type="radio" name="plan_id" value="<?= (int) $p['id'] ?>" class="hidden" <?= (int) $p['id'] === $selected ? 'checked' : '' ?>>
                        <div class="font-bold"><?= e(locale() === 'ar' && $p['name_ar'] ? $p['name_ar'] : $p['name']) ?></div>
                        <div class="text-sm opacity-60"><?= (float) $p['price'] > 0 ? '$' . e((float) $p['price']) . ' / ' . e(__($p['billing_cycle'] === 'yearly' ? 'year' : 'month')) : e(__('Free')) ?></div>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="demo" value="1" class="checkbox checkbox-primary checkbox-sm" checked> <?= e(__('Start with sample products')) ?></label>
        <button class="btn btn-primary btn-block rounded-full shadow-xl shadow-primary/30"><?= icon('rocket', 'size-5') ?> <?= e(__('Create my shop')) ?></button>
        <p class="text-xs text-center opacity-60"><?= e(__('14-day trial. No credit card required.')) ?></p>
    </form>
</div>
