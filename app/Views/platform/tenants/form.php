<?php
$t = $tenant;
$v = static fn (string $k, string $d = '') => e(old($k, $t[$k] ?? $d));
?>
<div class="grid xl:grid-cols-[1fr_22rem] gap-6">
    <form method="post" action="<?= e($t ? base_url('/platform/shops/' . $t['id']) : base_url('/platform/shops')) ?>" class="space-y-6">
        <?= csrf_field() ?>
        <section class="surface p-6">
            <h2 class="font-bold text-lg flex items-center gap-2"><?= icon('store', 'size-5 text-primary') ?><?= e(__('Shop')) ?></h2>
            <div class="grid md:grid-cols-2 gap-4 mt-4">
                <label class="field"><span class="label-text"><?= e(__('Shop name')) ?></span><input name="name" value="<?= $v('name') ?>" class="input w-full" required></label>
                <label class="field"><span class="label-text"><?= e(__('Shop address (slug)')) ?></span>
                    <input name="slug" value="<?= $v('slug') ?>" class="input w-full font-mono" <?= $t ? 'disabled' : 'required pattern="[a-z0-9]+(-[a-z0-9]+)*"' ?> dir="ltr">
                    <?php if ($t): ?><span class="text-xs opacity-60"><?= e(__('The address is permanent: it names the shop database.')) ?></span><?php endif; ?></label>
                <label class="field"><span class="label-text"><?= e(__('Custom domain (optional)')) ?></span><input name="domain" value="<?= $v('domain') ?>" class="input w-full font-mono" placeholder="shop.example.com" dir="ltr">
                    <span class="text-xs opacity-60"><?= e(__('Point the domain DNS to this server.')) ?></span></label>
                <label class="field"><span class="label-text"><?= e(__('Plan')) ?></span>
                    <select name="plan_id" class="select w-full">
                        <option value=""><?= e(__('No plan (unlimited)')) ?></option>
                        <?php foreach ($plans as $p): ?><option value="<?= (int) $p['id'] ?>" <?= (string) old('plan_id', $t['plan_id'] ?? '') === (string) $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?> — $<?= e((float) $p['price']) ?></option><?php endforeach; ?>
                    </select></label>
                <label class="field"><span class="label-text"><?= e(__('Status')) ?></span>
                    <select name="status" class="select w-full">
                        <?php foreach (['active', 'trial', 'suspended'] as $s): ?><option value="<?= $s ?>" <?= old('status', $t['status'] ?? 'active') === $s ? 'selected' : '' ?>><?= e(__('status.' . $s)) ?></option><?php endforeach; ?>
                    </select></label>
                <?php if ($t): ?>
                    <label class="field"><span class="label-text"><?= e(__('Trial ends')) ?></span><input type="date" name="trial_ends_at" value="<?= $v('trial_ends_at') ?>" class="input w-full"></label>
                <?php else: ?>
                    <label class="field"><span class="label-text"><?= e(__('Default language')) ?></span>
                        <select name="locale" class="select w-full"><option value="en">English</option><option value="ar" <?= locale() === 'ar' ? 'selected' : '' ?>>العربية</option></select></label>
                <?php endif; ?>
            </div>
        </section>

        <?php if (!$t): ?>
            <section class="surface p-6">
                <h2 class="font-bold text-lg flex items-center gap-2"><?= icon('user-cog', 'size-5 text-primary') ?><?= e(__('Shop admin account')) ?></h2>
                <div class="grid md:grid-cols-3 gap-4 mt-4">
                    <label class="field"><span class="label-text"><?= e(__('Full name')) ?></span><input name="owner_name" value="<?= $v('owner_name') ?>" class="input w-full" required></label>
                    <label class="field"><span class="label-text"><?= e(__('Email')) ?></span><input type="email" name="owner_email" value="<?= $v('owner_email') ?>" class="input w-full" required></label>
                    <label class="field"><span class="label-text"><?= e(__('Password')) ?></span><input type="password" name="owner_password" minlength="8" class="input w-full" required autocomplete="new-password"></label>
                </div>
                <label class="flex items-center gap-2 mt-4 text-sm"><input type="checkbox" name="demo" value="1" class="checkbox checkbox-primary checkbox-sm"> <?= e(__('Add sample products')) ?></label>
            </section>
        <?php endif; ?>
        <button class="btn btn-primary rounded-full shadow-lg shadow-primary/25"><?= icon($t ? 'save' : 'database-zap', 'size-4') ?> <?= e($t ? __('Save shop') : __('Create shop & database')) ?></button>
    </form>

    <?php if ($t): ?>
        <aside class="space-y-4">
            <div class="surface p-5 space-y-3 text-sm">
                <a href="<?= e($t['url']) ?>" target="_blank" class="btn btn-block rounded-full"><?= icon('external-link', 'size-4') ?> <?= e(__('Open storefront')) ?></a>
                <a href="<?= e($t['url'] . '/admin') ?>" target="_blank" class="btn btn-block rounded-full"><?= icon('layout-dashboard', 'size-4') ?> <?= e(__('Open shop admin')) ?></a>
                <div class="divider my-1"></div>
                <div class="flex justify-between"><span class="opacity-60"><?= e(__('Database')) ?></span><span class="font-mono"><?= e($t['db_name']) ?></span></div>
                <div class="flex justify-between"><span class="opacity-60"><?= e(__('DB user')) ?></span><span class="font-mono"><?= e($t['db_user'] ?: __('shared')) ?></span></div>
                <div class="flex justify-between"><span class="opacity-60"><?= e(__('Owner')) ?></span><span><?= e($t['owner_email']) ?></span></div>
                <div class="flex justify-between"><span class="opacity-60"><?= e(__('Created')) ?></span><span><?= e(format_date($t['created_at'])) ?></span></div>
            </div>
            <form method="post" action="<?= e(base_url('/platform/shops/' . $t['id'] . '/delete')) ?>" class="surface p-5 space-y-3 border-error/30">
                <?= csrf_field() ?>
                <h3 class="font-bold text-error flex items-center gap-2"><?= icon('triangle-alert', 'size-4') ?><?= e(__('Danger zone')) ?></h3>
                <p class="text-xs opacity-70"><?= e(__('Deleting drops the shop database and uploaded files. This cannot be undone.')) ?></p>
                <input name="confirm" class="input input-sm w-full font-mono" placeholder="<?= e(__('Type :slug to confirm', ['slug' => $t['slug']])) ?>" dir="ltr">
                <button class="btn btn-error btn-sm btn-block rounded-full"><?= e(__('Delete shop')) ?></button>
            </form>
        </aside>
    <?php endif; ?>
</div>
