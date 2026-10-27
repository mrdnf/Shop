<div class="surface p-8 shadow-2xl shadow-primary/10">
    <h1 class="text-3xl font-black"><?= e(__('Create account')) ?></h1>
    <p class="opacity-60 mt-1"><?= e(__('Track orders, save addresses and checkout faster.')) ?></p>
    <form method="post" action="<?= e(url('/register')) ?>" class="mt-8 space-y-4">
        <?= csrf_field() ?>
        <input type="hidden" name="next" value="<?= e($next) ?>">
        <label class="field"><span class="label-text"><?= e(__('Full name')) ?></span><input name="name" value="<?= e(old('name')) ?>" required class="input w-full" autocomplete="name"></label>
        <div class="grid sm:grid-cols-2 gap-4">
            <label class="field"><span class="label-text"><?= e(__('Email')) ?></span><input type="email" name="email" value="<?= e(old('email')) ?>" required class="input w-full" autocomplete="email"></label>
            <label class="field"><span class="label-text"><?= e(__('Phone')) ?></span><input name="phone" dir="ltr" value="<?= e(old('phone')) ?>" class="input w-full" autocomplete="tel"></label>
        </div>
        <div class="grid sm:grid-cols-2 gap-4">
            <label class="field"><span class="label-text"><?= e(__('Password')) ?></span><input type="password" name="password" minlength="8" required class="input w-full" autocomplete="new-password"></label>
            <label class="field"><span class="label-text"><?= e(__('Confirm password')) ?></span><input type="password" name="password_confirmation" minlength="8" required class="input w-full" autocomplete="new-password"></label>
        </div>
        <button class="btn btn-primary w-full rounded-full shadow-xl shadow-primary/30 mt-2"><?= e(__('Create account')) ?></button>
    </form>
    <p class="text-center text-sm mt-6 opacity-80"><?= e(__('Already have an account?')) ?> <a href="<?= e(url('/login')) ?>" class="link link-primary font-semibold"><?= e(__('Sign in')) ?></a></p>
</div>
