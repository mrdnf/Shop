<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('APP_VERSION', '1.0.0');

spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $file = BASE_PATH . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

require BASE_PATH . '/app/helpers.php';

App\Core\Config::load();

date_default_timezone_set((string) App\Core\Config::get('timezone', 'UTC'));
error_reporting(E_ALL);
ini_set('display_errors', App\Core\Config::get('debug') ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . '/storage/logs/php-error.log');
