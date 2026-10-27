<?php
declare(strict_types=1);

namespace App\Core;

final class Middleware
{
    public static function handle(string $name): void
    {
        match ($name) {
            'auth' => self::requireLogin(),
            'guest' => Auth::check() ? redirect(home_for_role()) : null,
            'admin' => self::requireRole('admin', 'staff'),
            'owner' => self::requireRole('admin'),
            'driver' => self::requireRole('driver', 'admin', 'staff'),
            'platform.auth' => self::requirePlatform(),
            'api' => self::requireApiKey(),
            'nocsrf' => null,
            default => throw new \LogicException("Unknown middleware {$name}"),
        };
    }

    private static function requireLogin(): void
    {
        if (!Auth::check()) {
            Session::flash('warning', __('Please sign in to continue.'));
            redirect(url('/login') . '?next=' . rawurlencode(current_path()));
        }
    }

    private static function requireRole(string ...$roles): void
    {
        self::requireLogin();
        if (!Auth::is(...$roles)) {
            throw new HttpException(403, __('You do not have access to this area.'));
        }
    }

    private static function requirePlatform(): void
    {
        if (!Auth::check()) {
            redirect(base_url('/platform/login'));
        }
    }

    /** API keys are per shop: `Authorization: Bearer mk_...` or `X-API-Key: mk_...`. */
    private static function requireApiKey(): void
    {
        $header = Request::header('Authorization') ?? '';
        $key = str_starts_with($header, 'Bearer ') ? substr($header, 7) : (Request::header('X-API-Key') ?? '');
        $key = trim($key);
        if ($key === '' || !DB::hasTenant()) {
            throw new HttpException(401, 'Missing API key');
        }
        $row = DB::tenant()->fetch(
            'SELECT * FROM api_keys WHERE key_hash = ? AND revoked_at IS NULL',
            [hash('sha256', $key)]
        );
        if (!$row) {
            throw new HttpException(401, 'Invalid API key');
        }
        DB::tenant()->update('api_keys', ['last_used_at' => now()], 'id = ?', [$row['id']]);
        $GLOBALS['api_key'] = $row;
    }
}
