<?php
declare(strict_types=1);

namespace App\Core;

final class Request
{
    private static ?array $json = null;

    public static function method(): string
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($method === 'POST' && isset($_POST['_method'])) {
            $override = strtoupper((string) $_POST['_method']);
            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                return $override;
            }
        }
        return $method;
    }

    /** Sub-folder the app is served from ('' when at the web root). */
    public static function baseUrl(): string
    {
        if (PHP_SAPI === 'cli') {
            return '';
        }
        $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        return rtrim($dir === '/' || $dir === '.' ? '' : $dir, '/');
    }

    public static function path(): string
    {
        $path = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
        $base = self::baseUrl();
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }
        $path = '/' . trim($path, '/');
        return $path;
    }

    public static function host(): string
    {
        return (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    }

    public static function isSecure(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }

    public static function input(string $key, mixed $default = null): mixed
    {
        $value = $_POST[$key] ?? self::json()[$key] ?? $_GET[$key] ?? $default;
        return is_string($value) ? trim($value) : $value;
    }

    public static function all(): array
    {
        return array_merge($_GET, self::json(), $_POST);
    }

    public static function only(string ...$keys): array
    {
        $out = [];
        foreach ($keys as $key) {
            $out[$key] = self::input($key);
        }
        return $out;
    }

    public static function query(string $key, mixed $default = null): mixed
    {
        $value = $_GET[$key] ?? $default;
        return is_string($value) ? trim($value) : $value;
    }

    public static function file(string $key): ?array
    {
        $file = $_FILES[$key] ?? null;
        return $file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE ? $file : null;
    }

    public static function json(): array
    {
        if (self::$json === null) {
            self::$json = [];
            if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
                $data = json_decode((string) file_get_contents('php://input'), true);
                self::$json = is_array($data) ? $data : [];
            }
        }
        return self::$json;
    }

    public static function wantsJson(): bool
    {
        return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
            || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }

    public static function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$key] ?? null;
    }

    public static function ip(): string
    {
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }
}
