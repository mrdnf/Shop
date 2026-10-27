<?php
use App\Core\Session;

foreach (['success', 'error', 'warning', 'info'] as $type):
    $message = Session::getFlash($type);
    if ($message): ?>
        <div hidden data-flash="<?= e($message) ?>" data-type="<?= $type ?>"></div>
    <?php endif;
endforeach;
