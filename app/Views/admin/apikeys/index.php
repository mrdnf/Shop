<?php if ($newKey): ?>
    <div role="alert" class="alert alert-success mb-6 items-start">
        <?= icon('key-round', 'size-5') ?>
        <div class="flex-1 min-w-0">
            <div class="font-bold"><?= e(__('Your new API key')) ?></div>
            <code class="block mt-1 font-mono text-sm break-all" dir="ltr"><?= e($newKey) ?></code>
            <div class="text-xs mt-1 opacity-80"><?= e(__('Copy it now, it will not be shown again.')) ?></div>
        </div>
        <button class="btn btn-sm" data-copy="<?= e($newKey) ?>" data-copied="<?= e(__('Copied')) ?>"><?= icon('copy', 'size-4') ?></button>
    </div>
<?php endif; ?>

<div class="grid xl:grid-cols-[1fr_24rem] gap-6">
    <div class="space-y-6">
        <section class="surface overflow-hidden">
            <div class="p-5 font-bold"><?= e(__('API keys')) ?></div>
            <table class="table table-modern">
                <tbody>
                <?php foreach ($keys as $k): ?>
                    <tr class="<?= $k['revoked_at'] ? 'opacity-50' : '' ?>">
                        <td><div class="font-semibold"><?= e($k['name']) ?></div><div class="font-mono text-xs opacity-60" dir="ltr"><?= e($k['key_prefix']) ?>…</div></td>
                        <td class="text-xs opacity-60"><?= e(__('Last used')) ?>: <?= e(format_date($k['last_used_at'], true)) ?></td>
                        <td class="text-end">
                            <?php if ($k['revoked_at']): ?><span class="badge badge-ghost"><?= e(__('Revoked')) ?></span>
                            <?php else: ?>
                                <form method="post" action="<?= e(url('/admin/api-keys/' . $k['id'] . '/revoke')) ?>" data-confirm="<?= e(__('Revoke this key? Integrations using it will stop working.')) ?>">
                                    <?= csrf_field() ?><button class="btn btn-ghost btn-xs text-error"><?= e(__('Revoke')) ?></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$keys): ?><tr><td class="opacity-60"><?= e(__('No API keys yet.')) ?></td></tr><?php endif; ?>
                </tbody>
            </table>
        </section>

        <section class="surface p-6">
            <h2 class="font-bold text-lg flex items-center gap-2"><?= icon('book-open', 'size-5 text-primary') ?><?= e(__('API reference')) ?></h2>
            <p class="text-sm opacity-70 mt-1"><?= e(__('Base URL')) ?>: <code class="font-mono" dir="ltr"><?= e($apiBase) ?></code> — <?= e(__('send')) ?> <code dir="ltr">Authorization: Bearer mk_…</code></p>
            <div class="overflow-x-auto mt-4">
                <table class="table table-sm" dir="ltr">
                    <tbody class="font-mono text-xs">
                    <tr><td><span class="badge badge-success badge-sm">GET</span></td><td>/products?updated_since=2025-01-01</td><td class="font-sans opacity-70"><?= e(__('Catalogue with stock balances')) ?></td></tr>
                    <tr><td><span class="badge badge-warning badge-sm">POST</span></td><td>/inventory</td><td class="font-sans opacity-70"><?= e(__('Push stock: {"items":[{"sku","quantity"}]}')) ?></td></tr>
                    <tr><td><span class="badge badge-success badge-sm">GET</span></td><td>/orders?status=pending&amp;since=…</td><td class="font-sans opacity-70"><?= e(__('Orders with items (for ERP import)')) ?></td></tr>
                    <tr><td><span class="badge badge-success badge-sm">GET</span></td><td>/orders/{number}</td><td class="font-sans opacity-70"><?= e(__('One order with shipments')) ?></td></tr>
                    <tr><td><span class="badge badge-warning badge-sm">POST</span></td><td>/shipments/{tracking}/events</td><td class="font-sans opacity-70"><?= e(__('Courier status push: {"status","note","location","lat","lng"}')) ?></td></tr>
                    </tbody>
                </table>
            </div>
            <div class="mt-4 text-sm"><?= e(__('Outgoing order webhook signing secret')) ?>: <code class="text-xs bg-base-200 px-2 py-1 rounded" dir="ltr"><?= e($webhookSecret) ?></code></div>
        </section>
    </div>

    <form method="post" action="<?= e(url('/admin/api-keys')) ?>" class="surface p-6 space-y-4 h-fit">
        <?= csrf_field() ?>
        <h2 class="font-bold text-lg flex items-center gap-2"><?= icon('key-round', 'size-5 text-primary') ?><?= e(__('New API key')) ?></h2>
        <label class="field"><span class="label-text"><?= e(__('Name')) ?></span><input name="name" class="input w-full" placeholder="<?= e(__('e.g. Odoo ERP, Warehouse, Courier')) ?>" required></label>
        <button class="btn btn-primary w-full rounded-full"><?= e(__('Generate key')) ?></button>
    </form>
</div>
