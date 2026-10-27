<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Settings;

/**
 * Per-company theming: logo, name, brand colours, EN/AR fonts, light/dark,
 * top-menu vs sidebar layout and corner radius. Works for a shop (shop DB
 * settings) and for the SaaS platform itself (platform settings).
 */
final class Theme
{
    public const FONTS_EN = ['Plus Jakarta Sans', 'Inter', 'Poppins', 'Outfit', 'DM Sans', 'Montserrat', 'Nunito', 'Roboto', 'Manrope'];
    public const FONTS_AR = ['Cairo', 'Tajawal', 'Almarai', 'IBM Plex Sans Arabic', 'Noto Kufi Arabic', 'Readex Pro', 'Changa', 'El Messiri', 'Rubik'];

    public const RADIUS = [
        'sharp' => ['box' => '0.25rem', 'field' => '0.25rem', 'selector' => '0.25rem'],
        'soft' => ['box' => '1rem', 'field' => '0.6rem', 'selector' => '0.5rem'],
        'round' => ['box' => '1.75rem', 'field' => '2rem', 'selector' => '1rem'],
    ];

    public const PRESETS = [
        'Violet Dream' => ['#7c3aed', '#db2777', '#06b6d4'],
        'Ocean' => ['#0369a1', '#0d9488', '#f59e0b'],
        'Emerald' => ['#059669', '#0891b2', '#f97316'],
        'Sunset' => ['#ea580c', '#e11d48', '#7c3aed'],
        'Midnight' => ['#1e40af', '#7c3aed', '#22d3ee'],
        'Desert Gold' => ['#b45309', '#0f766e', '#be123c'],
    ];

    public static function defaults(): array
    {
        return [
            'theme.primary' => '#7c3aed',
            'theme.secondary' => '#db2777',
            'theme.accent' => '#06b6d4',
            'theme.font_en' => 'Plus Jakarta Sans',
            'theme.font_ar' => 'Cairo',
            'theme.default_locale' => 'en',
            'theme.default_mode' => 'light',
            'theme.menu_layout' => 'top',
            'theme.admin_menu_layout' => 'sidebar',
            'theme.radius' => 'soft',
            'theme.hero_title_en' => 'Everything you love, delivered fast',
            'theme.hero_title_ar' => 'كل ما تحبه، يصلك بسرعة',
            'theme.hero_subtitle_en' => 'Live stock, secure checkout and real-time delivery tracking.',
            'theme.hero_subtitle_ar' => 'مخزون مباشر، دفع آمن وتتبع لحظي للتوصيل.',
        ];
    }

    public static function get(string $key): string
    {
        return (string) Settings::get('theme.' . $key, self::defaults()['theme.' . $key] ?? '');
    }

    /** 'light' | 'dark' | 'system' — the visitor's own choice (cookie) wins over the company default. */
    public static function mode(): string
    {
        $cookie = $_COOKIE['mode'] ?? '';
        return in_array($cookie, ['light', 'dark'], true) ? $cookie : self::get('default_mode');
    }

    /** Value for <html data-theme>; 'system' is resolved client-side before paint. */
    public static function dataTheme(): string
    {
        $mode = self::mode();
        return $mode === 'dark' ? 'dark' : 'light';
    }

    public static function menuLayout(string $area = 'shop'): string
    {
        $layout = $area === 'admin' ? self::get('admin_menu_layout') : self::get('menu_layout');
        return $layout === 'sidebar' ? 'sidebar' : 'top';
    }

    public static function fontsHref(): string
    {
        $families = [];
        foreach (array_unique([self::get('font_en'), self::get('font_ar')]) as $font) {
            if ($font !== '') {
                $families[] = 'family=' . str_replace(' ', '+', $font) . ':wght@400;500;600;700;800';
            }
        }
        return 'https://fonts.googleapis.com/css2?' . implode('&', $families) . '&display=swap';
    }

    public static function css(): string
    {
        $primary = self::color('primary', '#7c3aed');
        $secondary = self::color('secondary', '#db2777');
        $accent = self::color('accent', '#06b6d4');
        $radius = self::RADIUS[self::get('radius')] ?? self::RADIUS['soft'];
        $en = self::get('font_en');
        $ar = self::get('font_ar');

        $vars = [
            '--color-primary' => $primary,
            '--color-primary-content' => self::contrast($primary),
            '--color-secondary' => $secondary,
            '--color-secondary-content' => self::contrast($secondary),
            '--color-accent' => $accent,
            '--color-accent-content' => self::contrast($accent),
            '--radius-box' => $radius['box'],
            '--radius-field' => $radius['field'],
            '--radius-selector' => $radius['selector'],
        ];
        $decl = '';
        foreach ($vars as $k => $v) {
            $decl .= "{$k}:{$v};";
        }
        $fontEn = "\"{$en}\",\"{$ar}\",ui-sans-serif,system-ui,sans-serif";
        $fontAr = "\"{$ar}\",\"{$en}\",ui-sans-serif,system-ui,sans-serif";

        return "[data-theme=light],[data-theme=dark]{{$decl}}"
            . ":root{--font-sans:{$fontEn};--brand-1:{$primary};--brand-2:{$secondary};--brand-3:{$accent}}"
            . "html[lang=ar]{--font-sans:{$fontAr}}";
    }

    private static function color(string $key, string $fallback): string
    {
        $value = self::get($key);
        return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? $value : $fallback;
    }

    /** Readable text colour on top of a hex background. */
    public static function contrast(string $hex): string
    {
        $hex = ltrim($hex, '#');
        [$r, $g, $b] = array_map(static fn ($c) => hexdec($c) / 255, str_split($hex, 2));
        $lin = static fn ($c) => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        $luminance = 0.2126 * $lin($r) + 0.7152 * $lin($g) + 0.0722 * $lin($b);
        return $luminance > 0.45 ? '#111827' : '#ffffff';
    }
}
