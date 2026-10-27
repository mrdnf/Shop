<?php
/**
 * Generic application shell used by the shop admin, driver app and the SaaS
 * platform admin. Renders as a sidebar or a top menu.
 *
 * @var array  $menu       [['title' => ?string, 'items' => [[label, url, icon, active, badge?]]]]
 * @var string $layoutMode 'sidebar' | 'top'
 * @var string $panelName
 * @var string $homeUrl
 * @var string $logoutUrl
 * @var array  $userLinks
 * @var string $langUrl
 * @var ?string $storeUrl
 */
use App\Core\View;

$user = auth();
$langUrl = $langUrl ?? url('/lang/' . (locale() === 'ar' ? 'en' : 'ar'));
$storeUrl = $storeUrl ?? null;
$userLinks = $userLinks ?? [];
$sidebarFooter = $sidebarFooter ?? '';
ob_start();

$renderItem = static function (array $item, string $extra = '', bool $compact = false) {
    [$label, $href, $ic, $active] = $item;
    $badge = $item[4] ?? null; ?>
    <li><a href="<?= e($href) ?>" class="<?= $active ? 'menu-active' : '' ?> <?= $extra ?>" title="<?= e($label) ?>">
        <?= icon($ic, 'size-[18px]') ?><span class="flex-1 <?= $compact && !$active ? 'hidden 2xl:inline' : '' ?>"><?= e($label) ?></span>
        <?php if ($badge): ?><span class="badge badge-sm badge-secondary font-bold"><?= e($badge) ?></span><?php endif; ?>
    </a></li>
<?php };

$topbarRight = static function () use ($user, $userLinks, $logoutUrl, $langUrl, $storeUrl) { ?>
    <?php if (!empty($storeUrl)): ?>
        <a href="<?= e($storeUrl) ?>" target="_blank" class="btn btn-ghost btn-sm rounded-full hidden sm:inline-flex"><?= icon('external-link', 'size-4') ?> <?= e(__('View store')) ?></a>
    <?php endif; ?>
    <?= View::partial('partials/controls', ['langUrl' => $langUrl]) ?>
    <?= View::partial('partials/user_menu', ['links' => $userLinks ?? [], 'logoutUrl' => $logoutUrl]) ?>
<?php };
?>

<?php if ($layoutMode === 'sidebar'): ?>
<div class="drawer lg:drawer-open">
    <input id="panel-drawer" type="checkbox" class="drawer-toggle">
    <div class="drawer-content flex flex-col min-h-screen bg-brand-soft">
        <header class="sticky top-0 z-30 glass border-b border-base-content/5 no-print">
            <div class="h-16 px-4 md:px-6 flex items-center gap-3">
                <label for="panel-drawer" class="btn btn-ghost btn-sm btn-circle lg:hidden"><?= icon('panel-left') ?></label>
                <div class="min-w-0">
                    <div class="text-xs opacity-60 font-medium"><?= e($panelName) ?></div>
                    <h1 class="font-bold text-lg leading-tight truncate"><?= e($title ?? '') ?></h1>
                </div>
                <div class="ms-auto flex items-center gap-1"><?php $topbarRight(); ?></div>
            </div>
        </header>
        <main class="flex-1 p-4 md:p-8 animate-fade-up"><?= $content ?></main>
    </div>
    <div class="drawer-side z-40 no-print">
        <label for="panel-drawer" class="drawer-overlay"></label>
        <aside class="w-72 min-h-full bg-base-100 border-e border-base-content/5 flex flex-col">
            <div class="h-16 px-5 flex items-center gap-2 border-b border-base-content/5">
                <?= View::partial('partials/brand', ['href' => $homeUrl]) ?>
            </div>
            <div class="flex-1 overflow-y-auto">
                <?php foreach ($menu as $section): ?>
                    <ul class="menu w-full px-3 py-2 gap-0.5">
                        <?php if (!empty($section['title'])): ?><li class="menu-title text-xs uppercase tracking-wider"><?= e($section['title']) ?></li><?php endif; ?>
                        <?php foreach ($section['items'] as $item) { $renderItem($item, 'py-2.5 rounded-xl'); } ?>
                    </ul>
                <?php endforeach; ?>
            </div>
            <?php if (!empty($sidebarFooter)): ?><div class="p-4"><?= $sidebarFooter ?></div><?php endif; ?>
        </aside>
    </div>
</div>
<?php else: ?>
<div class="min-h-screen flex flex-col bg-brand-soft">
    <header class="sticky top-0 z-30 glass border-b border-base-content/5 no-print">
        <div class="max-w-7xl mx-auto h-16 px-4 flex items-center gap-2">
            <div class="dropdown lg:hidden">
                <div tabindex="0" role="button" class="btn btn-ghost btn-sm btn-circle"><?= icon('menu') ?></div>
                <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-50 mt-3 w-64 p-2 shadow-2xl">
                    <?php foreach ($menu as $section): ?>
                        <?php if (!empty($section['title'])): ?><li class="menu-title"><?= e($section['title']) ?></li><?php endif; ?>
                        <?php foreach ($section['items'] as $item) { $renderItem($item); } ?>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?= View::partial('partials/brand', ['href' => $homeUrl, 'showName' => true]) ?>
            <ul class="menu menu-horizontal hidden lg:flex gap-1 ms-4 p-0">
                <?php foreach ($menu as $i => $section): ?>
                    <?php if ($i === 0 || empty($section['title'])): ?>
                        <?php foreach ($section['items'] as $item) { $renderItem($item, 'rounded-full', count($menu) > 2); } ?>
                    <?php else:
                        $anyActive = (bool) array_filter($section['items'], static fn ($it) => $it[3]); ?>
                        <li>
                            <details>
                                <summary class="rounded-full <?= $anyActive ? 'text-primary font-semibold' : '' ?>"><?= e($section['title']) ?></summary>
                                <ul class="bg-base-100 rounded-box w-56 z-50 shadow-2xl p-2">
                                    <?php foreach ($section['items'] as $item) { $renderItem($item); } ?>
                                </ul>
                            </details>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
            <div class="ms-auto flex items-center gap-1"><?php $topbarRight(); ?></div>
        </div>
    </header>
    <main class="flex-1 w-full max-w-7xl mx-auto p-4 md:p-8 animate-fade-up">
        <div class="mb-6">
            <div class="text-xs opacity-60 font-medium"><?= e($panelName) ?></div>
            <h1 class="text-2xl font-extrabold tracking-tight"><?= e($title ?? '') ?></h1>
        </div>
        <?= $content ?>
    </main>
</div>
<?php endif; ?>
<?php
$content = ob_get_clean();
echo View::partial('layouts/base', compact('content') + ['title' => $title ?? null, 'head' => $head ?? '', 'scripts' => $scripts ?? '']);
