<?php
use App\Controllers\Admin\OrderController;
use App\Services\Delivery\DeliveryService;

$o = $order;
?>
<div class="flex flex-wrap items-center gap-3 mb-6">
    <a href="<?= e(url('/admin/orders')) ?>" class="btn btn-ghost btn-sm btn-circle"><?= icon('arrow-left', 'size-4 flip-rtl') ?></a>
    <div class="flex items-center gap-2"><?= status_badge($o['status']) ?><?= status_badge($o['payment_status']) ?></div>
    <span class="text-sm opacity-60"><?= e(format_date($o['created_at'], true)) ?></span>
    <a href="<?= e(url('/admin/orders/' . $o['id'] . '/invoice')) ?>" target="_blank" class="btn btn-sm rounded-full ms-auto"><?= icon('printer', 'size-4') ?> <?= e(__('Invoice')) ?></a>
</div>

<div class="grid xl:grid-cols-[1fr_22rem] gap-6">
    <div class="space-y-6">
        <section class="surface overflow-hidden">
            <div class="p-5 font-bold flex items-center gap-2"><?= icon('package', 'size-5 text-primary') ?><?= e(__('Items')) ?></div>
            <table class="table table-modern">
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <span class="size-11 rounded-xl placeholder-img overflow-hidden grid place-items-center shrink-0">
                                    <?php if ($item['image']): ?><img src="<?= e(upload_url($item['image'])) ?>" class="size-full object-cover" alt=""><?php else: ?><?= icon('package', 'size-5 text-primary/60') ?><?php endif; ?>
                                </span>
                                <div><div class="font-medium"><?= e($item['name']) ?></div><div class="text-xs font-mono opacity-50"><?= e($item['sku']) ?></div></div>
                            </div>
                        </td>
                        <td class="text-sm"><?= e(money($item['price'])) ?> × <?= (int) $item['qty'] ?></td>
                        <td class="text-end font-semibold"><?= e(money($item['total'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div class="p-5 border-t border-base-content/5 grid gap-1 text-sm max-w-xs ms-auto">
                <div class="flex justify-between"><span class="opacity-60"><?= e(__('Subtotal')) ?></span><span><?= e(money($o['subtotal'])) ?></span></div>
                <div class="flex justify-between"><span class="opacity-60"><?= e(__('Shipping')) ?><?= $zone ? ' (' . e(tr($zone, 'name')) . ')' : '' ?></span><span><?= e(money($o['shipping'])) ?></span></div>
                <div class="flex justify-between"><span class="opacity-60"><?= e(__('Tax')) ?></span><span><?= e(money($o['tax'])) ?></span></div>
                <div class="flex justify-between text-lg font-black pt-1"><span><?= e(__('Total')) ?></span><span class="text-brand"><?= e(money($o['total'])) ?></span></div>
            </div>
        </section>

        <section class="surface p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-bold flex items-center gap-2"><?= icon('truck', 'size-5 text-primary') ?><?= e(__('Shipments')) ?></h2>
            </div>
            <?php foreach ($shipments as $s): ?>
                <a href="<?= e(url('/admin/shipments/' . $s['id'])) ?>" class="flex flex-wrap items-center gap-3 p-4 rounded-box border border-base-content/10 hover:border-primary/40 transition mb-3">
                    <span class="kpi-icon bg-primary/10 text-primary size-10"><?= icon(DeliveryService::ICONS[$s['status']] ?? 'truck', 'size-5') ?></span>
                    <div class="flex-1 min-w-40">
                        <div class="font-mono font-bold"><?= e($s['tracking_number']) ?></div>
                        <div class="text-xs opacity-60"><?= e($s['courier_name'] ?? __('No courier')) ?><?= $s['driver_name'] ? ' · ' . e($s['driver_name']) : '' ?></div>
                    </div>
                    <?= status_badge($s['status']) ?>
                    <progress class="progress progress-primary w-full" value="<?= DeliveryService::progress($s['status']) ?>" max="100"></progress>
                </a>
            <?php endforeach; ?>

            <?php if ($o['status'] !== 'cancelled'): ?>
                <details class="collapse collapse-arrow border border-dashed border-base-content/20 rounded-box" <?= !$shipments ? 'open' : '' ?>>
                    <summary class="collapse-title font-semibold"><?= icon('plus', 'size-4 inline') ?> <?= e(__('Create shipment')) ?></summary>
                    <div class="collapse-content">
                        <form method="post" action="<?= e(url('/admin/orders/' . $o['id'] . '/shipments')) ?>" class="grid sm:grid-cols-2 gap-4">
                            <?= csrf_field() ?>
                            <label class="field"><span class="label-text"><?= e(__('Courier')) ?></span>
                                <select name="courier_id" class="select w-full">
                                    <option value=""><?= e(__('None')) ?></option>
                                    <?php foreach ($couriers as $c): ?><option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?> (<?= e(__('driver.' . $c['driver'])) ?>)</option><?php endforeach; ?>
                                </select></label>
                            <label class="field"><span class="label-text"><?= e(__('Driver')) ?></span>
                                <select name="driver_id" class="select w-full">
                                    <option value=""><?= e(__('Assign later')) ?></option>
                                    <?php foreach ($drivers as $d): ?><option value="<?= (int) $d['id'] ?>"><?= e($d['name']) ?></option><?php endforeach; ?>
                                </select></label>
                            <label class="field"><span class="label-text"><?= e(__('Tracking number')) ?></span>
                                <input name="tracking_number" class="input w-full font-mono" placeholder="<?= e(__('Auto-generate')) ?>"></label>
                            <label class="field"><span class="label-text"><?= e(__('Expected delivery')) ?></span>
                                <input type="date" name="eta" class="input w-full" value="<?= $zone ? e(date('Y-m-d', strtotime('+' . (int) $zone['eta_max_days'] . ' days'))) : '' ?>"></label>
                            <div class="sm:col-span-2"><button class="btn btn-primary rounded-full"><?= icon('truck', 'size-4') ?> <?= e(__('Create shipment')) ?></button></div>
                        </form>
                    </div>
                </details>
            <?php endif; ?>
        </section>

        <section class="surface p-5">
            <h2 class="font-bold mb-4 flex items-center gap-2"><?= icon('history', 'size-5 text-primary') ?><?= e(__('History')) ?></h2>
            <ul class="timeline timeline-vertical timeline-compact">
                <?php foreach ($history as $i => $h): ?>
                    <li>
                        <?php if ($i > 0): ?><hr class="bg-base-content/10"><?php endif; ?>
                        <div class="timeline-middle"><span class="block size-3 rounded-full <?= $i === 0 ? 'bg-primary' : 'bg-base-content/20' ?>"></span></div>
                        <div class="timeline-end mb-4 ms-2">
                            <div class="font-semibold text-sm"><?= e(__('status.' . $h['status'])) ?> <span class="text-xs opacity-50 font-normal"><?= e(format_date($h['created_at'], true)) ?><?= $h['user_name'] ? ' · ' . e($h['user_name']) : '' ?></span></div>
                            <?php if ($h['note']): ?><div class="text-sm opacity-70"><?= e($h['note']) ?></div><?php endif; ?>
                        </div>
                        <?php if ($i < count($history) - 1): ?><hr class="bg-base-content/10"><?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    </div>

    <aside class="space-y-4">
        <form method="post" action="<?= e(url('/admin/orders/' . $o['id'] . '/status')) ?>" class="surface p-5 space-y-3">
            <?= csrf_field() ?>
            <h2 class="font-bold"><?= e(__('Update status')) ?></h2>
            <select name="status" class="select w-full">
                <?php foreach (OrderController::STATUSES as $s): ?><option value="<?= $s ?>" <?= $o['status'] === $s ? 'selected' : '' ?>><?= e(__('status.' . $s)) ?></option><?php endforeach; ?>
            </select>
            <input name="note" class="input w-full" placeholder="<?= e(__('Note (optional)')) ?>">
            <button class="btn btn-primary btn-sm w-full rounded-full"><?= e(__('Save')) ?></button>
        </form>
        <form method="post" action="<?= e(url('/admin/orders/' . $o['id'] . '/payment')) ?>" class="surface p-5 space-y-3">
            <?= csrf_field() ?>
            <h2 class="font-bold flex items-center justify-between"><?= e(__('Payment')) ?> <span class="badge badge-ghost badge-sm"><?= e(__('payment.' . $o['payment_method'])) ?></span></h2>
            <div class="join w-full">
                <?php foreach (['pending', 'paid', 'refunded'] as $ps): ?>
                    <button name="payment_status" value="<?= $ps ?>" class="join-item btn btn-sm flex-1 <?= $o['payment_status'] === $ps ? 'btn-primary' : '' ?>"><?= e(__('status.' . $ps)) ?></button>
                <?php endforeach; ?>
            </div>
        </form>
        <div class="surface p-5 text-sm space-y-2">
            <h2 class="font-bold flex items-center gap-2"><?= icon('user-round', 'size-4 text-primary') ?><?= e(__('Customer')) ?></h2>
            <div class="font-semibold"><?= e($o['customer_name']) ?></div>
            <?php if ($o['email']): ?><div class="opacity-70"><?= e($o['email']) ?></div><?php endif; ?>
            <a href="tel:<?= e($o['phone']) ?>" class="link block" dir="ltr"><?= e($o['phone']) ?></a>
            <?php if ($o['user_id']): ?><a href="<?= e(url('/admin/customers/' . $o['user_id'])) ?>" class="link link-primary text-xs"><?= e(__('View customer')) ?></a><?php else: ?><span class="badge badge-ghost badge-sm"><?= e(__('Guest')) ?></span><?php endif; ?>
        </div>
        <div class="surface p-5 text-sm space-y-1">
            <h2 class="font-bold flex items-center gap-2 mb-2"><?= icon('map-pin', 'size-4 text-primary') ?><?= e(__('Delivery address')) ?></h2>
            <div><?= e($o['street']) ?><?= $o['building'] ? ', ' . e($o['building']) : '' ?></div>
            <div><?= e(trim($o['city'] . ($o['area'] ? ', ' . $o['area'] : ''))) ?> <?= e($o['country'] ?? '') ?></div>
            <?php if ($o['address_notes']): ?><div class="opacity-70 italic"><?= e($o['address_notes']) ?></div><?php endif; ?>
            <?php if ($o['lat']): ?><a target="_blank" rel="noopener" class="link link-primary text-xs inline-flex items-center gap-1" href="https://www.google.com/maps?q=<?= e($o['lat']) ?>,<?= e($o['lng']) ?>"><?= icon('map', 'size-3') ?><?= e(__('Open in maps')) ?></a><?php endif; ?>
        </div>
    </aside>
</div>
