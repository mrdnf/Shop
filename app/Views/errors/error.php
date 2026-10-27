<?php
/** Standalone error page: must not depend on any database. */
$titles = [
    403 => __('Access denied'),
    404 => __('Page not found'),
    405 => __('Method not allowed'),
    419 => __('Session expired'),
    422 => __('Something is not right'),
    500 => __('Something went wrong'),
];
$heading = $titles[$status] ?? __('Error');
$homeUrl = function_exists('url') ? url('/') : '/';
?><!doctype html>
<html lang="<?= e(locale()) ?>" dir="<?= is_rtl() ? 'rtl' : 'ltr' ?>" data-theme="<?= ($_COOKIE['mode'] ?? '') === 'dark' ? 'dark' : 'light' ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= (int) $status ?> · <?= e($heading) ?></title>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <style>[data-theme]{--color-primary:#7c3aed;--color-primary-content:#fff;--color-secondary:#db2777;--color-accent:#06b6d4}</style>
</head>
<body class="min-h-screen grid place-items-center bg-brand-soft p-6">
<div class="text-center max-w-lg">
    <div class="text-[7rem] md:text-[9rem] font-black leading-none text-brand"><?= (int) $status ?></div>
    <h1 class="text-2xl font-extrabold mt-2"><?= e($heading) ?></h1>
    <?php if (!empty($message)): ?>
        <p class="mt-3 opacity-70 whitespace-pre-line break-words"><?= e($message) ?></p>
    <?php endif; ?>
    <div class="mt-8 flex gap-2 justify-center">
        <a href="javascript:history.back()" class="btn btn-ghost rounded-full"><?= e(__('Go back')) ?></a>
        <a href="<?= e($homeUrl) ?>" class="btn btn-primary rounded-full"><?= e(__('Home')) ?></a>
    </div>
</div>
</body>
</html>
