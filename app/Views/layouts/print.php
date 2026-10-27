<?php
use App\Core\View;

ob_start(); ?>
<div class="max-w-3xl mx-auto p-6 md:p-10">
    <div class="no-print flex justify-end gap-2 mb-6">
        <button onclick="history.back()" class="btn btn-ghost btn-sm"><?= icon('arrow-left', 'size-4 flip-rtl') ?> <?= e(__('Back')) ?></button>
        <button onclick="window.print()" class="btn btn-primary btn-sm"><?= icon('printer', 'size-4') ?> <?= e(__('Print')) ?></button>
    </div>
    <div class="bg-base-100 rounded-box shadow-xl p-8 md:p-12 print:shadow-none print:p-0"><?= $content ?></div>
</div>
<?php
$content = ob_get_clean();
echo View::partial('layouts/base', ['content' => $content, 'title' => $title ?? '', 'bodyClass' => 'bg-base-200']);
