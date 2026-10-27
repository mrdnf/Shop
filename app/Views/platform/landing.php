<section class="relative overflow-hidden">
    <div class="absolute inset-0 bg-brand-soft"></div>
    <div class="blob size-[30rem] bg-primary -top-40 -start-40"></div>
    <div class="blob size-[24rem] bg-secondary top-10 end-0" style="animation-delay:-3s"></div>
    <div class="relative max-w-6xl mx-auto px-4 py-20 md:py-28 text-center animate-fade-up">
        <span class="badge badge-lg glass border-base-content/10 gap-2"><span class="live-dot"></span><?= e(__('Multi-tenant e-commerce platform')) ?></span>
        <h1 class="mt-6 text-4xl md:text-7xl font-black tracking-tight leading-[1.05]">
            <?= e(setting('theme.hero_title_' . locale()) ?: __('Launch your online shop in minutes')) ?>
        </h1>
        <p class="mt-6 text-lg md:text-xl opacity-70 max-w-2xl mx-auto"><?= e(setting('theme.hero_subtitle_' . locale()) ?: __('Live ERP stock sync, delivery tracking, Arabic & English, and your own brand. Every shop gets its own secure database.')) ?></p>
        <div class="mt-10 flex flex-wrap justify-center gap-3">
            <?php if ($signup): ?><a href="<?= e(base_url('/start')) ?>" class="btn btn-primary btn-lg rounded-full px-10 shadow-2xl shadow-primary/30"><?= icon('rocket', 'size-5') ?> <?= e(__('Create your shop')) ?></a><?php endif; ?>
            <a href="#pricing" class="btn btn-lg rounded-full glass border-base-content/10"><?= e(__('See pricing')) ?></a>
        </div>
    </div>
</section>

<section id="features" class="max-w-6xl mx-auto px-4 py-16">
    <div class="grid md:grid-cols-3 gap-5">
        <?php foreach ([
            ['refresh-cw', __('Live inventory'), __('Connect any ERP, POS, spreadsheet or database. Stock syncs on schedule, in real time at checkout, or by push API.')],
            ['map-pinned', __('Delivery with live tracking'), __('Own fleet with driver app and GPS, third-party couriers, or courier APIs. Customers watch the map.')],
            ['palette', __('Your brand, your way'), __('Logo, colours, fonts, Arabic/English, dark/light, top or sidebar menu — per company.')],
            ['database', __('Database per shop'), __('Every shop runs on its own MySQL database for isolation, performance and easy backups.')],
            ['users', __('Admin, staff, drivers, customers'), __('Clear roles with their own areas: shop admin, team, driver app and customer accounts.')],
            ['globe', __('Custom domains'), __('Shops live at /s/your-shop, on a subdomain, or on their own domain.')],
        ] as $i => [$ic, $title, $text]): ?>
            <div class="surface p-6 card-lift animate-fade-up" style="animation-delay: <?= $i * 0.05 ?>s">
                <span class="size-12 rounded-2xl bg-brand grid place-items-center shadow-lg shadow-primary/25"><?= icon($ic, 'size-6') ?></span>
                <h3 class="font-bold text-lg mt-4"><?= e($title) ?></h3>
                <p class="opacity-70 mt-1 text-sm leading-relaxed"><?= e($text) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php if ($plans): ?>
<section id="pricing" class="max-w-6xl mx-auto px-4 py-16">
    <h2 class="section-title text-center"><?= e(__('Simple pricing')) ?></h2>
    <div class="grid md:grid-cols-3 gap-6 mt-10">
        <?php foreach ($plans as $p): ?>
            <div class="relative surface p-8 flex flex-col <?= $p['is_featured'] ? 'ring-2 ring-primary shadow-2xl shadow-primary/20 md:-translate-y-3' : '' ?>">
                <?php if ($p['is_featured']): ?><span class="absolute -top-3 start-1/2 -translate-x-1/2 rtl:translate-x-1/2 badge badge-primary"><?= e(__('Most popular')) ?></span><?php endif; ?>
                <h3 class="font-bold text-xl"><?= e(locale() === 'ar' && $p['name_ar'] ? $p['name_ar'] : $p['name']) ?></h3>
                <div class="mt-4"><span class="text-5xl font-black"><?= (float) $p['price'] > 0 ? '$' . e(rtrim(rtrim(number_format((float) $p['price'], 2), '0'), '.')) : e(__('Free')) ?></span>
                    <?php if ((float) $p['price'] > 0): ?><span class="opacity-60">/ <?= e(__($p['billing_cycle'] === 'yearly' ? 'year' : 'month')) ?></span><?php endif; ?></div>
                <ul class="mt-6 space-y-2 flex-1">
                    <?php foreach (array_filter(preg_split('/\r?\n/', (string) $p['features']) ?: []) as $f): ?>
                        <li class="flex gap-2 text-sm"><?= icon('circle-check', 'size-4 text-success shrink-0 mt-0.5') ?><?= e($f) ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php if ($signup): ?><a href="<?= e(base_url('/start?plan=' . $p['id'])) ?>" class="btn mt-8 rounded-full <?= $p['is_featured'] ? 'btn-primary' : '' ?>"><?= e(__('Get started')) ?></a><?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($shops): ?>
<section id="shops" class="max-w-6xl mx-auto px-4 py-16">
    <h2 class="section-title text-center"><?= e(__('Shops on our platform')) ?></h2>
    <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4 mt-10">
        <?php foreach ($shops as $shop): ?>
            <a href="<?= e($shop['url']) ?>" class="surface p-5 card-lift flex items-center gap-3">
                <span class="size-11 rounded-xl bg-brand grid place-items-center font-black text-sm"><?= e(initials($shop['name'])) ?></span>
                <div class="min-w-0"><div class="font-bold truncate"><?= e($shop['name']) ?></div><div class="text-xs opacity-60 truncate"><?= e(preg_replace('#^https?://#', '', $shop['url'])) ?></div></div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
