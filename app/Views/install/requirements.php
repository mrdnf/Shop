<h1 class="text-3xl font-extrabold tracking-tight"><?= e(__('Welcome to Matjar')) ?> 👋</h1>
<p class="mt-2 opacity-70"><?= e(__('A multi-language online shop with live inventory integration, delivery tracking and a full SaaS mode. Let us check your server first.')) ?></p>

<ul class="mt-8 divide-y divide-base-content/5 rounded-box border border-base-content/10">
    <?php foreach ($checks as [$label, $pass, $info]): ?>
        <li class="flex items-center justify-between px-4 py-3">
            <span class="font-medium"><?= e($label) ?> <?php if ($info): ?><span class="opacity-50 text-sm"><?= e($info) ?></span><?php endif; ?></span>
            <?php if ($pass): ?>
                <span class="badge badge-success badge-soft gap-1"><?= icon('check', 'size-3.5') ?> OK</span>
            <?php else: ?>
                <span class="badge badge-error badge-soft gap-1"><?= icon('x', 'size-3.5') ?> <?= e(__('Missing')) ?></span>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>

<div class="mt-8 flex justify-end">
    <?php if ($ok): ?>
        <a href="<?= e(base_url('/install/database')) ?>" class="btn btn-primary rounded-full px-8"><?= e(__('Continue')) ?> <?= icon('arrow-right', 'size-4 flip-rtl') ?></a>
    <?php else: ?>
        <a href="<?= e(base_url('/install')) ?>" class="btn rounded-full"><?= icon('refresh-cw', 'size-4') ?> <?= e(__('Check again')) ?></a>
    <?php endif; ?>
</div>
