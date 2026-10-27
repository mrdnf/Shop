<?php
declare(strict_types=1);

namespace App\Core;

final class Lang
{
    public const AVAILABLE = ['en' => 'English', 'ar' => 'العربية'];

    private static string $locale = 'en';
    private static array $lines = [];

    public static function set(string $locale): void
    {
        self::$locale = isset(self::AVAILABLE[$locale]) ? $locale : 'en';
    }

    public static function get(): string
    {
        return self::$locale;
    }

    public static function isRtl(): bool
    {
        return self::$locale === 'ar';
    }

    public static function trans(string $key, array $params = []): string
    {
        $line = self::lines(self::$locale)[$key] ?? self::lines('en')[$key] ?? $key;
        foreach ($params as $name => $value) {
            $line = str_replace(':' . $name, (string) $value, $line);
        }
        return $line;
    }

    private static function lines(string $locale): array
    {
        if (!isset(self::$lines[$locale])) {
            $file = BASE_PATH . "/lang/{$locale}.php";
            self::$lines[$locale] = is_file($file) ? (array) require $file : [];
        }
        return self::$lines[$locale];
    }

    /**
     * Pick the locale: explicit switch (cookie) > logged-in user's preference > company default.
     */
    public static function detect(string $default): void
    {
        $cookie = $_COOKIE['lang'] ?? null;
        if (is_string($cookie) && isset(self::AVAILABLE[$cookie])) {
            self::set($cookie);
            return;
        }
        self::set($default);
    }
}
