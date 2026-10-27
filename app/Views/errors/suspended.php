<?php
use App\Core\View;

ob_start(); ?>
<div class="min-h-screen grid place-items-center p-6 bg-brand-soft">
    <div class="surface max-w-md p-10 text-center shadow-2xl">
        <div class="mx-auto size-16 rounded-2xl bg-warning/15 text-warning grid place-items-center"><?= icon('store', 'size-8') ?></div>
        <h1 class="text-2xl font-extrabold mt-5"><?= e(company_name()) ?></h1>
        <p class="mt-2 opacity-70"><?= e(__('This shop is temporarily unavailable. Please check back soon.')) ?></p>
    </div>
</div>
<?php
echo View::partial('layouts/base', ['content' => ob_get_clean(), 'title' => company_name()]);
