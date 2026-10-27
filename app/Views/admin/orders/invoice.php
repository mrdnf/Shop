<?php use App\Core\View; ?>
<div class="flex flex-wrap justify-between gap-6">
    <div>
        <?= View::partial('partials/brand', ['forceName' => true, 'size' => 'lg']) ?>
        <div class="text-sm opacity-70 mt-3 space-y-0.5">
            <?php if ($company['address']): ?><div class="whitespace-pre-line"><?= e($company['address']) ?></div><?php endif; ?>
            <?php if ($company['phone']): ?><div dir="ltr"><?= e($company['phone']) ?></div><?php endif; ?>
            <?php if ($company['email']): ?><div><?= e($company['email']) ?></div><?php endif; ?>
            <?php if ($company['tax_number']): ?><div><?= e(__('Tax number')) ?>: <?= e($company['tax_number']) ?></div><?php endif; ?>
        </div>
    </div>
    <div class="text-end">
        <div class="text-4xl font-black text-brand"><?= e(__('Invoice')) ?></div>
        <div class="font-mono mt-2"><?= e($order['number']) ?></div>
        <div class="text-sm opacity-60"><?= e(format_date($order['created_at'])) ?></div>
    </div>
</div>
<div class="divider"></div>
<div class="grid sm:grid-cols-2 gap-6 text-sm">
    <div>
        <div class="text-xs uppercase opacity-50 font-semibold mb-1"><?= e(__('Bill to')) ?></div>
        <div class="font-semibold"><?= e($order['customer_name']) ?></div>
        <div><?= e($order['street']) ?><?= $order['building'] ? ', ' . e($order['building']) : '' ?></div>
        <div><?= e(trim($order['city'] . ($order['area'] ? ', ' . $order['area'] : ''))) ?></div>
        <div dir="ltr" class="text-start rtl:text-end"><?= e($order['phone']) ?></div>
    </div>
    <div class="sm:text-end">
        <div class="text-xs uppercase opacity-50 font-semibold mb-1"><?= e(__('Payment')) ?></div>
        <div><?= e(__('payment.' . $order['payment_method'])) ?> · <?= e(__('status.' . $order['payment_status'])) ?></div>
    </div>
</div>
<table class="table mt-8">
    <thead><tr><th><?= e(__('Item')) ?></th><th>SKU</th><th class="text-end"><?= e(__('Price')) ?></th><th class="text-end"><?= e(__('Qty')) ?></th><th class="text-end"><?= e(__('Total')) ?></th></tr></thead>
    <tbody>
    <?php foreach ($items as $i): ?>
        <tr><td><?= e($i['name']) ?></td><td class="font-mono text-xs"><?= e($i['sku']) ?></td><td class="text-end"><?= e(money($i['price'])) ?></td><td class="text-end"><?= (int) $i['qty'] ?></td><td class="text-end"><?= e(money($i['total'])) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>
<div class="max-w-xs ms-auto mt-6 space-y-1 text-sm">
    <div class="flex justify-between"><span><?= e(__('Subtotal')) ?></span><span><?= e(money($order['subtotal'])) ?></span></div>
    <div class="flex justify-between"><span><?= e(__('Shipping')) ?></span><span><?= e(money($order['shipping'])) ?></span></div>
    <div class="flex justify-between"><span><?= e(__('Tax')) ?></span><span><?= e(money($order['tax'])) ?></span></div>
    <div class="flex justify-between text-xl font-black border-t border-base-content/10 pt-2"><span><?= e(__('Total')) ?></span><span><?= e(money($order['total'])) ?></span></div>
</div>
<p class="text-center text-xs opacity-50 mt-12"><?= e(__('Thank you for shopping with :name', ['name' => company_name()])) ?></p>
