<?php
use App\Core\Tenant;
use App\Core\View;

$isPlatform = !Tenant::current();
$home = $isPlatform ? base_url('/') : url('/');
$langUrl = $isPlatform ? base_url('/lang/' . (locale() === 'ar' ? 'en' : 'ar')) : url('/lang/' . (locale() === 'ar' ? 'en' : 'ar'));
$features = $isPlatform
    ? [['server', __('A dedicated database per shop')], ['palette', __('Per-company branding & themes')], ['shield-check', __('Secure, isolated tenants')]]
    : [['refresh-cw', __('Live stock from our warehouse')], ['truck', __('Real-time delivery tracking')], ['shield-check', __('Secure checkout')]];
ob_start(); ?>
<div class="min-h-screen grid lg:grid-cols-2">
    <section class="relative hidden lg:flex flex-col justify-between overflow-hidden bg-brand p-12">
        <div class="blob size-96 bg-white/25 -top-20 -start-20"></div>
        <div class="blob size-80 bg-white/20 bottom-0 end-0" style="animation-delay:-4s"></div>
        <div class="relative"><?= View::partial('partials/brand', ['href' => $home, 'size' => 'lg', 'forceName' => true]) ?></div>
        <div class="relative space-y-6 max-w-md">
            <h2 class="text-4xl font-black leading-tight"><?= e($isPlatform ? __('Run every shop from one place.') : (setting('theme.hero_title_' . locale()) ?: company_name())) ?></h2>
            <ul class="space-y-3">
                <?php foreach ($features as [$ic, $text]): ?>
                    <li class="flex items-center gap-3"><span class="size-9 rounded-xl bg-white/20 grid place-items-center"><?= icon($ic, 'size-5') ?></span><span class="font-medium"><?= e($text) ?></span></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <p class="relative text-sm opacity-75">© <?= date('Y') ?> <?= e(company_name()) ?></p>
    </section>
    <section class="flex flex-col bg-brand-soft">
        <div class="flex items-center justify-between p-4">
            <div class="lg:hidden"><?= View::partial('partials/brand', ['href' => $home]) ?></div>
            <a href="<?= e($home) ?>" class="btn btn-ghost btn-sm hidden lg:inline-flex"><?= icon('arrow-left', 'size-4 flip-rtl') ?> <?= e(__('Back')) ?></a>
            <div class="flex gap-1"><?= View::partial('partials/controls', ['langUrl' => $langUrl]) ?></div>
        </div>
        <div class="flex-1 grid place-items-center p-4">
            <div class="w-full max-w-md animate-fade-up"><?= $content ?></div>
        </div>
    </section>
</div>
<?php
$content = ob_get_clean();
echo View::partial('layouts/base', ['content' => $content, 'title' => $title ?? '']);
