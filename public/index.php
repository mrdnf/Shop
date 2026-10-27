<?php
declare(strict_types=1);

// Let PHP's built-in server serve static files directly.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . rawurldecode((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    if (is_file($file)) {
        return false;
    }
}

require dirname(__DIR__) . '/app/bootstrap.php';

(new App\Core\App())->run();
