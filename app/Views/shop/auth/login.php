<div class="surface p-8 shadow-2xl shadow-primary/10">
    <h1 class="text-3xl font-black"><?= e(__('Welcome back')) ?></h1>
    <p class="opacity-60 mt-1"><?= e(__('Sign in to your account at :name', ['name' => company_name()])) ?></p>
    <form method="post" action="<?= e(url('/login')) ?>" class="mt-8 space-y-4">
        <?= csrf_field() ?>
        <input type="hidden" name="next" value="<?= e($next) ?>">
        <label class="field"><span class="label-text"><?= e(__('Email')) ?></span>
            <label class="input w-full"><?= icon('mail', 'size-4 opacity-50') ?><input type="email" name="email" value="<?= e(old('email')) ?>" required autofocus class="grow" autocomplete="email"></label>
        </label>
        <label class="field"><span class="label-text"><?= e(__('Password')) ?></span>
            <label class="input w-full" x-data="{ show: false }"><?= icon('lock', 'size-4 opacity-50') ?>
                <input :type="show ? 'text' : 'password'" type="password" name="password" required class="grow" autocomplete="current-password">
                <button type="button" @click="show = !show" class="opacity-50 hover:opacity-100"><?= icon('eye', 'size-4') ?></button>
            </label>
        </label>
        <button class="btn btn-primary w-full rounded-full shadow-xl shadow-primary/30 mt-2"><?= e(__('Sign in')) ?> <?= icon('arrow-right', 'size-4 flip-rtl') ?></button>
    </form>
    <p class="text-center text-sm mt-6 opacity-80"><?= e(__('New here?')) ?> <a href="<?= e(url('/register')) ?><?= $next ? '?next=' . e(rawurlencode($next)) : '' ?>" class="link link-primary font-semibold"><?= e(__('Create an account')) ?></a></p>
</div>
