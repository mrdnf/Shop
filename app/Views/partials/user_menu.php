<?php
$user = auth();
$logoutUrl = $logoutUrl ?? url('/logout');
?>
<?php if ($user): ?>
    <div class="dropdown dropdown-end">
        <div tabindex="0" role="button" class="btn btn-ghost btn-sm gap-2 px-1.5 rounded-full">
            <span class="avatar avatar-placeholder">
                <span class="bg-brand w-8 rounded-full text-xs font-bold"><?= e(initials($user['name'])) ?></span>
            </span>
            <span class="hidden md:inline max-w-28 truncate font-semibold"><?= e($user['name']) ?></span>
            <?= icon('chevron-down', 'size-4 opacity-60 hidden md:inline') ?>
        </div>
        <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-50 mt-3 w-60 p-2 shadow-2xl border border-base-content/5">
            <li class="px-3 py-2">
                <div class="flex flex-col items-start gap-0 pointer-events-none">
                    <span class="font-bold"><?= e($user['name']) ?></span>
                    <span class="text-xs opacity-60"><?= e($user['email']) ?></span>
                </div>
            </li>
            <div class="divider my-0"></div>
            <?php foreach ($links ?? [] as [$label, $href, $ic]): ?>
                <li><a href="<?= e($href) ?>"><?= icon($ic, 'size-4') ?> <?= e($label) ?></a></li>
            <?php endforeach; ?>
            <li>
                <form method="post" action="<?= e($logoutUrl) ?>" class="p-0">
                    <?= csrf_field() ?>
                    <button class="flex w-full items-center gap-2 px-3 py-1.5 text-error"><?= icon('log-out', 'size-4') ?> <?= e(__('Sign out')) ?></button>
                </form>
            </li>
        </ul>
    </div>
<?php endif; ?>
