<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Settings;
use App\Core\Upload;
use App\Services\Theme;

/** Company branding: logo, names, colours, fonts, language, light/dark, menu layout. */
final class ThemeController extends AdminController
{
    public function edit(): string
    {
        return $this->view('admin/theme/edit', [
            'title' => __('Branding & theme'),
            'theme' => Settings::all() + Theme::defaults(),
            'fontsEn' => Theme::FONTS_EN,
            'fontsAr' => Theme::FONTS_AR,
            'presets' => Theme::PRESETS,
            'action' => url('/admin/theme'),
            'scope' => 'shop',
        ]);
    }

    public function update(): never
    {
        self::saveTheme();
        flash('success', __('Theme saved.'));
        redirect(url('/admin/theme'));
    }

    /** Shared with the platform settings screen (same keys, different store). */
    public static function saveTheme(): void
    {
        $in = static fn (string $k, string $d = '') => trim((string) Request::input($k, $d));
        $hex = static fn (string $v, string $d) => preg_match('/^#[0-9a-fA-F]{6}$/', $v) ? $v : $d;
        $defaults = Theme::defaults();

        $values = [
            'company.name' => mb_substr($in('company_name'), 0, 150) ?: (string) Settings::get('company.name', 'Matjar'),
            'company.name_ar' => mb_substr($in('company_name_ar'), 0, 150),
            'company.tagline_en' => mb_substr($in('tagline_en'), 0, 190),
            'company.tagline_ar' => mb_substr($in('tagline_ar'), 0, 190),
            'theme.primary' => $hex($in('primary'), $defaults['theme.primary']),
            'theme.secondary' => $hex($in('secondary'), $defaults['theme.secondary']),
            'theme.accent' => $hex($in('accent'), $defaults['theme.accent']),
            'theme.font_en' => in_array($in('font_en'), Theme::FONTS_EN, true) ? $in('font_en') : $defaults['theme.font_en'],
            'theme.font_ar' => in_array($in('font_ar'), Theme::FONTS_AR, true) ? $in('font_ar') : $defaults['theme.font_ar'],
            'theme.default_locale' => $in('default_locale') === 'ar' ? 'ar' : 'en',
            'theme.default_mode' => in_array($in('default_mode'), ['light', 'dark', 'system'], true) ? $in('default_mode') : 'light',
            'theme.menu_layout' => $in('menu_layout') === 'sidebar' ? 'sidebar' : 'top',
            'theme.admin_menu_layout' => $in('admin_menu_layout') === 'top' ? 'top' : 'sidebar',
            'theme.radius' => array_key_exists($in('radius'), Theme::RADIUS) ? $in('radius') : 'soft',
            'theme.hero_title_en' => mb_substr($in('hero_title_en'), 0, 190),
            'theme.hero_title_ar' => mb_substr($in('hero_title_ar'), 0, 190),
            'theme.hero_subtitle_en' => mb_substr($in('hero_subtitle_en'), 0, 300),
            'theme.hero_subtitle_ar' => mb_substr($in('hero_subtitle_ar'), 0, 300),
        ];

        foreach (['logo' => 'company.logo', 'logo_dark' => 'company.logo_dark', 'favicon' => 'company.favicon', 'hero_image' => 'theme.hero_image'] as $field => $key) {
            if ($path = Upload::image(Request::file($field), 'branding', 2048)) {
                Upload::delete((string) Settings::get($key));
                $values[$key] = $path;
            } elseif (Request::input('remove_' . $field) === '1') {
                Upload::delete((string) Settings::get($key));
                $values[$key] = '';
            }
        }
        Settings::setMany($values);
    }
}
