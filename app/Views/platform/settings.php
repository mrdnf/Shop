<?php
use App\Core\View;

ob_start(); ?>
<section class="surface p-6">
    <h2 class="font-bold text-lg flex items-center gap-2"><?= icon('user-plus', 'size-5 text-primary') ?><?= e(__('Shop sign-up')) ?></h2>
    <div class="grid md:grid-cols-2 gap-4 mt-4">
        <label class="flex items-center justify-between rounded-box border border-base-content/10 p-4">
            <span><span class="font-semibold"><?= e(__('Allow self-service sign-up')) ?></span><span class="block text-xs opacity-60"><?= e(__('Visitors can create a shop from the public website (trial).')) ?></span></span>
            <input type="checkbox" name="allow_signup" value="1" class="toggle toggle-primary" <?= ($theme['platform.allow_signup'] ?? '0') === '1' ? 'checked' : '' ?>>
        </label>
        <label class="field"><span class="label-text"><?= e(__('Default plan for new shops')) ?></span>
            <select name="default_plan_id" class="select w-full">
                <option value="0"><?= e(__('None')) ?></option>
                <?php foreach ($plans as $p): ?><option value="<?= (int) $p['id'] ?>" <?= (string) ($theme['platform.default_plan_id'] ?? '') === (string) $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
            </select></label>
    </div>
</section>
<?php $extra = ob_get_clean(); ?>

<?= View::partial('partials/theme_form', compact('theme', 'fontsEn', 'fontsAr', 'presets', 'action', 'scope', 'extra')) ?>

<form method="post" action="<?= e(base_url('/platform/settings/password')) ?>" class="surface p-6 mt-6 max-w-xl space-y-4">
    <?= csrf_field() ?>
    <h2 class="font-bold text-lg flex items-center gap-2"><?= icon('key-round', 'size-5 text-primary') ?><?= e(__('Change password')) ?></h2>
    <input type="password" name="current_password" placeholder="<?= e(__('Current password')) ?>" class="input w-full" required>
    <div class="grid sm:grid-cols-2 gap-4">
        <input type="password" name="password" minlength="8" placeholder="<?= e(__('New password')) ?>" class="input w-full" required>
        <input type="password" name="password_confirmation" minlength="8" placeholder="<?= e(__('Confirm password')) ?>" class="input w-full" required>
    </div>
    <button class="btn rounded-full"><?= e(__('Update password')) ?></button>
</form>
