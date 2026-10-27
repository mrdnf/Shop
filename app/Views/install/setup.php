<?php $multi = $mode === 'multi'; ?>
<div class="flex items-center gap-3">
    <span class="badge badge-primary badge-soft"><?= e($multi ? __('Multi shop (SaaS)') : __('One shop')) ?></span>
    <a href="<?= e(base_url('/install/mode')) ?>" class="link text-sm opacity-70"><?= e(__('Change')) ?></a>
</div>
<h1 class="text-2xl font-extrabold mt-3"><?= e($multi ? __('Platform & owner account') : __('Your company & admin account')) ?></h1>

<form method="post" action="<?= e(base_url('/install/setup')) ?>" class="mt-6 space-y-8" x-data="{ primary: '<?= e(old('primary', '#7c3aed')) ?>' }">
    <?= csrf_field() ?>
    <fieldset class="grid md:grid-cols-2 gap-4">
        <legend class="font-bold mb-3 flex items-center gap-2"><?= icon('building', 'size-4 text-primary') ?> <?= e($multi ? __('Platform brand') : __('Company')) ?></legend>
        <label class="field"><span class="label-text"><?= e(__('Name (English)')) ?></span>
            <input name="company_name" class="input w-full" value="<?= e(old('company_name')) ?>" required></label>
        <label class="field"><span class="label-text"><?= e(__('Name (Arabic)')) ?></span>
            <input name="company_name_ar" dir="rtl" class="input w-full" value="<?= e(old('company_name_ar')) ?>"></label>
        <label class="field"><span class="label-text"><?= e(__('Default language')) ?></span>
            <select name="locale" class="select w-full">
                <option value="en" <?= old('locale', locale()) === 'en' ? 'selected' : '' ?>>English</option>
                <option value="ar" <?= old('locale', locale()) === 'ar' ? 'selected' : '' ?>>العربية</option>
            </select></label>
        <div class="field"><span class="label-text"><?= e(__('Brand colour')) ?></span>
            <div class="flex items-center gap-2">
                <input type="color" name="primary" x-model="primary" class="h-10 w-14 rounded-field cursor-pointer border border-base-content/10">
                <?php foreach ($presets as $name => [$p]): ?>
                    <button type="button" title="<?= e($name) ?>" @click="primary='<?= $p ?>'" class="size-7 rounded-full ring-2 ring-offset-2 ring-offset-base-100" :class="primary==='<?= $p ?>' ? 'ring-base-content' : 'ring-transparent'" style="background:<?= $p ?>"></button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php if (!$multi): ?>
            <label class="field"><span class="label-text"><?= e(__('Currency code')) ?></span>
                <input name="currency" class="input w-full" value="<?= e(old('currency', 'USD')) ?>" placeholder="USD, SAR, EGP, AED"></label>
            <label class="field"><span class="label-text"><?= e(__('Currency symbol')) ?></span>
                <input name="currency_symbol" class="input w-full" value="<?= e(old('currency_symbol', '$')) ?>" placeholder="$, ر.س, ج.م"></label>
        <?php else: ?>
            <label class="field md:col-span-2"><span class="label-text"><?= e(__('Base domain for shop subdomains (optional)')) ?></span>
                <input name="base_domain" class="input w-full font-mono" value="<?= e(old('base_domain')) ?>" placeholder="myplatform.com">
                <span class="text-xs opacity-60"><?= e(__('With wildcard DNS, shops open at {shop}.myplatform.com. Without it shops use /s/{shop}.')) ?></span></label>
        <?php endif; ?>
    </fieldset>

    <fieldset class="grid md:grid-cols-2 gap-4">
        <legend class="font-bold mb-3 flex items-center gap-2"><?= icon('shield-check', 'size-4 text-primary') ?> <?= e($multi ? __('Platform owner (super admin)') : __('Shop administrator')) ?></legend>
        <label class="field"><span class="label-text"><?= e(__('Full name')) ?></span>
            <input name="admin_name" class="input w-full" value="<?= e(old('admin_name')) ?>" required></label>
        <label class="field"><span class="label-text"><?= e(__('Email')) ?></span>
            <input name="admin_email" type="email" class="input w-full" value="<?= e(old('admin_email')) ?>" required></label>
        <label class="field"><span class="label-text"><?= e(__('Password')) ?></span>
            <input name="admin_password" type="password" minlength="8" class="input w-full" required autocomplete="new-password"></label>
        <label class="field"><span class="label-text"><?= e(__('Confirm password')) ?></span>
            <input name="admin_password_confirmation" type="password" minlength="8" class="input w-full" required autocomplete="new-password"></label>
    </fieldset>

    <label class="flex items-start gap-3 rounded-box border border-base-content/10 p-4 cursor-pointer">
        <input type="checkbox" name="demo" value="1" class="toggle toggle-primary mt-0.5" checked>
        <span>
            <span class="font-semibold"><?= e($multi ? __('Create a demo shop with sample products') : __('Add sample products, zones and couriers')) ?></span>
            <span class="block text-sm opacity-70"><?= e(__('Great for exploring. You can delete them anytime.')) ?></span>
        </span>
    </label>

    <div class="flex justify-between">
        <a href="<?= e(base_url('/install/mode')) ?>" class="btn btn-ghost rounded-full"><?= icon('arrow-left', 'size-4 flip-rtl') ?> <?= e(__('Back')) ?></a>
        <button class="btn btn-primary rounded-full px-8 shadow-lg shadow-primary/30"><?= icon('rocket', 'size-4') ?> <?= e(__('Install now')) ?></button>
    </div>
</form>
