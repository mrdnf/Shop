<?php
use App\Core\Csrf;

$steps = [__('Requirements'), __('Database'), __('Setup type'), __('Company & admin')];
$step = $step ?? 1;
?><!doctype html>
<html lang="<?= e(locale()) ?>" dir="<?= is_rtl() ? 'rtl' : 'ltr' ?>" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
    <title><?= e(__('Install Matjar')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Cairo:wght@400;600;700;800&display=swap">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <style>[data-theme=light]{--color-primary:#7c3aed;--color-primary-content:#fff;--color-secondary:#db2777;--color-secondary-content:#fff;--color-accent:#06b6d4}html[lang=ar]{--font-sans:"Cairo","Plus Jakarta Sans",ui-sans-serif,system-ui,sans-serif}</style>
    <script defer src="<?= asset('vendor/alpine.min.js') ?>"></script>
    <script src="<?= asset('vendor/lucide.min.js') ?>"></script>
    <script defer src="<?= asset('js/app.js') ?>"></script>
</head>
<body class="min-h-screen bg-brand-soft">
<div class="max-w-3xl mx-auto px-4 py-10">
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-3">
            <span class="bg-brand size-11 rounded-2xl grid place-items-center font-black shadow-lg shadow-primary/30">M</span>
            <div>
                <div class="font-extrabold text-xl">Matjar <span class="text-brand">SaaS</span></div>
                <div class="text-xs opacity-60"><?= e(__('Installation wizard')) ?></div>
            </div>
        </div>
        <a class="btn btn-ghost btn-sm" href="<?= e(base_url('/install/lang/' . (locale() === 'ar' ? 'en' : 'ar'))) ?>"><?= locale() === 'ar' ? 'English' : 'العربية' ?></a>
    </div>
    <ul class="steps w-full mb-8 text-xs">
        <?php foreach ($steps as $i => $label): ?>
            <li class="step <?= $i + 1 <= $step ? 'step-primary' : '' ?>"><?= e($label) ?></li>
        <?php endforeach; ?>
    </ul>
    <div class="surface p-6 md:p-10 shadow-2xl shadow-primary/10 animate-fade-up"><?= $content ?></div>
    <p class="text-center text-xs opacity-50 mt-6">Matjar v<?= APP_VERSION ?> · PHP <?= PHP_VERSION ?></p>
</div>
<?= App\Core\View::partial('partials/flash') ?>
</body>
</html>
