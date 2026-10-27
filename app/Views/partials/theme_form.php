<?php
/**
 * Branding form shared by the shop admin and the SaaS platform settings.
 * @var array $theme @var array $fontsEn @var array $fontsAr @var array $presets @var string $action
 */
$t = static fn (string $k) => (string) ($theme[$k] ?? '');
$state = [
    'primary' => $t('theme.primary'), 'secondary' => $t('theme.secondary'), 'accent' => $t('theme.accent'),
    'fontEn' => $t('theme.font_en'), 'fontAr' => $t('theme.font_ar'), 'mode' => $t('theme.default_mode') ?: 'light',
    'menu' => $t('theme.menu_layout') ?: 'top', 'adminMenu' => $t('theme.admin_menu_layout') ?: 'sidebar',
    'radius' => $t('theme.radius') ?: 'soft', 'locale' => $t('theme.default_locale') ?: 'en',
    'name' => $t('company.name'), 'nameAr' => $t('company.name_ar'),
];
$images = [
    'logo' => [__('Logo'), 'company.logo'],
    'logo_dark' => [__('Logo for dark mode'), 'company.logo_dark'],
    'favicon' => [__('Favicon'), 'company.favicon'],
    'hero_image' => [__('Homepage hero image'), 'theme.hero_image'],
];
?>
<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" x-data="themeEditor(<?= e(js($state)) ?>)" class="grid 2xl:grid-cols-[1fr_26rem] gap-6">
    <?= csrf_field() ?>
    <div class="space-y-6">
        <section class="surface p-6">
            <h2 class="font-bold text-lg flex items-center gap-2"><?= icon('building-2', 'size-5 text-primary') ?><?= e(__('Company identity')) ?></h2>
            <div class="grid md:grid-cols-2 gap-4 mt-4">
                <label class="field"><span class="label-text"><?= e(__('Company name (English)')) ?></span><input name="company_name" x-model="s.name" class="input w-full" required></label>
                <label class="field"><span class="label-text"><?= e(__('Company name (Arabic)')) ?></span><input name="company_name_ar" x-model="s.nameAr" dir="rtl" class="input w-full"></label>
                <label class="field"><span class="label-text"><?= e(__('Tagline (English)')) ?></span><input name="tagline_en" value="<?= e($t('company.tagline_en')) ?>" class="input w-full"></label>
                <label class="field"><span class="label-text"><?= e(__('Tagline (Arabic)')) ?></span><input name="tagline_ar" value="<?= e($t('company.tagline_ar')) ?>" dir="rtl" class="input w-full"></label>
            </div>
            <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mt-6">
                <?php foreach ($images as $field => [$label, $key]): $current = $t($key); ?>
                    <div class="rounded-box border border-dashed border-base-content/20 p-3">
                        <div class="text-sm font-medium mb-2"><?= e($label) ?></div>
                        <div class="h-20 rounded-lg <?= $field === 'logo_dark' ? 'bg-neutral' : 'bg-base-200' ?> grid place-items-center overflow-hidden">
                            <img id="prev-<?= $field ?>" src="<?= e(upload_url($current) ?? '') ?>" class="max-h-16 max-w-full object-contain <?= $current ? '' : 'hidden' ?>" alt="">
                            <?php if (!$current): ?><?= icon('image', 'size-6 opacity-30') ?><?php endif; ?>
                        </div>
                        <input type="file" name="<?= $field ?>" accept="image/*" data-preview="#prev-<?= $field ?>" class="file-input file-input-xs w-full mt-2">
                        <?php if ($current): ?><label class="flex items-center gap-1 text-xs mt-1"><input type="checkbox" name="remove_<?= $field ?>" value="1" class="checkbox checkbox-xs"> <?= e(__('Remove')) ?></label><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="surface p-6">
            <h2 class="font-bold text-lg flex items-center gap-2"><?= icon('palette', 'size-5 text-primary') ?><?= e(__('Colours')) ?></h2>
            <div class="flex flex-wrap gap-2 mt-4">
                <?php foreach ($presets as $name => [$p, $s2, $a]): ?>
                    <button type="button" @click="s.primary='<?= $p ?>'; s.secondary='<?= $s2 ?>'; s.accent='<?= $a ?>'" class="btn btn-sm rounded-full bg-base-100 gap-2">
                        <span class="flex -space-x-1.5 rtl:space-x-reverse"><span class="size-4 rounded-full ring-2 ring-base-100" style="background:<?= $p ?>"></span><span class="size-4 rounded-full ring-2 ring-base-100" style="background:<?= $s2 ?>"></span><span class="size-4 rounded-full ring-2 ring-base-100" style="background:<?= $a ?>"></span></span>
                        <?= e($name) ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="grid sm:grid-cols-3 gap-4 mt-5">
                <?php foreach (['primary' => __('Primary'), 'secondary' => __('Secondary'), 'accent' => __('Accent')] as $k => $label): ?>
                    <label class="field"><span class="label-text"><?= e($label) ?></span>
                        <div class="flex items-center gap-2">
                            <input type="color" x-model="s.<?= $k ?>" class="h-10 w-12 rounded-field cursor-pointer border border-base-content/10 shrink-0">
                            <input name="<?= $k ?>" x-model="s.<?= $k ?>" class="input w-full font-mono uppercase" maxlength="7" dir="ltr">
                        </div></label>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="surface p-6">
            <h2 class="font-bold text-lg flex items-center gap-2"><?= icon('type', 'size-5 text-primary') ?><?= e(__('Typography & language')) ?></h2>
            <div class="grid md:grid-cols-3 gap-4 mt-4">
                <label class="field"><span class="label-text"><?= e(__('English font')) ?></span>
                    <select name="font_en" x-model="s.fontEn" @change="loadFont(s.fontEn)" class="select w-full">
                        <?php foreach ($fontsEn as $f): ?><option value="<?= e($f) ?>" style="font-family:'<?= e($f) ?>'"><?= e($f) ?></option><?php endforeach; ?>
                    </select></label>
                <label class="field"><span class="label-text"><?= e(__('Arabic font')) ?></span>
                    <select name="font_ar" x-model="s.fontAr" @change="loadFont(s.fontAr)" class="select w-full">
                        <?php foreach ($fontsAr as $f): ?><option value="<?= e($f) ?>"><?= e($f) ?></option><?php endforeach; ?>
                    </select></label>
                <label class="field"><span class="label-text"><?= e(__('Default language')) ?></span>
                    <select name="default_locale" x-model="s.locale" class="select w-full"><option value="en">English</option><option value="ar">العربية</option></select></label>
            </div>
        </section>

        <section class="surface p-6">
            <h2 class="font-bold text-lg flex items-center gap-2"><?= icon('layout-template', 'size-5 text-primary') ?><?= e(__('Layout & appearance')) ?></h2>
            <div class="grid md:grid-cols-2 gap-6 mt-4">
                <div>
                    <div class="label-text mb-2"><?= e(($scope ?? 'shop') === 'shop' ? __('Storefront & customer area menu') : __('Public website menu')) ?></div>
                    <div class="grid grid-cols-2 gap-3">
                        <?php foreach (['top' => [__('Top menu'), 'panel-top'], 'sidebar' => [__('Sidebar menu'), 'panel-left']] as $k => [$label, $ic]): ?>
                            <label class="cursor-pointer rounded-box border-2 p-3 text-center transition" :class="s.menu==='<?= $k ?>' ? 'border-primary bg-primary/5' : 'border-base-content/10'">
                                <input type="radio" name="menu_layout" value="<?= $k ?>" x-model="s.menu" class="hidden"><?= icon($ic, 'size-6 mx-auto text-primary') ?><div class="text-sm font-semibold mt-1"><?= e($label) ?></div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div>
                    <div class="label-text mb-2"><?= e(__('Admin panel menu')) ?></div>
                    <div class="grid grid-cols-2 gap-3">
                        <?php foreach (['top' => [__('Top menu'), 'panel-top'], 'sidebar' => [__('Sidebar menu'), 'panel-left']] as $k => [$label, $ic]): ?>
                            <label class="cursor-pointer rounded-box border-2 p-3 text-center transition" :class="s.adminMenu==='<?= $k ?>' ? 'border-primary bg-primary/5' : 'border-base-content/10'">
                                <input type="radio" name="admin_menu_layout" value="<?= $k ?>" x-model="s.adminMenu" class="hidden"><?= icon($ic, 'size-6 mx-auto text-primary') ?><div class="text-sm font-semibold mt-1"><?= e($label) ?></div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div>
                    <div class="label-text mb-2"><?= e(__('Default mode')) ?></div>
                    <div class="join w-full">
                        <?php foreach (['light' => [__('Light'), 'sun'], 'dark' => [__('Dark'), 'moon'], 'system' => [__('System'), 'monitor']] as $k => [$label, $ic]): ?>
                            <label class="join-item btn flex-1" :class="s.mode==='<?= $k ?>' && 'btn-primary'"><input type="radio" name="default_mode" value="<?= $k ?>" x-model="s.mode" class="hidden"><?= icon($ic, 'size-4') ?><?= e($label) ?></label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div>
                    <div class="label-text mb-2"><?= e(__('Corners')) ?></div>
                    <div class="join w-full">
                        <?php foreach (['sharp' => __('Sharp'), 'soft' => __('Soft'), 'round' => __('Round')] as $k => $label): ?>
                            <label class="join-item btn flex-1" :class="s.radius==='<?= $k ?>' && 'btn-primary'"><input type="radio" name="radius" value="<?= $k ?>" x-model="s.radius" class="hidden"><?= e($label) ?></label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="surface p-6">
            <h2 class="font-bold text-lg flex items-center gap-2"><?= icon('megaphone', 'size-5 text-primary') ?><?= e(__('Homepage hero')) ?></h2>
            <div class="grid md:grid-cols-2 gap-4 mt-4">
                <label class="field"><span class="label-text"><?= e(__('Headline (English)')) ?></span><input name="hero_title_en" value="<?= e($t('theme.hero_title_en')) ?>" class="input w-full"></label>
                <label class="field"><span class="label-text"><?= e(__('Headline (Arabic)')) ?></span><input name="hero_title_ar" value="<?= e($t('theme.hero_title_ar')) ?>" dir="rtl" class="input w-full"></label>
                <label class="field"><span class="label-text"><?= e(__('Subtitle (English)')) ?></span><textarea name="hero_subtitle_en" rows="2" class="textarea w-full"><?= e($t('theme.hero_subtitle_en')) ?></textarea></label>
                <label class="field"><span class="label-text"><?= e(__('Subtitle (Arabic)')) ?></span><textarea name="hero_subtitle_ar" rows="2" dir="rtl" class="textarea w-full"><?= e($t('theme.hero_subtitle_ar')) ?></textarea></label>
            </div>
        </section>
        <?= $extra ?? '' ?>
    </div>

    <!-- Live preview -->
    <aside class="2xl:sticky 2xl:top-24 h-fit space-y-4">
        <div class="surface p-4">
            <div class="flex items-center justify-between mb-3">
                <span class="font-bold flex items-center gap-2"><?= icon('eye', 'size-4 text-primary') ?><?= e(__('Live preview')) ?></span>
                <div class="join">
                    <button type="button" class="join-item btn btn-xs" :class="pv==='light' && 'btn-primary'" @click="pv='light'"><?= icon('sun', 'size-3') ?></button>
                    <button type="button" class="join-item btn btn-xs" :class="pv==='dark' && 'btn-primary'" @click="pv='dark'"><?= icon('moon', 'size-3') ?></button>
                    <button type="button" class="join-item btn btn-xs" :class="lang==='ar' && 'btn-primary'" @click="lang = lang==='ar' ? 'en' : 'ar'">ع</button>
                </div>
            </div>
            <div :data-theme="pv" :dir="lang==='ar' ? 'rtl' : 'ltr'" :style="vars()" class="rounded-box overflow-hidden border border-base-content/10 bg-base-200 text-base-content">
                <div class="bg-base-100 h-10 px-3 flex items-center gap-2 border-b border-base-content/5" x-show="s.menu==='top'">
                    <span class="size-6 rounded-md grid place-items-center text-[10px] font-black" :style="grad()" x-text="initials()"></span>
                    <span class="text-xs font-bold" x-text="lang==='ar' && s.nameAr ? s.nameAr : s.name"></span>
                    <span class="ms-auto flex gap-2 text-[10px] opacity-60"><span>Home</span><span>Shop</span><span>Track</span></span>
                </div>
                <div class="flex">
                    <div class="w-20 bg-base-100 border-e border-base-content/5 p-2 space-y-1.5" x-show="s.menu==='sidebar'">
                        <span class="size-6 rounded-md grid place-items-center text-[10px] font-black" :style="grad()" x-text="initials()"></span>
                        <div class="h-2 rounded bg-primary/30"></div><div class="h-2 rounded bg-base-content/10"></div><div class="h-2 rounded bg-base-content/10"></div>
                    </div>
                    <div class="flex-1 p-3 space-y-3">
                        <div class="rounded-box p-4" :style="grad()">
                            <div class="text-sm font-black" x-text="lang==='ar' ? 'كل ما تحبه، يصلك بسرعة' : 'Everything you love, delivered fast'"></div>
                            <button type="button" class="btn btn-xs mt-2 bg-white/25 border-0 text-inherit" x-text="lang==='ar' ? 'تسوق الآن' : 'Shop now'"></button>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <template x-for="i in 2"><div class="bg-base-100 rounded-box p-2">
                                <div class="aspect-square rounded-field placeholder-img"></div>
                                <div class="text-[10px] font-semibold mt-1" x-text="lang==='ar' ? 'منتج رائع' : 'Great product'"></div>
                                <div class="flex items-center justify-between mt-1"><span class="text-xs font-black">$49</span><span class="btn btn-primary btn-xs btn-circle">+</span></div>
                            </div></template>
                        </div>
                        <div class="flex gap-1.5"><span class="badge badge-primary badge-xs">Primary</span><span class="badge badge-secondary badge-xs">Secondary</span><span class="badge badge-accent badge-xs">Accent</span></div>
                    </div>
                </div>
            </div>
        </div>
        <button class="btn btn-primary w-full rounded-full shadow-xl shadow-primary/25"><?= icon('save', 'size-4') ?> <?= e(__('Save theme')) ?></button>
    </aside>
</form>

<?php push_start(); ?>
<script>
function themeEditor(initial) {
    const radius = { sharp: ['0.25rem', '0.25rem'], soft: ['1rem', '0.6rem'], round: ['1.75rem', '2rem'] };
    const contrast = (hex) => {
        const n = parseInt((hex || '#000000').slice(1), 16), r = (n >> 16) / 255, g = (n >> 8 & 255) / 255, b = (n & 255) / 255;
        const l = (c) => c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
        return 0.2126 * l(r) + 0.7152 * l(g) + 0.0722 * l(b) > 0.45 ? '#111827' : '#ffffff';
    };
    return {
        s: initial, pv: initial.mode === 'dark' ? 'dark' : 'light', lang: initial.locale,
        init() { this.loadFont(this.s.fontEn); this.loadFont(this.s.fontAr); },
        loadFont(f) {
            const id = 'gf-' + f.replace(/\s+/g, '-');
            if (document.getElementById(id)) return;
            const l = document.createElement('link'); l.id = id; l.rel = 'stylesheet';
            l.href = 'https://fonts.googleapis.com/css2?family=' + f.replace(/\s+/g, '+') + ':wght@400;700;900&display=swap';
            document.head.appendChild(l);
        },
        initials() { return (this.s.name || '?').split(/\s+/).slice(0, 2).map(w => w[0] || '').join('').toUpperCase(); },
        grad() { return `background-image:linear-gradient(135deg, ${this.s.primary}, ${this.s.secondary}); color:${contrast(this.s.primary)}`; },
        vars() {
            const r = radius[this.s.radius] || radius.soft;
            const font = this.lang === 'ar' ? `'${this.s.fontAr}', '${this.s.fontEn}'` : `'${this.s.fontEn}', '${this.s.fontAr}'`;
            return `--color-primary:${this.s.primary};--color-primary-content:${contrast(this.s.primary)};--color-secondary:${this.s.secondary};--color-secondary-content:${contrast(this.s.secondary)};--color-accent:${this.s.accent};--color-accent-content:${contrast(this.s.accent)};--radius-box:${r[0]};--radius-field:${r[1]};font-family:${font}, sans-serif`;
        },
    };
}
</script>
<?php push_end('scripts'); ?>
