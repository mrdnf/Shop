<?php
declare(strict_types=1);

use App\Core\Auth;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\HttpException;
use App\Core\Lang;
use App\Core\Request;
use App\Core\Session;
use App\Core\Settings;
use App\Core\Tenant;

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function __(string $key, array $params = []): string
{
    return Lang::trans($key, $params);
}

function locale(): string
{
    return Lang::get();
}

function is_rtl(): bool
{
    return Lang::isRtl();
}

function data_get(mixed $target, string $path, mixed $default = null): mixed
{
    if ($path === '') {
        return $target;
    }
    foreach (explode('.', $path) as $segment) {
        if (is_array($target) && array_key_exists($segment, $target)) {
            $target = $target[$segment];
        } else {
            return $default;
        }
    }
    return $target;
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

/** URL inside the current shop (keeps the /s/{slug} prefix in multi-shop path mode). */
function url(string $path = '/', array $query = []): string
{
    $u = Request::baseUrl() . Tenant::basePath() . '/' . ltrim($path, '/');
    $u = $u === '/' ? '/' : rtrim($u, '/');
    if ($u === '') {
        $u = '/';
    }
    return $query ? $u . '?' . http_build_query($query) : $u;
}

/** URL outside any shop (platform / installer). */
function base_url(string $path = '/'): string
{
    $u = rtrim(Request::baseUrl() . '/' . ltrim($path, '/'), '/');
    return $u === '' ? '/' : $u;
}

function asset(string $path): string
{
    return Request::baseUrl() . '/assets/' . ltrim($path, '/') . '?v=' . APP_VERSION;
}

function upload_url(?string $path): ?string
{
    return $path ? Request::baseUrl() . '/' . ltrim($path, '/') : null;
}

function full_url(string $path): string
{
    return (Request::isSecure() ? 'https' : 'http') . '://' . Request::host() . $path;
}

function redirect(string $to, int $status = 302): never
{
    header('Location: ' . $to, true, $status);
    exit;
}

function back(): never
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $host = parse_url($ref, PHP_URL_HOST);
    redirect($ref && $host === preg_replace('/:\d+$/', '', Request::host()) ? $ref : url('/'));
}

function json_response(mixed $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function abort(int $status, string $message = ''): never
{
    throw new HttpException($status, $message);
}

function csrf_token(): string
{
    return Csrf::token();
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';
}

function method_field(string $method): string
{
    return '<input type="hidden" name="_method" value="' . e($method) . '">';
}

function old(string $key, mixed $default = ''): mixed
{
    $old = Session::getFlash('old', []);
    return $old[$key] ?? $default;
}

function error_for(string $key): ?string
{
    return Session::getFlash('errors', [])[$key] ?? null;
}

function flash(string $key, mixed $value): void
{
    Session::flash($key, $value);
}

function auth(): ?array
{
    return Auth::user();
}

function setting(string $key, mixed $default = null): mixed
{
    return Settings::get($key, $default);
}

function config(string $key, mixed $default = null): mixed
{
    return Config::get($key, $default);
}

/** Localised column: tr($row, 'name') -> name_ar when Arabic is active and filled, else name_en. */
function tr(?array $row, string $field): string
{
    if (!$row) {
        return '';
    }
    if (locale() === 'ar' && !empty($row[$field . '_ar'])) {
        return (string) $row[$field . '_ar'];
    }
    return (string) ($row[$field . '_en'] ?? $row[$field] ?? '');
}

function company_name(): string
{
    $ar = setting('company.name_ar');
    return (string) (locale() === 'ar' && $ar ? $ar : setting('company.name', Config::get('app_name', 'Matjar')));
}

function money(float|int|string|null $amount): string
{
    $symbol = (string) setting('store.currency_symbol', '$');
    $formatted = number_format((float) $amount, (int) setting('store.decimals', 2));
    return setting('store.symbol_position', 'before') === 'after' || is_rtl()
        ? $formatted . ' ' . $symbol
        : $symbol . (mb_strlen($symbol) > 1 ? ' ' : '') . $formatted;
}

function format_date(?string $date, bool $time = false): string
{
    if (!$date) {
        return '—';
    }
    return date($time ? 'M j, Y · H:i' : 'M j, Y', strtotime($date));
}

function slugify(string $text): string
{
    $text = strtolower(trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', $text), '-'));
    return $text !== '' ? $text : bin2hex(random_bytes(4));
}

function icon(string $name, string $class = 'size-5'): string
{
    return '<i data-lucide="' . e($name) . '" class="' . e($class) . '"></i>';
}

function current_path(): string
{
    $path = Request::path();
    $base = Tenant::basePath();
    return $base !== '' && str_starts_with($path, $base) ? (substr($path, strlen($base)) ?: '/') : $path;
}

function is_active(string $prefix, bool $exact = false): bool
{
    $path = current_path();
    return $exact ? $path === $prefix : ($path === $prefix || str_starts_with($path, rtrim($prefix, '/') . '/'));
}

function home_for_role(): string
{
    return match (Auth::role()) {
        'admin', 'staff' => url('/admin'),
        'driver' => url('/driver'),
        'platform' => base_url('/platform'),
        default => url('/account'),
    };
}

/** daisyUI badge colour for an order / shipment / payment status. */
function status_class(string $status): string
{
    return match ($status) {
        'pending', 'trial' => 'badge-warning',
        'confirmed', 'processing', 'ready' => 'badge-info',
        'shipped', 'picked_up', 'in_transit' => 'badge-primary',
        'out_for_delivery' => 'badge-accent',
        'delivered', 'paid', 'active', 'success' => 'badge-success',
        'cancelled', 'failed', 'returned', 'refunded', 'suspended', 'blocked', 'error' => 'badge-error',
        default => 'badge-ghost',
    };
}

function status_badge(string $status): string
{
    return '<span class="badge badge-soft ' . status_class($status) . ' badge-sm whitespace-nowrap">' . e(__('status.' . $status)) . '</span>';
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/u', trim($name)) ?: [];
    $out = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $out .= mb_strtoupper(mb_substr($part, 0, 1));
    }
    return $out ?: '?';
}

function plan_limit(string $key): ?int
{
    $plan = Tenant::plan();
    return $plan && $plan[$key] !== null ? (int) $plan[$key] : null;
}

/** JSON for inline <script> / Alpine attributes (safe against </script> and quotes). */
function js(mixed $value): string
{
    return (string) json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function push_start(): void
{
    ob_start();
}

function push_end(string $stack): void
{
    App\Core\View::push($stack, (string) ob_get_clean());
}
