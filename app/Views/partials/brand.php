<?php
/** @var string $href */
$logo = setting('company.logo');
$logoDark = setting('company.logo_dark');
$size = $size ?? 'md';
$showName = $showName ?? true;
$h = $size === 'lg' ? 'h-11' : 'h-9';
?>
<a href="<?= e($href ?? url('/')) ?>" class="flex items-center gap-2.5 min-w-0 group">
    <?php if ($logo): ?>
        <img src="<?= e(upload_url($logo)) ?>" alt="<?= e(company_name()) ?>" class="<?= $h ?> w-auto max-w-40 object-contain <?= $logoDark ? 'dark:hidden' : '' ?>">
        <?php if ($logoDark): ?><img src="<?= e(upload_url($logoDark)) ?>" alt="<?= e(company_name()) ?>" class="<?= $h ?> w-auto max-w-40 object-contain hidden dark:block"><?php endif; ?>
    <?php else: ?>
        <span class="bg-brand <?= $h ?> aspect-square rounded-xl grid place-items-center font-black text-sm shadow-lg shadow-primary/30 group-hover:rotate-6 transition">
            <?= e(initials(company_name())) ?>
        </span>
    <?php endif; ?>
    <?php if ($showName && (!$logo || ($forceName ?? false))): ?>
        <span class="font-extrabold text-lg tracking-tight truncate"><?= e(company_name()) ?></span>
    <?php endif; ?>
</a>
