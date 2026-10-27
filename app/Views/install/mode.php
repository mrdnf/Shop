<h1 class="text-2xl font-extrabold"><?= e(__('How will you use Matjar?')) ?></h1>
<p class="mt-1 opacity-70"><?= e(__('This decides the roles and areas of the system. Both options give every shop its own database.')) ?></p>

<form method="post" action="<?= e(base_url('/install/mode')) ?>" class="mt-8" x-data="{ mode: '<?= e($mode) ?>' }">
    <?= csrf_field() ?>
    <div class="grid md:grid-cols-2 gap-4">
        <?php
        $options = [
            'single' => ['store', __('One shop'), __('Admin + Users'), [
                __('One company, one storefront'),
                __('Admin area to run the shop'),
                __('Users (customers) area'),
                __('Driver app for your fleet'),
            ]],
            'multi' => ['building-2', __('Multi shop (SaaS)'), __('Platform admin + Shop admins + Users'), [
                __('Sell the platform to many companies'),
                __('Each shop: own database, branding, domain'),
                __('Plans, limits & self sign-up'),
                __('Shops at /s/{shop}, subdomain or custom domain'),
            ]],
        ];
        foreach ($options as $key => [$ic, $label, $roles, $bullets]): ?>
            <label class="relative cursor-pointer rounded-box border-2 p-6 transition"
                   :class="mode === '<?= $key ?>' ? 'border-primary bg-primary/5 shadow-xl shadow-primary/10' : 'border-base-content/10 hover:border-primary/40'">
                <input type="radio" name="mode" value="<?= $key ?>" class="hidden" x-model="mode">
                <span class="absolute top-4 end-4 size-6 rounded-full border-2 grid place-items-center"
                      :class="mode === '<?= $key ?>' ? 'border-primary bg-primary text-primary-content' : 'border-base-content/20'">
                    <?= icon('check', 'size-3.5') ?>
                </span>
                <span class="size-14 rounded-2xl bg-brand grid place-items-center shadow-lg shadow-primary/30"><?= icon($ic, 'size-7') ?></span>
                <span class="block mt-4 text-xl font-extrabold"><?= e($label) ?></span>
                <span class="block text-sm font-semibold text-primary"><?= e($roles) ?></span>
                <ul class="mt-4 space-y-2 text-sm">
                    <?php foreach ($bullets as $b): ?>
                        <li class="flex gap-2"><?= icon('circle-check', 'size-4 text-success shrink-0 mt-0.5') ?><span><?= e($b) ?></span></li>
                    <?php endforeach; ?>
                </ul>
            </label>
        <?php endforeach; ?>
    </div>
    <div class="flex justify-between mt-8">
        <a href="<?= e(base_url('/install/database')) ?>" class="btn btn-ghost rounded-full"><?= icon('arrow-left', 'size-4 flip-rtl') ?> <?= e(__('Back')) ?></a>
        <button class="btn btn-primary rounded-full px-8"><?= e(__('Continue')) ?> <?= icon('arrow-right', 'size-4 flip-rtl') ?></button>
    </div>
</form>
