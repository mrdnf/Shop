<?php
use App\Core\Auth;
use App\Core\Settings;
use App\Core\View;

$signup = Settings::get('platform.allow_signup', '0') === '1';
$langUrl = base_url('/lang/' . (locale() === 'ar' ? 'en' : 'ar'));
ob_start(); ?>
<header class="sticky top-0 z-40 glass border-b border-base-content/5">
    <div class="max-w-7xl mx-auto px-4 h-16 flex items-center gap-3">
        <?= View::partial('partials/brand', ['href' => base_url('/')]) ?>
        <nav class="hidden md:flex gap-1 ms-6">
            <a href="<?= e(base_url('/')) ?>#features" class="btn btn-ghost btn-sm rounded-full"><?= e(__('Features')) ?></a>
            <a href="<?= e(base_url('/')) ?>#pricing" class="btn btn-ghost btn-sm rounded-full"><?= e(__('Pricing')) ?></a>
            <a href="<?= e(base_url('/')) ?>#shops" class="btn btn-ghost btn-sm rounded-full"><?= e(__('Shops')) ?></a>
        </nav>
        <div class="ms-auto flex items-center gap-1">
            <?= View::partial('partials/controls', ['langUrl' => $langUrl]) ?>
            <a href="<?= e(base_url(Auth::check() ? '/platform' : '/platform/login')) ?>" class="btn btn-ghost btn-sm rounded-full"><?= e(Auth::check() ? __('Dashboard') : __('Sign in')) ?></a>
            <?php if ($signup): ?><a href="<?= e(base_url('/start')) ?>" class="btn btn-primary btn-sm rounded-full shadow-lg shadow-primary/30"><?= e(__('Start free')) ?></a><?php endif; ?>
        </div>
    </div>
</header>
<main><?= $content ?></main>
<footer class="border-t border-base-content/5 bg-base-100 py-8 text-center text-sm opacity-70">
    © <?= date('Y') ?> <?= e(company_name()) ?> · <?= e(__('Powered by')) ?> Matjar
</footer>
<?php
$content = ob_get_clean();
echo View::partial('layouts/base', ['content' => $content, 'title' => $title ?? '']);
