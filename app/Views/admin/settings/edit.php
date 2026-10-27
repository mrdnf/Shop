<?php $g = static fn (string $k, string $d = '') => e($s[$k] ?? $d); ?>
<form method="post" action="<?= e(url('/admin/settings')) ?>" class="grid xl:grid-cols-2 gap-6">
    <?= csrf_field() ?>
    <section class="surface p-6 space-y-4">
        <h2 class="font-bold text-lg flex items-center gap-2"><?= icon('coins', 'size-5 text-primary') ?><?= e(__('Currency & tax')) ?></h2>
        <div class="grid sm:grid-cols-2 gap-4">
            <label class="field"><span class="label-text"><?= e(__('Currency code')) ?></span><input name="currency" value="<?= $g('store.currency', 'USD') ?>" class="input w-full" required></label>
            <label class="field"><span class="label-text"><?= e(__('Currency symbol')) ?></span><input name="currency_symbol" value="<?= $g('store.currency_symbol', '$') ?>" class="input w-full" required></label>
            <label class="field"><span class="label-text"><?= e(__('Symbol position')) ?></span>
                <select name="symbol_position" class="select w-full">
                    <option value="before" <?= ($s['store.symbol_position'] ?? 'before') === 'before' ? 'selected' : '' ?>><?= e(__('Before amount')) ?> ($10)</option>
                    <option value="after" <?= ($s['store.symbol_position'] ?? '') === 'after' ? 'selected' : '' ?>><?= e(__('After amount')) ?> (10 ر.س)</option>
                </select></label>
            <label class="field"><span class="label-text"><?= e(__('Decimals')) ?></span><input type="number" min="0" max="3" name="decimals" value="<?= $g('store.decimals', '2') ?>" class="input w-full"></label>
            <label class="field"><span class="label-text"><?= e(__('Tax rate (%)')) ?></span><input type="number" step="0.01" min="0" max="100" name="tax_rate" value="<?= $g('store.tax_rate', '0') ?>" class="input w-full"></label>
            <label class="field"><span class="label-text"><?= e(__('Tax number')) ?></span><input name="tax_number" value="<?= $g('company.tax_number') ?>" class="input w-full"></label>
        </div>
    </section>

    <section class="surface p-6 space-y-4">
        <h2 class="font-bold text-lg flex items-center gap-2"><?= icon('store', 'size-5 text-primary') ?><?= e(__('Orders & checkout')) ?></h2>
        <div class="grid sm:grid-cols-2 gap-4">
            <label class="field"><span class="label-text"><?= e(__('Order number prefix')) ?></span><input name="order_prefix" value="<?= $g('store.order_prefix', 'ORD') ?>" class="input w-full font-mono" required></label>
            <label class="field"><span class="label-text"><?= e(__('Tracking number prefix')) ?></span><input name="tracking_prefix" value="<?= $g('delivery.tracking_prefix', 'MJ') ?>" class="input w-full font-mono" required></label>
        </div>
        <label class="flex items-center justify-between rounded-box border border-base-content/10 p-4"><span><?= e(__('Allow guest checkout')) ?></span><input type="checkbox" name="allow_guest_checkout" value="1" class="toggle toggle-primary" <?= ($s['store.allow_guest_checkout'] ?? '1') === '1' ? 'checked' : '' ?>></label>
        <label class="flex items-center justify-between rounded-box border border-base-content/10 p-4"><span class="flex items-center gap-2"><?= icon('banknote', 'size-4') ?><?= e(__('Cash on delivery')) ?></span><input type="checkbox" name="payment_cod" value="1" class="toggle toggle-primary" <?= ($s['payment.cod'] ?? '1') === '1' ? 'checked' : '' ?>></label>
        <label class="flex items-center justify-between rounded-box border border-base-content/10 p-4"><span class="flex items-center gap-2"><?= icon('landmark', 'size-4') ?><?= e(__('Bank transfer')) ?></span><input type="checkbox" name="payment_bank" value="1" class="toggle toggle-primary" <?= ($s['payment.bank'] ?? '0') === '1' ? 'checked' : '' ?>></label>
        <label class="field"><span class="label-text"><?= e(__('Bank transfer details')) ?></span><textarea name="bank_details" rows="3" class="textarea w-full" placeholder="IBAN: ..."><?= $g('payment.bank_details') ?></textarea></label>
    </section>

    <section class="surface p-6 space-y-4 xl:col-span-2">
        <h2 class="font-bold text-lg flex items-center gap-2"><?= icon('contact', 'size-5 text-primary') ?><?= e(__('Contact details')) ?></h2>
        <div class="grid md:grid-cols-3 gap-4">
            <label class="field"><span class="label-text"><?= e(__('Email')) ?></span><input type="email" name="email" value="<?= $g('company.email') ?>" class="input w-full"></label>
            <label class="field"><span class="label-text"><?= e(__('Phone')) ?></span><input name="phone" dir="ltr" value="<?= $g('company.phone') ?>" class="input w-full"></label>
            <label class="field"><span class="label-text"><?= e(__('Address')) ?></span><input name="address" value="<?= $g('company.address') ?>" class="input w-full"></label>
        </div>
    </section>
    <div class="xl:col-span-2"><button class="btn btn-primary rounded-full shadow-lg shadow-primary/25"><?= icon('save', 'size-4') ?> <?= e(__('Save settings')) ?></button></div>
</form>
