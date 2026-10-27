<div class="text-center py-14 px-6">
    <div class="mx-auto size-16 rounded-2xl bg-primary/10 text-primary grid place-items-center"><?= icon($icon ?? 'inbox', 'size-8') ?></div>
    <h3 class="mt-4 font-bold text-lg"><?= e($title ?? __('Nothing here yet')) ?></h3>
    <?php if (!empty($text)): ?><p class="opacity-60 mt-1 max-w-sm mx-auto"><?= e($text) ?></p><?php endif; ?>
    <?php if (!empty($action)): ?><div class="mt-5"><?= $action ?></div><?php endif; ?>
</div>
