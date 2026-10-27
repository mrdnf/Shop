<section class="relative overflow-hidden min-h-[70vh] grid place-items-center px-4 py-16">
    <div class="absolute inset-0 bg-brand-soft"></div>
    <div class="blob size-96 bg-primary -top-20 -start-20"></div>
    <div class="blob size-80 bg-secondary bottom-0 end-0" style="animation-delay:-4s"></div>
    <div class="relative text-center max-w-xl w-full animate-fade-up">
        <span class="mx-auto size-20 rounded-3xl bg-brand grid place-items-center shadow-2xl shadow-primary/30"><?= icon('map-pinned', 'size-10') ?></span>
        <h1 class="text-4xl md:text-5xl font-black mt-6"><?= e(__('Track your order')) ?></h1>
        <p class="opacity-70 mt-3 text-lg"><?= e(__('Live status and driver location, updated in real time.')) ?></p>
        <form action="<?= e(url('/track')) ?>" class="mt-8 flex gap-2 glass p-2 rounded-full border border-base-content/10 shadow-xl">
            <label class="input input-lg flex-1 rounded-full border-0 bg-transparent focus-within:outline-none">
                <?= icon('package-search', 'size-5 opacity-50') ?>
                <input name="number" required placeholder="<?= e(__('Tracking number')) ?>" class="grow font-mono" dir="ltr">
            </label>
            <button class="btn btn-primary btn-lg rounded-full px-8"><?= e(__('Track')) ?></button>
        </form>
        <?php if (auth()): ?>
            <a href="<?= e(url('/account/orders')) ?>" class="link mt-5 inline-block opacity-70"><?= e(__('Or see all my orders')) ?></a>
        <?php endif; ?>
    </div>
</section>
