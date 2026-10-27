<?php
use App\Core\View;
use App\Services\Theme;

$favicon = setting('company.favicon');
$pageTitle = !empty($title) && $title !== company_name() ? $title . ' · ' . company_name() : company_name();
?><!doctype html>
<html lang="<?= e(locale()) ?>" dir="<?= is_rtl() ? 'rtl' : 'ltr' ?>" data-theme="<?= e(Theme::dataTheme()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <meta name="theme-color" content="<?= e(Theme::get('primary')) ?>">
    <title><?= e($pageTitle) ?></title>
    <?php if ($favicon): ?><link rel="icon" href="<?= e(upload_url($favicon)) ?>"><?php endif; ?>
    <?php if (Theme::mode() === 'system'): ?>
        <script>if (matchMedia('(prefers-color-scheme: dark)').matches) document.documentElement.setAttribute('data-theme', 'dark');</script>
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="<?= e(Theme::fontsHref()) ?>">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <style><?= Theme::css() ?></style>
    <?= $head ?? '' ?>
    <?= View::stack('head') ?>
    <script defer src="<?= asset('vendor/alpine.min.js') ?>"></script>
    <script src="<?= asset('vendor/lucide.min.js') ?>"></script>
    <script defer src="<?= asset('js/app.js') ?>"></script>
</head>
<body class="min-h-screen <?= e($bodyClass ?? '') ?>">
<?= $content ?>
<?= View::partial('partials/flash') ?>
<?= $scripts ?? '' ?>
<?= View::stack('scripts') ?>
</body>
</html>
