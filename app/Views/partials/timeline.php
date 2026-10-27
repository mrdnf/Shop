<?php
use App\Services\Delivery\DeliveryService;

/** @var array $events newest first */
if (!$events): ?>
    <p class="opacity-60 text-sm"><?= e(__('No tracking updates yet.')) ?></p>
<?php return; endif; ?>
<ol class="relative space-y-6 ps-8 before:absolute before:top-2 before:bottom-2 before:start-[0.9rem] before:w-0.5 before:bg-base-content/10">
    <?php foreach ($events as $i => $ev):
        $bad = in_array($ev['status'], DeliveryService::PROBLEMS, true); ?>
        <li class="relative">
            <span class="absolute -start-8 top-0 size-7 rounded-full grid place-items-center ring-4 ring-base-100
                <?= $i === 0 ? ($bad ? 'bg-error text-error-content' : 'bg-brand') : 'bg-base-200 text-base-content/60' ?>">
                <?= icon(DeliveryService::ICONS[$ev['status']] ?? 'circle', 'size-3.5') ?>
            </span>
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                <span class="font-semibold <?= $i === 0 ? '' : 'opacity-80' ?>"><?= e(__('status.' . $ev['status'])) ?></span>
                <span class="text-xs opacity-50"><?= e(format_date($ev['created_at'], true)) ?></span>
                <?php if (!empty($showSource)): ?><span class="badge badge-ghost badge-xs"><?= e(__('source.' . $ev['source'])) ?></span><?php endif; ?>
            </div>
            <?php if ($ev['note']): ?><p class="text-sm opacity-70 mt-0.5"><?= e($ev['note']) ?></p><?php endif; ?>
            <?php if ($ev['location']): ?><p class="text-xs opacity-60 mt-0.5 flex items-center gap-1"><?= icon('map-pin', 'size-3') ?><?= e($ev['location']) ?></p><?php endif; ?>
        </li>
    <?php endforeach; ?>
</ol>
