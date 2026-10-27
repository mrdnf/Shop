<?php
/** @var array $page from Database::paginate */
if (($page['pages'] ?? 1) <= 1) {
    return;
}
$link = static function (int $n): string {
    $q = $_GET;
    $q['page'] = $n;
    return '?' . http_build_query($q);
};
$current = $page['page'];
$from = max(1, $current - 2);
$to = min($page['pages'], $current + 2);
?>
<div class="flex items-center justify-between gap-3 mt-6">
    <span class="text-sm opacity-60"><?= e(__(':total results', ['total' => $page['total']])) ?></span>
    <div class="join">
        <a class="join-item btn btn-sm <?= $current <= 1 ? 'btn-disabled' : '' ?>" href="<?= e($link(max(1, $current - 1))) ?>"><?= icon('chevron-left', 'size-4 flip-rtl') ?></a>
        <?php for ($i = $from; $i <= $to; $i++): ?>
            <a class="join-item btn btn-sm <?= $i === $current ? 'btn-primary' : '' ?>" href="<?= e($link($i)) ?>"><?= $i ?></a>
        <?php endfor; ?>
        <a class="join-item btn btn-sm <?= $current >= $page['pages'] ? 'btn-disabled' : '' ?>" href="<?= e($link(min($page['pages'], $current + 1))) ?>"><?= icon('chevron-right', 'size-4 flip-rtl') ?></a>
    </div>
</div>
