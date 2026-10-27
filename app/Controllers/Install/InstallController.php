<?php
declare(strict_types=1);

namespace App\Controllers\Install;

use App\Core\Controller;
use App\Core\DB;
use App\Core\Request;
use App\Core\Session;
use App\Services\Installer;
use App\Services\Theme;

/**
 * First-run wizard:
 *   1. requirements  2. MySQL connection  3. ONE shop or MULTI shop (SaaS)  4. company + admin
 */
final class InstallController extends Controller
{
    protected string $layout = 'layouts/install';

    public function lang(string $code): never
    {
        setcookie('lang', $code === 'ar' ? 'ar' : 'en', ['expires' => time() + 31536000, 'path' => '/', 'samesite' => 'Lax']);
        back();
    }

    public function index(): string
    {
        $checks = [
            ['PHP 8.1+', version_compare(PHP_VERSION, '8.1.0', '>='), PHP_VERSION],
            ['PDO MySQL', extension_loaded('pdo_mysql'), ''],
            ['Sodium (encryption)', extension_loaded('sodium'), ''],
            ['cURL (integrations)', extension_loaded('curl'), ''],
            ['mbstring', extension_loaded('mbstring'), ''],
            ['fileinfo (uploads)', extension_loaded('fileinfo'), ''],
            [__('Writable') . ' config/', is_writable(BASE_PATH . '/config'), ''],
            [__('Writable') . ' public/uploads/', is_writable(BASE_PATH . '/public/uploads'), ''],
            [__('Writable') . ' storage/', is_writable(BASE_PATH . '/storage'), ''],
        ];
        $ok = !in_array(false, array_column($checks, 1), true);
        return $this->view('install/requirements', ['checks' => $checks, 'ok' => $ok, 'step' => 1]);
    }

    public function database(): string
    {
        return $this->view('install/database', ['db' => Session::get('install.db', []), 'step' => 2]);
    }

    public function saveDatabase(): never
    {
        $data = $this->validate([
            'host' => 'required|max:190',
            'port' => 'required|integer',
            'user' => 'required|max:80',
            'pass' => 'nullable',
            'platform_db' => 'required|max:50',
            'tenant_prefix' => 'nullable|max:20',
        ]);
        $data['pass'] = (string) ($_POST['pass'] ?? '');
        $data['port'] = (int) $data['port'];
        $data['per_tenant_user'] = Request::input('per_tenant_user') === '1';

        $data['tenant_prefix'] = (string) $data['tenant_prefix'];
        if (!preg_match('/^[A-Za-z0-9_-]+$/', $data['platform_db']) || !preg_match('/^[A-Za-z0-9_-]*$/', $data['tenant_prefix'])) {
            flash('error', __('Database names may only contain letters, numbers and underscores.'));
            flash('old', Request::all());
            back();
        }

        try {
            $server = DB::server(null, $data);
            $server->value('SELECT 1');
            $exists = $server->value('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?', [$data['platform_db']]);
            if ($exists && $server->value('SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?', [$data['platform_db'], 'tenants'])) {
                throw new \RuntimeException(__('Database :db already contains an installation.', ['db' => $data['platform_db']]));
            }
        } catch (\Throwable $e) {
            flash('error', __('Connection failed: :msg', ['msg' => $e->getMessage()]));
            flash('old', Request::all());
            back();
        }

        Session::set('install.db', $data);
        redirect(base_url('/install/mode'));
    }

    public function mode(): string
    {
        $this->requireDb();
        return $this->view('install/mode', ['mode' => Session::get('install.mode', 'single'), 'step' => 3]);
    }

    public function saveMode(): never
    {
        $this->requireDb();
        $mode = Request::input('mode') === 'multi' ? 'multi' : 'single';
        Session::set('install.mode', $mode);
        redirect(base_url('/install/setup'));
    }

    public function setup(): string
    {
        $this->requireDb();
        $mode = Session::get('install.mode');
        if (!$mode) {
            redirect(base_url('/install/mode'));
        }
        return $this->view('install/setup', ['mode' => $mode, 'step' => 4, 'presets' => Theme::PRESETS]);
    }

    public function finish(): never
    {
        $db = $this->requireDb();
        $mode = Session::get('install.mode', 'single');
        $data = $this->validate([
            'company_name' => 'required|max:150',
            'company_name_ar' => 'nullable|max:150',
            'admin_name' => 'required|max:120',
            'admin_email' => 'required|email|max:190',
            'admin_password' => 'required|min:8|confirmed',
            'locale' => 'required|in:en,ar',
            'primary' => 'required|color',
            'currency' => 'nullable|max:10',
            'currency_symbol' => 'nullable|max:10',
            'base_domain' => 'nullable|max:190',
        ]);
        $demo = Request::input('demo') === '1';

        try {
            Installer::run($db, $mode, $data + ['demo' => $demo]);
        } catch (\Throwable $e) {
            error_log((string) $e);
            flash('error', __('Installation failed: :msg', ['msg' => $e->getMessage()]));
            flash('old', Request::all());
            back();
        }

        Session::forget('install.db');
        Session::forget('install.mode');
        flash('success', __('Installation complete! Sign in with the admin account you just created.'));
        redirect($mode === 'multi' ? base_url('/platform/login') : base_url('/login'));
    }

    private function requireDb(): array
    {
        $db = Session::get('install.db');
        if (!$db) {
            redirect(base_url('/install/database'));
        }
        return $db;
    }
}
