<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Key/value settings. The shop store lives in each shop DB (`settings`);
 * the platform store lives in the central DB (`platform_settings`).
 */
final class Settings
{
    private static array $cache = [];

    public static function reset(): void
    {
        self::$cache = [];
    }

    /** The store for the current context: the shop if one is active, otherwise the platform. */
    private static function source(): array
    {
        return DB::hasTenant() ? [DB::tenant(), 'settings', 'shop'] : [DB::platform(), 'platform_settings', 'platform'];
    }

    public static function all(): array
    {
        [$db, $table, $key] = self::source();
        if (!isset(self::$cache[$key])) {
            self::$cache[$key] = [];
            foreach ($db->fetchAll('SELECT k, v FROM ' . Database::ident($table)) as $row) {
                self::$cache[$key][$row['k']] = $row['v'];
            }
        }
        return self::$cache[$key];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::all();
        return array_key_exists($key, $all) && $all[$key] !== '' ? $all[$key] : $default;
    }

    public static function set(string $key, mixed $value): void
    {
        self::setMany([$key => $value]);
    }

    public static function setMany(array $values, ?Database $db = null, string $table = 'settings'): void
    {
        if ($db === null) {
            [$db, $table] = self::source();
        }
        foreach ($values as $k => $v) {
            $db->query(
                'INSERT INTO ' . Database::ident($table) . ' (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)',
                [$k, is_array($v) ? json_encode($v) : (string) $v]
            );
        }
        self::reset();
    }
}
