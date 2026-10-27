<?php $c = $customer; ?>
<div class="grid xl:grid-cols-[20rem_1fr] gap-6">
    <aside class="space-y-4">
        <div class="surface p-6 text-center">
            <span class="avatar avatar-placeholder"><span class="w-20 rounded-3xl bg-brand text-2xl font-black"><?= e(initials($c['name'])) ?></span></span>
            <h2 class="font-bold text-xl mt-3"><?= e($c['name']) ?></h2>
            <div class="text-sm opacity-60"><?= e($c['email']) ?></div>
            <div class="text-sm opacity-60" dir="ltr"><?= e($c['phone'] ?? '') ?></div>
            <div class="mt-3"><?= status_badge($c['status']) ?></div>
            <form method="post" action="<?= e(url('/admin/customers/' . $c['id'] . '/toggle')) ?>" class="mt-4">
                <?= csrf_field() ?>
                <button class="btn btn-sm rounded-full <?= $c['status'] === 'active' ? 'btn-error btn-outline' : 'btn-success' ?>"><?= e($c['status'] === 'active' ? __('Block customer') : __('Unblock customer')) ?></button>
            </form>
        </div>
        <div class="surface p-5">
            <h3 class="font-bold mb-3"><?= e(__('Addresses')) ?></h3>
            <?php foreach ($addresses as $a): ?>
                <div class="text-sm mb-3"><div class="font-semibold"><?= e($a['label'] ?: $a['full_name']) ?></div><div class="opacity-70"><?= e($a['street']) ?>, <?= e($a['city']) ?></div></div>
            <?php endforeach; ?>
            <?php if (!$addresses): ?><p class="text-sm opacity-60"><?= e(__('No saved addresses')) ?></p><?php endif; ?>
        </div>
    </aside>
    <section class="surface overflow-hidden">
        <div class="p-5 font-bold"><?= e(__('Orders')) ?> <span class="badge badge-sm"><?= count($orders) ?></span></div>
        <table class="table table-modern">
            <tbody>
            <?php foreach ($orders as $o): ?>
                <tr class="cursor-pointer" onclick="location='<?= e(url('/admin/orders/' . $o['id'])) ?>'">
                    <td class="font-mono font-semibold"><?= e($o['number']) ?></td>
                    <td class="text-sm opacity-60"><?= e(format_date($o['created_at'], true)) ?></td>
                    <td><?= status_badge($o['status']) ?></td>
                    <td class="text-end font-bold"><?= e(money($o['total'])) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$orders): ?><tr><td class="opacity-60"><?= e(__('No orders yet')) ?></td></tr><?php endif; ?>
            </tbody>
        </table>
    </section>
</div>
