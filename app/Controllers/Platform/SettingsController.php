<?php
declare(strict_types=1);

namespace App\Controllers\Platform;

use App\Controllers\Admin\ThemeController;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Settings;
use App\Services\Theme;

/** Branding of the SaaS platform itself + sign-up policy. */
final class SettingsController extends PlatformController
{
    public function edit(): string
    {
        return $this->view('platform/settings', [
            'title' => __('Platform settings'),
            'theme' => Settings::all() + Theme::defaults(),
            'fontsEn' => Theme::FONTS_EN,
            'fontsAr' => Theme::FONTS_AR,
            'presets' => Theme::PRESETS,
            'plans' => $this->db()->fetchAll('SELECT * FROM plans ORDER BY sort'),
            'action' => base_url('/platform/settings'),
            'scope' => 'platform',
        ]);
    }

    public function update(): never
    {
        ThemeController::saveTheme();
        Settings::setMany([
            'platform.allow_signup' => Request::input('allow_signup') === '1' ? '1' : '0',
            'platform.default_plan_id' => (string) (int) Request::input('default_plan_id', 0),
        ]);
        flash('success', __('Settings saved.'));
        redirect(base_url('/platform/settings'));
    }

    public function password(): never
    {
        $this->validate(['current_password' => 'required', 'password' => 'required|min:8|confirmed']);
        if (!password_verify((string) $_POST['current_password'], (string) auth()['password'])) {
            flash('error', __('Your current password is incorrect.'));
            back();
        }
        $this->db()->update('platform_admins', ['password' => password_hash((string) $_POST['password'], PASSWORD_DEFAULT)], 'id = ?', [Auth::id()]);
        flash('success', __('Password changed.'));
        back();
    }
}
