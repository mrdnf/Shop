<?php use App\Core\View; ?>
<div class="surface overflow-hidden">
    <?php if ($page['items']): ?>
        <?= View::partial('shop/account/_orders_table', ['orders' => $page['items']]) ?>
    <?php else: ?>
        <?= View::partial('partials/empty', ['icon' => 'package', 'title' => __('No orders yet'), 'action' => '<a class="btn btn-primary btn-sm rounded-full" href="' . e(url('/products')) . '">' . e(__('Start shopping')) . '</a>']) ?>
    <?php endif; ?>
</div>
<?= View::partial('partials/pagination', ['page' => $page]) ?>
