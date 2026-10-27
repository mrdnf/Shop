<?php
declare(strict_types=1);

namespace App\Core;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE || PHP_SAPI === 'cli') {
            return;
        }
        session_name('matjar_session');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => Request::isSecure(),
        ]);
        session_start();

        // Rotate flash data: what was flashed last request is readable now.
        $_SESSION['_flash_now'] = $_SESSION['_flash_next'] ?? [];
        $_SESSION['_flash_next'] = [];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, mixed $value): void
    {
        if ($key === 'old' && is_array($value)) {
            // Never keep secrets in the session for re-filling forms.
            $value = array_filter($value, static fn ($k) => !preg_match('/pass|token|secret|cfg/i', (string) $k), ARRAY_FILTER_USE_KEY);
        }
        $_SESSION['_flash_next'][$key] = $value;
        // Make it readable on this request too (useful when rendering directly).
        $_SESSION['_flash_now'][$key] = $value;
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        return $_SESSION['_flash_now'][$key] ?? $default;
    }

    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
}
