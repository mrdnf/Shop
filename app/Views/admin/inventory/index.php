<?php
use App\Core\View;

$all = ['local' => ['label' => __('provider.local'), 'description' => __('Stock is managed inside this shop.'), 'fields' => []]] + $providers;
$icons = ['local' => 'warehouse', 'rest' => 'cloud-cog', 'csv' => 'file-spreadsheet', 'database' => 'database'];
$webhook = full_url(url('/api/v1/inventory'));
?>
<div class="grid sm:grid-cols-3 gap-4 mb-6">
    <div class="surface p-5 flex items-center gap-4"><span class="kpi-icon bg-primary/10 text-primary"><?= icon('refresh-cw') ?></span><div><div class="text-2xl font-black"><?= (int) $stats['tracked'] ?></div><div class="text-sm opacity-60"><?= e(__('Products following the integration')) ?></div></div></div>
    <div class="surface p-5 flex items-center gap-4"><span class="kpi-icon bg-secondary/10 text-secondary"><?= icon('hand') ?></span><div><div class="text-2xl font-black"><?= (int) $stats['local'] ?></div><div class="text-sm opacity-60"><?= e(__('Managed manually')) ?></div></div></div>
    <div class="surface p-5 flex items-center gap-4"><span class="kpi-icon bg-error/10 text-error"><?= icon('package-x') ?></span><div><div class="text-2xl font-black"><?= (int) $stats['out'] ?></div><div class="text-sm opacity-60"><?= e(__('Out of stock')) ?></div></div></div>
</div>

<form method="post" action="<?= e(url('/admin/inventory')) ?>" x-data="inventoryForm()" class="space-y-6">
    <?= csrf_field() ?>
    <section class="surface p-6">
        <h2 class="font-bold text-lg"><?= e(__('Where does your stock balance live?')) ?></h2>
        <p class="text-sm opacity-60"><?= e(__('Pick a connector. Stock is pulled on a schedule, checked live at checkout, or pushed to us by your system.')) ?></p>
        <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-3 mt-5">
            <?php foreach ($all as $key => $p): ?>
                <label class="cursor-pointer rounded-box border-2 p-4 transition" :class="provider === '<?= $key ?>' ? 'border-primary bg-primary/5 shadow-lg shadow-primary/10' : 'border-base-content/10 hover:border-primary/30'">
                    <input type="radio" name="provider" value="<?= e($key) ?>" x-model="provider" class="hidden">
                    <span class="size-11 rounded-xl grid place-items-center" :class="provider === '<?= $key ?>' ? 'bg-brand' : 'bg-base-200'"><?= icon($icons[$key] ?? 'plug', 'size-5') ?></span>
                    <div class="font-bold mt-3"><?= e(__($p['label'])) ?></div>
                    <div class="text-xs opacity-60 mt-1"><?= e(__($p['description'])) ?></div>
                </label>
            <?php endforeach; ?>
        </div>

        <?php foreach ($providers as $key => $p): ?>
            <div x-show="provider === '<?= $key ?>'" x-cloak class="mt-6" :disabled="provider !== '<?= $key ?>'">
                <template x-if="provider === '<?= $key ?>'"><div>
                    <?= View::partial('partials/config_fields', ['fields' => $p['fields'], 'values' => $current === $key ? $config : [], 'hasSecret' => $current === $key]) ?>
                </div></template>
            </div>
        <?php endforeach; ?>

        <div class="flex flex-wrap items-center gap-3 mt-6" x-show="provider !== 'local'" x-cloak>
            <button type="button" class="btn btn-sm rounded-full" @click="test()" :class="testing && 'btn-disabled'">
                <span class="loading loading-spinner loading-xs" x-show="testing"></span><?= icon('plug-zap', 'size-4') ?> <?= e(__('Test connection')) ?>
            </button>
            <span class="text-sm" :class="result && (result.ok ? 'text-success' : 'text-error')" x-text="result ? result.message : ''"></span>
        </div>
        <template x-if="result && result.sample && Object.keys(result.sample).length">
            <div class="mt-3 flex flex-wrap gap-2">
                <template x-for="(qty, sku) in result.sample"><span class="badge badge-ghost font-mono" x-text="sku + ': ' + qty"></span></template>
            </div>
        </template>
    </section>

    <section class="surface p-6" x-show="provider !== 'local'" x-cloak>
        <h2 class="font-bold text-lg mb-4"><?= e(__('Sync behaviour')) ?></h2>
        <div class="grid md:grid-cols-3 gap-4">
            <label class="field"><span class="label-text"><?= e(__('Scheduled sync every (minutes)')) ?></span>
                <input type="number" min="1" name="sync_interval" value="<?= e(setting('inventory.sync_interval', 15)) ?>" class="input w-full">
                <span class="text-xs opacity-60"><?= e(__('Runs from the cron job (bin/cron.php).')) ?></span></label>
            <label class="field"><span class="label-text"><?= e(__('Real-time cache (seconds)')) ?></span>
                <input type="number" min="0" name="cache_ttl" value="<?= e(setting('inventory.cache_ttl', 300)) ?>" class="input w-full"></label>
            <label class="flex items-start gap-3 rounded-box border border-base-content/10 p-4 cursor-pointer">
                <input type="checkbox" name="realtime" value="1" class="toggle toggle-primary mt-0.5" <?= setting('inventory.realtime') === '1' ? 'checked' : '' ?>>
                <span><span class="font-semibold"><?= e(__('Real-time check')) ?></span><span class="block text-xs opacity-60"><?= e(__('Ask the external system for the live balance on product pages, cart and checkout.')) ?></span></span>
            </label>
        </div>
    </section>

    <section class="surface p-6">
        <h2 class="font-bold text-lg flex items-center gap-2"><?= icon('webhook', 'size-5 text-primary') ?><?= e(__('Two-way integration')) ?></h2>
        <div class="grid lg:grid-cols-2 gap-6 mt-4">
            <div>
                <div class="font-semibold"><?= e(__('Push balances to us (any system)')) ?></div>
                <p class="text-sm opacity-60 mt-1"><?= e(__('Your ERP / POS can POST stock changes in real time with an API key:')) ?></p>
                <div class="mockup-code text-xs mt-3" dir="ltr">
                    <pre data-prefix="$"><code>curl -X POST <?= e($webhook) ?> \</code></pre>
                    <pre data-prefix=" "><code>  -H "Authorization: Bearer mk_..." \</code></pre>
                    <pre data-prefix=" "><code>  -H "Content-Type: application/json" \</code></pre>
                    <pre data-prefix=" "><code>  -d '{"items":[{"sku":"EL-1001","quantity":12}]}'</code></pre>
                </div>
                <?php if (!$hasKeys): ?><a href="<?= e(url('/admin/api-keys')) ?>" class="btn btn-sm rounded-full mt-3"><?= icon('key-round', 'size-4') ?> <?= e(__('Create an API key')) ?></a><?php endif; ?>
            </div>
            <div>
                <div class="font-semibold"><?= e(__('Send orders to your system')) ?></div>
                <p class="text-sm opacity-60 mt-1"><?= e(__('We POST every new / cancelled order (SKU + quantity) so your ERP deducts stock. Signed with HMAC-SHA256 in X-Matjar-Signature.')) ?></p>
                <label class="field mt-3"><span class="label-text"><?= e(__('Order webhook URL')) ?></span>
                    <input name="order_webhook_url" dir="ltr" value="<?= e(setting('inventory.order_webhook_url', '')) ?>" class="input w-full" placeholder="https://erp.example.com/hooks/orders"></label>
                <div class="flex items-center gap-2 mt-3 text-sm">
                    <span class="opacity-60"><?= e(__('Signing secret')) ?>:</span>
                    <code class="bg-base-200 px-2 py-1 rounded text-xs truncate max-w-56" dir="ltr"><?= e(setting('inventory.webhook_secret', '')) ?></code>
                    <button type="button" class="btn btn-ghost btn-xs" data-copy="<?= e(setting('inventory.webhook_secret', '')) ?>" data-copied="<?= e(__('Copied')) ?>"><?= icon('copy', 'size-3.5') ?></button>
                    <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="regenerate_secret" value="1" class="checkbox checkbox-xs"> <?= e(__('Regenerate')) ?></label>
                </div>
            </div>
        </div>
    </section>

    <div class="flex flex-wrap gap-3">
        <button class="btn btn-primary rounded-full shadow-lg shadow-primary/25"><?= icon('save', 'size-4') ?> <?= e(__('Save settings')) ?></button>
    </div>
</form>

<div class="grid xl:grid-cols-2 gap-6 mt-8">
    <section class="surface overflow-hidden">
        <div class="p-5 flex items-center justify-between">
            <h2 class="font-bold"><?= e(__('Sync runs')) ?></h2>
            <?php if ($current !== 'local'): ?>
                <form method="post" action="<?= e(url('/admin/inventory/sync')) ?>"><?= csrf_field() ?><button class="btn btn-primary btn-sm rounded-full"><?= icon('refresh-cw', 'size-4') ?> <?= e(__('Sync now')) ?></button></form>
            <?php endif; ?>
        </div>
        <table class="table table-modern table-sm">
            <tbody>
            <?php foreach ($runs as $r): ?>
                <tr>
                    <td><?= status_badge($r['status']) ?></td>
                    <td class="text-xs"><span class="badge badge-ghost badge-xs"><?= e($r['trigger_type']) ?></span> <?= e(format_date($r['started_at'], true)) ?></td>
                    <td class="text-xs opacity-70 max-w-xs truncate" title="<?= e($r['message']) ?>"><?= e($r['message']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$runs): ?><tr><td class="opacity-60"><?= e(__('No sync has run yet.')) ?></td></tr><?php endif; ?>
            </tbody>
        </table>
    </section>
    <section class="surface overflow-hidden">
        <div class="p-5 font-bold"><?= e(__('Latest stock movements')) ?></div>
        <table class="table table-modern table-sm">
            <tbody>
            <?php foreach ($logs as $l): ?>
                <tr>
                    <td class="font-mono text-xs"><?= e($l['sku']) ?></td>
                    <td><span class="font-bold <?= $l['change_qty'] >= 0 ? 'text-success' : 'text-error' ?>"><?= $l['change_qty'] >= 0 ? '+' : '' ?><?= (int) $l['change_qty'] ?></span> → <?= (int) $l['balance'] ?></td>
                    <td><span class="badge badge-ghost badge-xs"><?= e($l['source']) ?></span></td>
                    <td class="text-xs opacity-50"><?= e(format_date($l['created_at'], true)) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$logs): ?><tr><td class="opacity-60"><?= e(__('No movements yet.')) ?></td></tr><?php endif; ?>
            </tbody>
        </table>
    </section>
</div>

<?php push_start(); ?>
<script>
function inventoryForm() {
    return {
        provider: <?= js($current) ?>, testing: false, result: null,
        async test() {
            this.testing = true; this.result = null;
            const fd = new FormData(this.$root);
            try {
                const res = await fetch(<?= js(url('/admin/inventory/test')) ?>, { method: 'POST', body: fd, headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                this.result = await res.json();
            } catch (e) { this.result = { ok: false, message: String(e) }; }
            this.testing = false;
        },
    };
}
</script>
<?php push_end('scripts'); ?>
