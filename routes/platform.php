<?php
declare(strict_types=1);

use App\Controllers\Platform;
use App\Core\Router;

/** SaaS owner routes (multi-shop mode only): marketing site, shop sign-up, platform admin. */
return static function (Router $r): void {
    $r->get('/', [Platform\HomeController::class, 'index']);
    $r->get('/lang/{code}', [Platform\HomeController::class, 'lang']);
    $r->get('/start', [Platform\HomeController::class, 'signup']);
    $r->post('/start', [Platform\HomeController::class, 'createShop']);
    $r->get('/install', static fn () => redirect(base_url('/')));

    $r->get('/platform/login', [Platform\AuthController::class, 'login']);
    $r->post('/platform/login', [Platform\AuthController::class, 'authenticate']);
    $r->post('/platform/logout', [Platform\AuthController::class, 'logout']);

    $r->group(['prefix' => '/platform', 'middleware' => ['platform.auth']], static function (Router $r) {
        $r->get('/', [Platform\DashboardController::class, 'index']);

        $r->get('/shops', [Platform\TenantController::class, 'index']);
        $r->get('/shops/create', [Platform\TenantController::class, 'create']);
        $r->post('/shops', [Platform\TenantController::class, 'store']);
        $r->get('/shops/{id:\d+}/edit', [Platform\TenantController::class, 'edit']);
        $r->post('/shops/{id:\d+}', [Platform\TenantController::class, 'update']);
        $r->post('/shops/{id:\d+}/delete', [Platform\TenantController::class, 'destroy']);

        $r->get('/plans', [Platform\PlanController::class, 'index']);
        $r->post('/plans', [Platform\PlanController::class, 'save']);
        $r->post('/plans/{id:\d+}/delete', [Platform\PlanController::class, 'destroy']);

        $r->get('/settings', [Platform\SettingsController::class, 'edit']);
        $r->post('/settings', [Platform\SettingsController::class, 'update']);
        $r->post('/settings/password', [Platform\SettingsController::class, 'password']);
    });
};
