<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Resolves which shop (tenant) a request belongs to.
 *
 * single mode: there is exactly one shop and it serves every URL.
 * multi mode:  custom domain  -> shop with that domain
 *              {slug}.{base_domain} -> shop by slug
 *              /s/{slug}/...  -> shop by slug (path based, works everywhere)
 *              anything else  -> SaaS platform (landing page, /platform admin)
 */
final class Tenant
{
    private static ?array $current = null;
    private static string $basePath = '';

    /** @return array{0: ?array, 1: string} tenant row (or null) and the path relative to the shop */
    public static function resolve(string $host, string $path): array
    {
        $platform = DB::platform();

        if (!Config::isMulti()) {
            $tenant = $platform->fetch('SELECT * FROM tenants ORDER BY id LIMIT 1');
            return [$tenant, $path];
        }

        $host = strtolower(preg_replace('/:\d+$/', '', $host) ?? $host);

        $tenant = $platform->fetch('SELECT * FROM tenants WHERE domain = ?', [$host]);
        if ($tenant) {
            return [$tenant, $path];
        }

        $baseDomain = strtolower((string) Config::get('base_domain', ''));
        if ($baseDomain !== '' && str_ends_with($host, '.' . $baseDomain)) {
            $slug = substr($host, 0, -strlen('.' . $baseDomain));
            if ($slug !== 'www' && ($tenant = self::findBySlug($slug))) {
                return [$tenant, $path];
            }
        }

        if (preg_match('#^/s/([a-z0-9-]+)(/.*)?$#', $path, $m)) {
            $tenant = self::findBySlug($m[1]);
            if ($tenant) {
                self::$basePath = '/s/' . $m[1];
                return [$tenant, $m[2] ?? '/'];
            }
        }

        return [null, $path];
    }

    public static function findBySlug(string $slug): ?array
    {
        return DB::platform()->fetch('SELECT * FROM tenants WHERE slug = ?', [$slug]);
    }

    /** Activate a shop for this request: connect its own database. */
    public static function set(array $tenant, ?string $basePath = null): void
    {
        self::$current = $tenant;
        if ($basePath !== null) {
            self::$basePath = $basePath;
        }
        DB::setTenant(DB::forTenant($tenant));
        Settings::reset();
    }

    public static function clear(): void
    {
        self::$current = null;
        self::$basePath = '';
        DB::setTenant(null);
        Settings::reset();
    }

    public static function current(): ?array
    {
        return self::$current;
    }

    public static function id(): ?int
    {
        return self::$current ? (int) self::$current['id'] : null;
    }

    public static function slug(): ?string
    {
        return self::$current['slug'] ?? null;
    }

    public static function basePath(): string
    {
        return self::$basePath;
    }

    /** Public URL of a shop's storefront (used by the platform panel). */
    public static function publicUrl(array $tenant): string
    {
        $scheme = Request::isSecure() ? 'https' : 'http';
        if (!empty($tenant['domain'])) {
            return "{$scheme}://{$tenant['domain']}";
        }
        $baseDomain = (string) Config::get('base_domain', '');
        if ($baseDomain !== '') {
            return "{$scheme}://{$tenant['slug']}.{$baseDomain}";
        }
        return Request::baseUrl() . '/s/' . $tenant['slug'];
    }

    public static function plan(): ?array
    {
        if (!self::$current || empty(self::$current['plan_id'])) {
            return null;
        }
        static $plans = [];
        $id = (int) self::$current['plan_id'];
        return $plans[$id] ??= DB::platform()->fetch('SELECT * FROM plans WHERE id = ?', [$id]);
    }
}
