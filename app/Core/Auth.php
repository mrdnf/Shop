<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Authentication is scoped: each shop has its own users (in its own DB) and the
 * SaaS platform owner has separate platform admins. Logins never leak across scopes.
 */
final class Auth
{
    private static array $cache = [];

    public static function scope(): string
    {
        return Tenant::current() ? 'shop_' . Tenant::id() : 'platform';
    }

    public static function login(array $user): void
    {
        Session::regenerate();
        $_SESSION['auth'][self::scope()] = (int) $user['id'];
        unset(self::$cache[self::scope()]);
        $table = Tenant::current() ? 'users' : 'platform_admins';
        self::db()->update($table, ['last_login_at' => now()], 'id = ?', [$user['id']]);
    }

    public static function logout(): void
    {
        unset($_SESSION['auth'][self::scope()], self::$cache[self::scope()]);
        Session::regenerate();
    }

    public static function id(): ?int
    {
        return isset($_SESSION['auth'][self::scope()]) ? (int) $_SESSION['auth'][self::scope()] : null;
    }

    public static function user(): ?array
    {
        $scope = self::scope();
        if (array_key_exists($scope, self::$cache)) {
            return self::$cache[$scope];
        }
        $id = self::id();
        $user = null;
        if ($id) {
            $user = Tenant::current()
                ? self::db()->fetch("SELECT * FROM users WHERE id = ? AND status = 'active'", [$id])
                : self::db()->fetch('SELECT *, \'platform\' AS role FROM platform_admins WHERE id = ?', [$id]);
        }
        return self::$cache[$scope] = $user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function role(): ?string
    {
        return self::user()['role'] ?? null;
    }

    public static function is(string ...$roles): bool
    {
        return in_array(self::role(), $roles, true);
    }

    /** Verify credentials against the current scope's user table. */
    public static function attempt(string $email, string $password): ?array
    {
        $user = Tenant::current()
            ? self::db()->fetch('SELECT * FROM users WHERE email = ?', [strtolower($email)])
            : self::db()->fetch('SELECT * FROM platform_admins WHERE email = ?', [strtolower($email)]);

        if (!$user || !password_verify($password, (string) $user['password'])) {
            return null;
        }
        if (($user['status'] ?? 'active') !== 'active') {
            return null;
        }
        return $user;
    }

    public static function refresh(): void
    {
        unset(self::$cache[self::scope()]);
    }

    private static function db(): Database
    {
        return Tenant::current() ? DB::tenant() : DB::platform();
    }
}
