<?php
$roles = ['admin' => ['shield-check', __('role.admin'), __('Full access including settings & integrations')],
          'staff' => ['briefcase', __('role.staff'), __('Orders, shipments, products & customers')],
          'driver' => ['bike', __('role.driver'), __('Driver app: assigned deliveries & live GPS')]];
?>
<div x-data="{ edit: {}, open(u) { this.edit = u; $refs.edit.showModal(); } }" class="grid xl:grid-cols-[1fr_24rem] gap-6">
    <section class="surface overflow-hidden h-fit">
        <div class="p-5 flex items-center justify-between">
            <h2 class="font-bold"><?= e(__('Team members')) ?> <span class="badge badge-sm"><?= count($users) ?><?= $limit !== null ? ' / ' . $limit : '' ?></span></h2>
        </div>
        <table class="table table-modern">
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><div class="flex items-center gap-3">
                        <span class="avatar avatar-placeholder"><span class="w-10 rounded-full bg-brand font-bold text-sm"><?= e(initials($u['name'])) ?></span></span>
                        <div><div class="font-semibold"><?= e($u['name']) ?></div><div class="text-xs opacity-60"><?= e($u['email']) ?></div></div>
                    </div></td>
                    <td><span class="badge badge-soft <?= $u['role'] === 'admin' ? 'badge-primary' : ($u['role'] === 'driver' ? 'badge-accent' : 'badge-secondary') ?> gap-1"><?= icon($roles[$u['role']][0], 'size-3') ?><?= e($roles[$u['role']][1]) ?></span></td>
                    <td><?= status_badge($u['status']) ?></td>
                    <td class="text-xs opacity-60"><?= e(__('Last login')) ?>: <?= e(format_date($u['last_login_at'], true)) ?></td>
                    <td class="text-end">
                        <button class="btn btn-ghost btn-xs btn-circle" @click="open(<?= e(js(['id' => $u['id'], 'name' => $u['name'], 'phone' => $u['phone'], 'role' => $u['role'], 'status' => $u['status']])) ?>)"><?= icon('pencil', 'size-4') ?></button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <form method="post" action="<?= e(url('/admin/staff')) ?>" class="surface p-6 space-y-4 h-fit">
        <?= csrf_field() ?>
        <h2 class="font-bold text-lg flex items-center gap-2"><?= icon('user-plus', 'size-5 text-primary') ?><?= e(__('Add team member')) ?></h2>
        <label class="field"><span class="label-text"><?= e(__('Full name')) ?></span><input name="name" class="input w-full" required></label>
        <label class="field"><span class="label-text"><?= e(__('Email')) ?></span><input type="email" name="email" class="input w-full" required></label>
        <label class="field"><span class="label-text"><?= e(__('Phone')) ?></span><input name="phone" dir="ltr" class="input w-full"></label>
        <label class="field"><span class="label-text"><?= e(__('Password')) ?></span><input type="password" name="password" minlength="8" class="input w-full" required autocomplete="new-password"></label>
        <div class="space-y-2">
            <?php foreach ($roles as $k => [$ic, $label, $desc]): ?>
                <label class="flex items-start gap-3 rounded-box border border-base-content/10 p-3 cursor-pointer has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                    <input type="radio" name="role" value="<?= $k ?>" class="radio radio-primary radio-sm mt-0.5" <?= $k === 'staff' ? 'checked' : '' ?>>
                    <span><span class="font-semibold flex items-center gap-1"><?= icon($ic, 'size-4') ?><?= e($label) ?></span><span class="text-xs opacity-60"><?= e($desc) ?></span></span>
                </label>
            <?php endforeach; ?>
        </div>
        <button class="btn btn-primary w-full rounded-full"><?= e(__('Add member')) ?></button>
    </form>

    <dialog x-ref="edit" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg mb-4"><?= e(__('Edit team member')) ?></h3>
            <form method="post" :action="<?= e(js(url('/admin/staff') . '/')) ?> + edit.id" class="space-y-4">
                <?= csrf_field() ?>
                <label class="field"><span class="label-text"><?= e(__('Full name')) ?></span><input name="name" x-model="edit.name" class="input w-full" required></label>
                <label class="field"><span class="label-text"><?= e(__('Phone')) ?></span><input name="phone" x-model="edit.phone" dir="ltr" class="input w-full"></label>
                <div class="grid grid-cols-2 gap-4">
                    <label class="field"><span class="label-text"><?= e(__('Role')) ?></span>
                        <select name="role" x-model="edit.role" class="select w-full"><?php foreach ($roles as $k => [, $label]): ?><option value="<?= $k ?>"><?= e($label) ?></option><?php endforeach; ?></select></label>
                    <label class="field"><span class="label-text"><?= e(__('Status')) ?></span>
                        <select name="status" x-model="edit.status" class="select w-full"><option value="active"><?= e(__('status.active')) ?></option><option value="blocked"><?= e(__('status.blocked')) ?></option></select></label>
                </div>
                <label class="field"><span class="label-text"><?= e(__('New password (optional)')) ?></span><input type="password" name="password" minlength="8" class="input w-full" autocomplete="new-password"></label>
                <div class="modal-action justify-between">
                    <button type="submit" :formaction="<?= e(js(url('/admin/staff') . '/')) ?> + edit.id + '/delete'" class="btn btn-ghost text-error" onclick="return confirm(<?= e(js(__('Remove this team member?'))) ?>)"><?= icon('trash-2', 'size-4') ?></button>
                    <div class="flex gap-2"><button type="button" class="btn btn-ghost" onclick="this.closest('dialog').close()"><?= e(__('Cancel')) ?></button><button class="btn btn-primary"><?= e(__('Save')) ?></button></div>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>
</div>
