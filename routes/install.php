<?php
declare(strict_types=1);

use App\Controllers\Install\InstallController;
use App\Core\Router;

return static function (Router $r): void {
    $r->get('/install', [InstallController::class, 'index']);
    $r->get('/install/database', [InstallController::class, 'database']);
    $r->post('/install/database', [InstallController::class, 'saveDatabase']);
    $r->get('/install/mode', [InstallController::class, 'mode']);
    $r->post('/install/mode', [InstallController::class, 'saveMode']);
    $r->get('/install/setup', [InstallController::class, 'setup']);
    $r->post('/install/setup', [InstallController::class, 'finish']);
    $r->get('/install/lang/{code}', [InstallController::class, 'lang']);
};
