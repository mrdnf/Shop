<?php
use App\Services\Delivery\DeliveryService;

/** Visual delivery progress for a shipment status. */
$flow = DeliveryService::FLOW;
$index = array_search($status, $flow, true);
$problem = in_array($status, DeliveryService::PROBLEMS, true);
?>
<?php if ($problem): ?>
    <div role="alert" class="alert alert-error alert-soft">
        <?= icon(DeliveryService::ICONS[$status], 'size-5') ?>
        <span class="font-semibold"><?= e(__('status.' . $status)) ?></span>
    </div>
<?php else: ?>
    <ul class="steps steps-vertical sm:steps-horizontal w-full">
        <?php foreach ($flow as $i => $s): ?>
            <li class="step <?= $index !== false && $i <= $index ? 'step-primary' : '' ?>" data-content="<?= $index !== false && $i < $index ? '✓' : ($i + 1) ?>">
                <span class="text-xs sm:text-[0.7rem] font-medium <?= $i === $index ? 'text-primary font-bold' : 'opacity-70' ?>"><?= e(__('status.' . $s)) ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
