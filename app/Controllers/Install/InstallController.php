<?php
declare(strict_types=1);

namespace App\Controllers\Install;

use App\Core\Config;
use App\Core\Controller;
use App\Core\DB;
use App\Core\Database;
use App\Core\Migrator;
use App\Core\Request;
use App\Core\Session;
use App\Core\Settings;
use App\Services\TenantProvisioner;
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
            'tenant_prefix' => 'required|max:20',
        ]);
        $data['pass'] = (string) ($_POST['pass'] ?? '');
        $data['port'] = (int) $data['port'];
        $data['per_tenant_user'] = Request::input('per_tenant_user') === '1';

        if (!preg_match('/^[A-Za-z0-9_]+$/', $data['platform_db']) || !preg_match('/^[A-Za-z0-9_]+$/', $data['tenant_prefix'])) {
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

        $config = [
            'installed' => false,
            'app_name' => 'Matjar',
            'app_key' => base64_encode(random_bytes(32)),
            'mode' => $mode,
            'base_domain' => strtolower(trim((string) $data['base_domain'])),
            'timezone' => 'UTC',
            'debug' => false,
            'db' => $db,
        ];

        $createdPlatformDb = false;
        try {
            Config::write($config);

            $server = DB::server();
            $createdPlatformDb = !$server->value('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?', [$db['platform_db']]);
            $server->exec('CREATE DATABASE IF NOT EXISTS ' . Database::ident($db['platform_db']) . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $platform = DB::platform();
            Migrator::run($platform, BASE_PATH . '/database/platform');

            Settings::setMany(array_merge(Theme::defaults(), [
                'company.name' => $data['company_name'],
                'company.name_ar' => (string) $data['company_name_ar'],
                'theme.default_locale' => $data['locale'],
                'theme.primary' => $data['primary'],
                'theme.admin_menu_layout' => 'sidebar',
                'platform.allow_signup' => $mode === 'multi' ? '1' : '0',
                'theme.hero_title_en' => 'Launch your online shop in minutes',
                'theme.hero_title_ar' => 'أطلق متجرك الإلكتروني في دقائق',
                'theme.hero_subtitle_en' => 'Live ERP stock sync, delivery tracking, Arabic & English, and your own brand. Every shop gets its own secure database.',
                'theme.hero_subtitle_ar' => 'مزامنة مباشرة للمخزون، تتبع التوصيل، عربي وإنجليزي، وهويتك الخاصة. لكل متجر قاعدة بيانات آمنة خاصة به.',
            ]), $platform, 'platform_settings');

            $owner = [
                'owner_name' => $data['admin_name'],
                'owner_email' => $data['admin_email'],
                'owner_password' => $data['admin_password'],
                'locale' => $data['locale'],
                'primary' => $data['primary'],
                'currency' => $data['currency'] ?: 'USD',
                'currency_symbol' => $data['currency_symbol'] ?: '$',
            ];

            if ($mode === 'multi') {
                $plans = [
                    ['Starter', 'المبتدئ', 0, 50, 2, "Up to 50 products\n2 staff accounts\nLive delivery tracking", 0],
                    ['Business', 'الأعمال', 29, 1000, 10, "Up to 1,000 products\n10 staff accounts\nERP inventory integration\nCourier API integration", 1],
                    ['Enterprise', 'المؤسسات', 99, null, null, "Unlimited products\nUnlimited staff\nCustom domain\nPriority support", 0],
                ];
                $firstPlan = null;
                foreach ($plans as $i => [$name, $nameAr, $price, $maxP, $maxS, $features, $featured]) {
                    $id = $platform->insert('plans', [
                        'name' => $name, 'name_ar' => $nameAr, 'price' => $price, 'max_products' => $maxP, 'max_staff' => $maxS,
                        'features' => $features, 'is_featured' => $featured, 'sort' => $i, 'created_at' => now(),
                    ]);
                    $firstPlan ??= $id;
                }
                Settings::setMany(['platform.default_plan_id' => (string) $firstPlan], $platform, 'platform_settings');

                $platform->insert('platform_admins', [
                    'name' => $data['admin_name'],
                    'email' => strtolower($data['admin_email']),
                    'password' => password_hash($data['admin_password'], PASSWORD_DEFAULT),
                    'created_at' => now(),
                ]);

                if ($demo) {
                    TenantProvisioner::create($owner + ['slug' => 'demo', 'name' => 'Demo Store', 'name_ar' => 'متجر تجريبي', 'plan_id' => $firstPlan, 'demo' => true]);
                }
            } else {
                TenantProvisioner::create($owner + [
                    'slug' => 'main',
                    'name' => $data['company_name'],
                    'name_ar' => (string) $data['company_name_ar'],
                    'demo' => $demo,
                ]);
            }

            $config['installed'] = true;
            Config::write($config);
        } catch (\Throwable $e) {
            error_log((string) $e);
            if ($createdPlatformDb) {
                try {
                    DB::server()->exec('DROP DATABASE IF EXISTS ' . Database::ident($db['platform_db']));
                } catch (\Throwable) {
                }
            }
            @unlink(Config::path());
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
