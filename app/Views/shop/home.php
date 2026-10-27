<?php
use App\Core\View;

$heroImage = setting('theme.hero_image');
?>
<!-- Hero -->
<section class="relative overflow-hidden">
    <div class="absolute inset-0 bg-brand-soft"></div>
    <div class="blob size-[28rem] bg-primary -top-40 -start-32"></div>
    <div class="blob size-[22rem] bg-secondary top-20 end-0" style="animation-delay:-3s"></div>
    <div class="blob size-[18rem] bg-accent bottom-0 start-1/3" style="animation-delay:-5s"></div>

    <div class="relative max-w-7xl mx-auto px-4 py-16 md:py-24 grid lg:grid-cols-2 gap-12 items-center">
        <div class="animate-fade-up">
            <span class="badge badge-lg glass border-base-content/10 gap-2 font-medium">
                <span class="live-dot"></span> <?= e(__('Live stock · Real-time tracking')) ?>
            </span>
            <h1 class="mt-6 text-4xl md:text-6xl font-black tracking-tight leading-[1.05]">
                <span class="text-brand"><?= e(setting('theme.hero_title_' . locale()) ?: __('Everything you love, delivered fast')) ?></span>
            </h1>
            <p class="mt-5 text-lg opacity-75 max-w-xl"><?= e(setting('theme.hero_subtitle_' . locale()) ?: __('Live stock, secure checkout and real-time delivery tracking.')) ?></p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="<?= e(url('/products')) ?>" class="btn btn-primary btn-lg rounded-full px-8 shadow-xl shadow-primary/30"><?= icon('shopping-bag', 'size-5') ?> <?= e(__('Shop now')) ?></a>
                <a href="<?= e(url('/track')) ?>" class="btn btn-lg rounded-full glass border-base-content/10"><?= icon('map-pinned', 'size-5') ?> <?= e(__('Track order')) ?></a>
            </div>
            <div class="mt-10 grid grid-cols-3 gap-4 max-w-md">
                <?php foreach ([['truck', __('Fast delivery')], ['shield-check', __('Secure checkout')], ['rotate-ccw', __('Easy returns')]] as [$ic, $label]): ?>
                    <div class="flex flex-col items-center text-center gap-2">
                        <span class="size-11 rounded-2xl glass border border-base-content/5 grid place-items-center text-primary"><?= icon($ic) ?></span>
                        <span class="text-xs font-semibold opacity-75"><?= e($label) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="relative hidden lg:block animate-fade-up" style="animation-delay:.15s">
            <?php if ($heroImage): ?>
                <img src="<?= e(upload_url($heroImage)) ?>" alt="" class="rounded-[2.5rem] shadow-2xl shadow-primary/20 aspect-[4/3] object-cover w-full rotate-1">
            <?php else: ?>
                <div class="relative aspect-[4/3]">
                    <div class="absolute inset-6 rounded-[2.5rem] bg-brand rotate-3 shadow-2xl shadow-primary/30"></div>
                    <div class="absolute inset-6 rounded-[2.5rem] glass border border-white/20 -rotate-2 p-8 flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="font-bold"><?= e(__('Order')) ?> #ORD-2048</span>
                            <span class="badge badge-accent"><?= e(__('status.out_for_delivery')) ?></span>
                        </div>
                        <div class="space-y-3">
                            <?php foreach (['picked_up', 'in_transit', 'out_for_delivery'] as $i => $s): ?>
                                <div class="flex items-center gap-3">
                                    <span class="size-8 rounded-full bg-primary text-primary-content grid place-items-center"><?= icon(App\Services\Delivery\DeliveryService::ICONS[$s], 'size-4') ?></span>
                                    <div class="flex-1 h-2 rounded-full bg-base-content/10 overflow-hidden"><div class="h-full bg-brand" style="width: <?= 100 - $i * 25 ?>%"></div></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="avatar avatar-placeholder"><span class="w-10 rounded-full bg-secondary text-secondary-content"><?= icon('bike', 'size-5') ?></span></span>
                            <div>
                                <div class="font-semibold text-sm"><?= e(__('Your driver is 5 minutes away')) ?></div>
                                <div class="text-xs opacity-60 flex items-center gap-1"><span class="live-dot size-1.5"></span><?= e(__('Live location')) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php if ($categories): ?>
<section class="max-w-7xl mx-auto px-4 mt-6">
    <div class="flex items-end justify-between mb-6">
        <h2 class="section-title"><?= e(__('Shop by category')) ?></h2>
        <a href="<?= e(url('/products')) ?>" class="link link-primary text-sm font-semibold"><?= e(__('View all')) ?></a>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <?php foreach ($categories as $i => $c): ?>
            <a href="<?= e(url('/category/' . $c['slug'])) ?>" class="group surface p-5 card-lift text-center animate-fade-up" style="animation-delay: <?= $i * 0.05 ?>s">
                <span class="mx-auto size-14 rounded-2xl bg-primary/10 text-primary grid place-items-center group-hover:bg-brand transition duration-300">
                    <?= icon($c['icon'] ?: 'tag', 'size-7') ?>
                </span>
                <div class="mt-3 font-bold"><?= e(tr($c, 'name')) ?></div>
                <div class="text-xs opacity-60"><?= e(__(':n products', ['n' => $c['products_count']])) ?></div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php foreach ([[__('Featured picks'), $featured, 'sparkles'], [__('New arrivals'), $latest, 'zap']] as [$heading, $list, $ic]): ?>
    <?php if (!$list) continue; ?>
    <section class="max-w-7xl mx-auto px-4 mt-16">
        <div class="flex items-end justify-between mb-6">
            <h2 class="section-title flex items-center gap-2"><?= icon($ic, 'size-7 text-primary') ?><?= e($heading) ?></h2>
            <a href="<?= e(url('/products')) ?>" class="btn btn-ghost btn-sm rounded-full"><?= e(__('View all')) ?> <?= icon('arrow-right', 'size-4 flip-rtl') ?></a>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
            <?php foreach ($list as $p): ?><?= View::partial('partials/product_card', ['p' => $p]) ?><?php endforeach; ?>
        </div>
    </section>
<?php endforeach; ?>

<?php if (!$featured && !$latest): ?>
    <div class="max-w-3xl mx-auto px-4 mt-10 surface">
        <?= View::partial('partials/empty', ['icon' => 'store', 'title' => __('The shop is getting ready'), 'text' => __('Products will appear here soon.')]) ?>
    </div>
<?php endif; ?>

<!-- Tracking CTA -->
<section class="max-w-7xl mx-auto px-4 mt-20">
    <div class="relative overflow-hidden rounded-[2rem] bg-brand p-8 md:p-14 shadow-2xl shadow-primary/25">
        <div class="blob size-80 bg-white/30 -top-20 -end-10"></div>
        <div class="relative grid md:grid-cols-2 gap-8 items-center">
            <div>
                <h2 class="text-3xl md:text-4xl font-black"><?= e(__('Where is my order?')) ?></h2>
                <p class="mt-3 opacity-90 text-lg"><?= e(__('Enter your tracking number and watch your delivery move on the map.')) ?></p>
            </div>
            <form action="<?= e(url('/track')) ?>" class="flex gap-2 bg-white/15 backdrop-blur p-2 rounded-full">
                <input name="number" required placeholder="<?= e(__('Tracking number')) ?>" class="input input-lg flex-1 rounded-full bg-white/90 text-gray-900 border-0 placeholder:text-gray-500">
                <button class="btn btn-lg rounded-full bg-gray-900 text-white border-0 hover:bg-black"><?= icon('search', 'size-5') ?></button>
            </form>
        </div>
    </div>
</section>
