<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Connection registry: one platform (central) database plus the database of the
 * shop serving the current request. Every shop has its own database.
 */
final class DB
{
    private static ?Database $platform = null;
    private static ?Database $tenant = null;

    /** Connect to the MySQL server itself (optionally a named database) with the installer credentials. */
    public static function server(?string $database = null, ?array $cfg = null): Database
    {
        $cfg ??= (array) Config::get('db', []);
        return new Database(
            (string) ($cfg['host'] ?? '127.0.0.1'),
            (int) ($cfg['port'] ?? 3306),
            (string) ($cfg['user'] ?? 'root'),
            (string) ($cfg['pass'] ?? ''),
            $database
        );
    }

    public static function platform(): Database
    {
        return self::$platform ??= self::server((string) Config::get('db.platform_db'));
    }

    public static function tenant(): Database
    {
        if (!self::$tenant) {
            throw new \RuntimeException('No shop database is connected for this request.');
        }
        return self::$tenant;
    }

    public static function hasTenant(): bool
    {
        return self::$tenant !== null;
    }

    public static function setTenant(?Database $db): void
    {
        self::$tenant = $db;
    }

    /** Open a connection to a shop's own database. */
    public static function forTenant(array $tenant): Database
    {
        $cfg = (array) Config::get('db', []);
        if (!empty($tenant['db_user'])) {
            $cfg['user'] = $tenant['db_user'];
            $cfg['pass'] = Crypto::decrypt((string) $tenant['db_pass']);
        }
        return self::server((string) $tenant['db_name'], $cfg);
    }
}
