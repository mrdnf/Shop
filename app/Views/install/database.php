<?php $v = static fn (string $k, string $d = '') => e(old($k, $db[$k] ?? $d)); ?>
<h1 class="text-2xl font-extrabold"><?= e(__('MySQL connection')) ?></h1>
<p class="mt-1 opacity-70"><?= e(__('Use an account that can create databases: every shop gets its own database.')) ?></p>

<form method="post" action="<?= e(base_url('/install/database')) ?>" class="mt-8 grid md:grid-cols-2 gap-4">
    <?= csrf_field() ?>
    <label class="field md:col-span-1"><span class="label-text"><?= e(__('Host')) ?></span>
        <input name="host" class="input w-full" value="<?= $v('host', '127.0.0.1') ?>" required></label>
    <label class="field"><span class="label-text"><?= e(__('Port')) ?></span>
        <input name="port" type="number" class="input w-full" value="<?= $v('port', '3306') ?>" required></label>
    <label class="field"><span class="label-text"><?= e(__('Username')) ?></span>
        <input name="user" class="input w-full" value="<?= $v('user', 'root') ?>" required autocomplete="off"></label>
    <label class="field"><span class="label-text"><?= e(__('Password')) ?></span>
        <input name="pass" type="password" class="input w-full" autocomplete="new-password"></label>
    <label class="field"><span class="label-text"><?= e(__('Platform database name')) ?></span>
        <input name="platform_db" class="input w-full font-mono" value="<?= $v('platform_db', 'matjar_platform') ?>" required>
        <span class="text-xs opacity-60"><?= e(__('Central database: shops, plans, platform admins.')) ?></span></label>
    <label class="field"><span class="label-text"><?= e(__('Shop database prefix')) ?></span>
        <input name="tenant_prefix" class="input w-full font-mono" value="<?= $v('tenant_prefix', 'matjar_shop_') ?>" required>
        <span class="text-xs opacity-60"><?= e(__('Each shop database is named prefix + shop address.')) ?></span></label>
    <label class="md:col-span-2 flex items-start gap-3 rounded-box border border-base-content/10 p-4 cursor-pointer">
        <input type="checkbox" name="per_tenant_user" value="1" class="toggle toggle-primary mt-0.5" <?= old('per_tenant_user', !empty($db['per_tenant_user']) ? '1' : '') ? 'checked' : '' ?>>
        <span>
            <span class="font-semibold"><?= e(__('Create a dedicated MySQL user per shop')) ?></span>
            <span class="block text-sm opacity-70"><?= e(__('Strongest isolation. Requires CREATE USER and GRANT OPTION privileges.')) ?></span>
        </span>
    </label>
    <div class="md:col-span-2 flex justify-between mt-4">
        <a href="<?= e(base_url('/install')) ?>" class="btn btn-ghost rounded-full"><?= icon('arrow-left', 'size-4 flip-rtl') ?> <?= e(__('Back')) ?></a>
        <button class="btn btn-primary rounded-full px-8"><?= icon('plug-zap', 'size-4') ?> <?= e(__('Test & continue')) ?></button>
    </div>
</form>
