<?php
/** Language + light/dark switches. $langBase: route prefix for the /lang/{code} switch. */
$langUrl = $langUrl ?? url('/lang/' . (locale() === 'ar' ? 'en' : 'ar'));
?>
<a href="<?= e($langUrl) ?>" class="btn btn-ghost btn-sm btn-circle font-bold" title="<?= locale() === 'ar' ? 'English' : 'العربية' ?>">
    <?= locale() === 'ar' ? 'EN' : 'ع' ?>
</a>
<button type="button" onclick="matjar.toggleMode()" class="btn btn-ghost btn-sm btn-circle" title="<?= e(__('Light / dark')) ?>">
    <span class="dark:hidden"><?= icon('moon', 'size-[18px]') ?></span>
    <span class="hidden dark:inline"><?= icon('sun', 'size-[18px]') ?></span>
</button>
