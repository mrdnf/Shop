<?php
declare(strict_types=1);

use App\Controllers\Admin;
use App\Controllers\Api\ApiController;
use App\Controllers\Driver\DriverController;
use App\Controllers\Shop;
use App\Core\Router;

/** Routes of a single shop (storefront, customer area, driver app, shop admin, API). */
return static function (Router $r): void {
    // Storefront
    $r->get('/', [Shop\HomeController::class, 'index']);
    $r->get('/lang/{code}', [Shop\HomeController::class, 'lang']);
    $r->get('/products', [Shop\CatalogController::class, 'index']);
    $r->get('/category/{slug}', [Shop\CatalogController::class, 'category']);
    $r->get('/product/{slug}', [Shop\CatalogController::class, 'show']);

    $r->get('/cart', [Shop\CartController::class, 'show']);
    $r->post('/cart/add', [Shop\CartController::class, 'add']);
    $r->post('/cart/update', [Shop\CartController::class, 'update']);
    $r->post('/cart/remove', [Shop\CartController::class, 'remove']);

    $r->get('/checkout', [Shop\CheckoutController::class, 'show']);
    $r->post('/checkout', [Shop\CheckoutController::class, 'place']);
    $r->get('/checkout/zone', [Shop\CheckoutController::class, 'zone']);
    $r->get('/order/{number}/thanks', [Shop\CheckoutController::class, 'thanks']);

    $r->get('/track', [Shop\TrackingController::class, 'form']);
    $r->get('/track/{number}', [Shop\TrackingController::class, 'show']);
    $r->get('/track/{number}/json', [Shop\TrackingController::class, 'json']);

    // Authentication (customers, staff, drivers, shop admins share one login)
    $r->get('/login', [Shop\AuthController::class, 'login'], ['guest']);
    $r->post('/login', [Shop\AuthController::class, 'authenticate'], ['guest']);
    $r->get('/register', [Shop\AuthController::class, 'register'], ['guest']);
    $r->post('/register', [Shop\AuthController::class, 'store'], ['guest']);
    $r->post('/logout', [Shop\AuthController::class, 'logout']);

    // Customer area
    $r->group(['prefix' => '/account', 'middleware' => ['auth']], static function (Router $r) {
        $r->get('/', [Shop\AccountController::class, 'index']);
        $r->get('/orders', [Shop\AccountController::class, 'orders']);
        $r->get('/orders/{number}', [Shop\AccountController::class, 'order']);
        $r->get('/addresses', [Shop\AccountController::class, 'addresses']);
        $r->post('/addresses', [Shop\AccountController::class, 'saveAddress']);
        $r->post('/addresses/{id:\d+}/delete', [Shop\AccountController::class, 'deleteAddress']);
        $r->get('/profile', [Shop\AccountController::class, 'profile']);
        $r->post('/profile', [Shop\AccountController::class, 'saveProfile']);
        $r->post('/password', [Shop\AccountController::class, 'savePassword']);
    });

    // Driver app
    $r->group(['prefix' => '/driver', 'middleware' => ['driver']], static function (Router $r) {
        $r->get('/', [DriverController::class, 'index']);
        $r->get('/shipments/{id:\d+}', [DriverController::class, 'show']);
        $r->post('/shipments/{id:\d+}/event', [DriverController::class, 'event']);
        $r->post('/shipments/{id:\d+}/location', [DriverController::class, 'location']);
    });

    // Shop admin
    $r->group(['prefix' => '/admin', 'middleware' => ['admin']], static function (Router $r) {
        $r->get('/', [Admin\DashboardController::class, 'index']);

        $r->get('/products', [Admin\ProductController::class, 'index']);
        $r->get('/products/create', [Admin\ProductController::class, 'create']);
        $r->post('/products', [Admin\ProductController::class, 'store']);
        $r->get('/products/{id:\d+}/edit', [Admin\ProductController::class, 'edit']);
        $r->post('/products/{id:\d+}', [Admin\ProductController::class, 'update']);
        $r->post('/products/{id:\d+}/delete', [Admin\ProductController::class, 'destroy']);
        $r->post('/products/{id:\d+}/stock', [Admin\ProductController::class, 'stock']);

        $r->get('/categories', [Admin\CategoryController::class, 'index']);
        $r->post('/categories', [Admin\CategoryController::class, 'store']);
        $r->post('/categories/{id:\d+}', [Admin\CategoryController::class, 'update']);
        $r->post('/categories/{id:\d+}/delete', [Admin\CategoryController::class, 'destroy']);

        $r->get('/orders', [Admin\OrderController::class, 'index']);
        $r->get('/orders/{id:\d+}', [Admin\OrderController::class, 'show']);
        $r->get('/orders/{id:\d+}/invoice', [Admin\OrderController::class, 'invoice']);
        $r->post('/orders/{id:\d+}/status', [Admin\OrderController::class, 'status']);
        $r->post('/orders/{id:\d+}/payment', [Admin\OrderController::class, 'payment']);
        $r->post('/orders/{id:\d+}/shipments', [Admin\OrderController::class, 'ship']);

        $r->get('/shipments', [Admin\ShipmentController::class, 'index']);
        $r->get('/shipments/{id:\d+}', [Admin\ShipmentController::class, 'show']);
        $r->post('/shipments/{id:\d+}/event', [Admin\ShipmentController::class, 'event']);
        $r->post('/shipments/{id:\d+}/driver', [Admin\ShipmentController::class, 'driver']);
        $r->post('/shipments/{id:\d+}/sync', [Admin\ShipmentController::class, 'sync']);

        $r->get('/customers', [Admin\CustomerController::class, 'index']);
        $r->get('/customers/{id:\d+}', [Admin\CustomerController::class, 'show']);
        $r->post('/customers/{id:\d+}/toggle', [Admin\CustomerController::class, 'toggle']);

        // Owner-only configuration
        $r->group(['middleware' => ['owner']], static function (Router $r) {
            $r->get('/inventory', [Admin\InventoryController::class, 'index']);
            $r->post('/inventory', [Admin\InventoryController::class, 'save']);
            $r->post('/inventory/test', [Admin\InventoryController::class, 'test']);
            $r->post('/inventory/sync', [Admin\InventoryController::class, 'sync']);

            $r->get('/delivery', [Admin\DeliveryController::class, 'index']);
            $r->post('/delivery/zones', [Admin\DeliveryController::class, 'saveZone']);
            $r->post('/delivery/zones/{id:\d+}/delete', [Admin\DeliveryController::class, 'deleteZone']);
            $r->post('/delivery/couriers', [Admin\DeliveryController::class, 'saveCourier']);
            $r->post('/delivery/couriers/{id:\d+}/delete', [Admin\DeliveryController::class, 'deleteCourier']);

            $r->get('/theme', [Admin\ThemeController::class, 'edit']);
            $r->post('/theme', [Admin\ThemeController::class, 'update']);

            $r->get('/settings', [Admin\SettingsController::class, 'edit']);
            $r->post('/settings', [Admin\SettingsController::class, 'update']);

            $r->get('/staff', [Admin\StaffController::class, 'index']);
            $r->post('/staff', [Admin\StaffController::class, 'store']);
            $r->post('/staff/{id:\d+}', [Admin\StaffController::class, 'update']);
            $r->post('/staff/{id:\d+}/delete', [Admin\StaffController::class, 'destroy']);

            $r->get('/api-keys', [Admin\ApiKeyController::class, 'index']);
            $r->post('/api-keys', [Admin\ApiKeyController::class, 'store']);
            $r->post('/api-keys/{id:\d+}/revoke', [Admin\ApiKeyController::class, 'revoke']);
        });
    });

    // Integration API (per-shop API keys)
    $r->group(['prefix' => '/api/v1', 'middleware' => ['nocsrf', 'api']], static function (Router $r) {
        $r->get('/products', [ApiController::class, 'products']);
        $r->post('/inventory', [ApiController::class, 'inventory']);
        $r->get('/orders', [ApiController::class, 'orders']);
        $r->get('/orders/{number}', [ApiController::class, 'order']);
        $r->post('/shipments/{tracking}/events', [ApiController::class, 'shipmentEvent']);
    });
};
