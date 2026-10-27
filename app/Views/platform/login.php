<div class="surface p-8 shadow-2xl shadow-primary/10">
    <span class="badge badge-primary badge-soft gap-1"><?= icon('shield-check', 'size-3') ?> <?= e(__('Platform owner')) ?></span>
    <h1 class="text-3xl font-black mt-3"><?= e(__('Platform sign in')) ?></h1>
    <p class="opacity-60 mt-1"><?= e(__('Manage shops, plans and platform branding.')) ?></p>
    <form method="post" action="<?= e(base_url('/platform/login')) ?>" class="mt-8 space-y-4">
        <?= csrf_field() ?>
        <label class="field"><span class="label-text"><?= e(__('Email')) ?></span>
            <label class="input w-full"><?= icon('mail', 'size-4 opacity-50') ?><input type="email" name="email" value="<?= e(old('email')) ?>" required autofocus class="grow"></label></label>
        <label class="field"><span class="label-text"><?= e(__('Password')) ?></span>
            <label class="input w-full"><?= icon('lock', 'size-4 opacity-50') ?><input type="password" name="password" required class="grow"></label></label>
        <button class="btn btn-primary w-full rounded-full shadow-xl shadow-primary/30"><?= e(__('Sign in')) ?></button>
    </form>
</div>
