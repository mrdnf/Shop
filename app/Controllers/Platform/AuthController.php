<?php
declare(strict_types=1);

namespace App\Controllers\Platform;

use App\Core\Auth;
use App\Core\Session;

final class AuthController extends PlatformController
{
    protected string $layout = 'layouts/auth';

    public function login(): string
    {
        if (Auth::check()) {
            redirect(base_url('/platform'));
        }
        return $this->view('platform/login', ['title' => __('Platform sign in')]);
    }

    public function authenticate(): never
    {
        $data = $this->validate(['email' => 'required|email', 'password' => 'required']);
        $attempts = array_filter(Session::get('platform_attempts', []), static fn ($t) => $t > time() - 600);
        if (count($attempts) >= 5) {
            flash('error', __('Too many attempts. Please wait a few minutes and try again.'));
            back();
        }
        $user = Auth::attempt((string) $data['email'], (string) $_POST['password']);
        if (!$user) {
            $attempts[] = time();
            Session::set('platform_attempts', $attempts);
            flash('error', __('These credentials do not match our records.'));
            flash('old', ['email' => $data['email']]);
            back();
        }
        Session::forget('platform_attempts');
        Auth::login($user);
        redirect(base_url('/platform'));
    }

    public function logout(): never
    {
        Auth::logout();
        redirect(base_url('/platform/login'));
    }
}
