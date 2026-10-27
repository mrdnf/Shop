<div class="grid md:grid-cols-2 gap-6">
    <form method="post" action="<?= e(url('/account/profile')) ?>" class="surface p-6 space-y-4">
        <?= csrf_field() ?>
        <h2 class="font-bold text-lg flex items-center gap-2"><?= icon('user-round', 'size-5 text-primary') ?><?= e(__('Profile')) ?></h2>
        <label class="field"><span class="label-text"><?= e(__('Full name')) ?></span><input name="name" value="<?= e($user['name']) ?>" class="input w-full" required></label>
        <label class="field"><span class="label-text"><?= e(__('Email')) ?></span><input type="email" name="email" value="<?= e($user['email']) ?>" class="input w-full" required></label>
        <label class="field"><span class="label-text"><?= e(__('Phone')) ?></span><input name="phone" dir="ltr" value="<?= e($user['phone']) ?>" class="input w-full"></label>
        <label class="field"><span class="label-text"><?= e(__('Language')) ?></span>
            <select name="locale" class="select w-full">
                <option value="en" <?= ($user['locale'] ?: locale()) === 'en' ? 'selected' : '' ?>>English</option>
                <option value="ar" <?= ($user['locale'] ?: locale()) === 'ar' ? 'selected' : '' ?>>العربية</option>
            </select></label>
        <button class="btn btn-primary rounded-full"><?= e(__('Save changes')) ?></button>
    </form>
    <form method="post" action="<?= e(url('/account/password')) ?>" class="surface p-6 space-y-4 h-fit">
        <?= csrf_field() ?>
        <h2 class="font-bold text-lg flex items-center gap-2"><?= icon('key-round', 'size-5 text-primary') ?><?= e(__('Change password')) ?></h2>
        <label class="field"><span class="label-text"><?= e(__('Current password')) ?></span><input type="password" name="current_password" class="input w-full" required autocomplete="current-password"></label>
        <label class="field"><span class="label-text"><?= e(__('New password')) ?></span><input type="password" name="password" minlength="8" class="input w-full" required autocomplete="new-password"></label>
        <label class="field"><span class="label-text"><?= e(__('Confirm password')) ?></span><input type="password" name="password_confirmation" minlength="8" class="input w-full" required autocomplete="new-password"></label>
        <button class="btn rounded-full"><?= e(__('Update password')) ?></button>
    </form>
</div>
