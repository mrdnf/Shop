<?php
declare(strict_types=1);

namespace App\Controllers\Shop;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;

final class AuthController extends Controller
{
    protected string $layout = 'layouts/auth';

    public function login(): string
    {
        return $this->view('shop/auth/login', ['title' => __('Sign in'), 'next' => (string) Request::query('next', '')]);
    }

    public function authenticate(): never
    {
        $data = $this->validate(['email' => 'required|email', 'password' => 'required']);

        $attempts = array_filter(Session::get('login_attempts', []), static fn ($t) => $t > time() - 600);
        if (count($attempts) >= 5) {
            flash('error', __('Too many attempts. Please wait a few minutes and try again.'));
            back();
        }

        $user = Auth::attempt((string) $data['email'], (string) $_POST['password']);
        if (!$user) {
            $attempts[] = time();
            Session::set('login_attempts', $attempts);
            flash('error', __('These credentials do not match our records.'));
            flash('old', ['email' => $data['email']]);
            back();
        }
        Session::forget('login_attempts');
        Auth::login($user);
        if ($user['locale']) {
            setcookie('lang', $user['locale'], ['expires' => time() + 31536000, 'path' => '/', 'samesite' => 'Lax']);
        }
        $next = (string) Request::input('next', '');
        redirect(self::safeNext($next) ?? home_for_role());
    }

    public function register(): string
    {
        return $this->view('shop/auth/register', ['title' => __('Create account'), 'next' => (string) Request::query('next', '')]);
    }

    public function store(): never
    {
        $data = $this->validate([
            'name' => 'required|max:120',
            'email' => 'required|email|max:190',
            'phone' => 'nullable|max:40',
            'password' => 'required|min:8|confirmed',
        ]);
        $email = strtolower((string) $data['email']);
        if ($this->db()->value('SELECT 1 FROM users WHERE email = ?', [$email])) {
            flash('error', __('An account with this email already exists.'));
            flash('old', Request::all());
            back();
        }
        $id = $this->db()->insert('users', [
            'name' => $data['name'],
            'email' => $email,
            'phone' => $data['phone'] ?: null,
            'password' => password_hash((string) $_POST['password'], PASSWORD_DEFAULT),
            'role' => 'customer',
            'locale' => locale(),
            'created_at' => now(),
        ]);
        Auth::login(['id' => $id]);
        flash('success', __('Welcome, :name!', ['name' => $data['name']]));
        redirect(self::safeNext((string) Request::input('next', '')) ?? url('/account'));
    }

    public function logout(): never
    {
        Auth::logout();
        redirect(url('/'));
    }

    /** Only allow local paths for post-login redirects. */
    private static function safeNext(string $next): ?string
    {
        return $next !== '' && str_starts_with($next, '/') && !str_starts_with($next, '//') && !str_contains($next, '\\') ? url($next) : null;
    }
}
