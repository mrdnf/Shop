<?php
declare(strict_types=1);

namespace App\Controllers\Platform;

use App\Core\Lang;
use App\Core\Request;
use App\Core\Settings;
use App\Core\Tenant;
use App\Services\TenantProvisioner;

/** Public SaaS website: landing, pricing, shop directory and self-service sign-up. */
final class HomeController extends PlatformController
{
    protected string $layout = 'layouts/landing';

    public function index(): string
    {
        $shops = $this->db()->fetchAll("SELECT * FROM tenants WHERE status IN ('active','trial') ORDER BY id DESC LIMIT 12");
        foreach ($shops as &$shop) {
            $shop['url'] = Tenant::publicUrl($shop);
        }
        unset($shop);
        return $this->view('platform/landing', [
            'title' => company_name(),
            'plans' => $this->db()->fetchAll('SELECT * FROM plans WHERE is_active = 1 ORDER BY sort, price'),
            'shops' => $shops,
            'signup' => Settings::get('platform.allow_signup', '0') === '1',
        ]);
    }

    public function lang(string $code): never
    {
        setcookie('lang', isset(Lang::AVAILABLE[$code]) ? $code : 'en', ['expires' => time() + 31536000, 'path' => '/', 'samesite' => 'Lax']);
        back();
    }

    public function signup(): string
    {
        $this->ensureSignup();
        return $this->view('platform/signup', [
            'title' => __('Create your shop'),
            'plans' => $this->db()->fetchAll('SELECT * FROM plans WHERE is_active = 1 ORDER BY sort, price'),
            'selected' => (int) Request::query('plan', (int) Settings::get('platform.default_plan_id', 0)),
        ]);
    }

    public function createShop(): never
    {
        $this->ensureSignup();
        $data = $this->validate([
            'shop_name' => 'required|max:150',
            'slug' => 'required|slug|min:3|max:40',
            'owner_name' => 'required|max:120',
            'owner_email' => 'required|email|max:190',
            'password' => 'required|min:8|confirmed',
            'plan_id' => 'nullable|integer',
        ]);
        $planId = (int) $data['plan_id'];
        if (!$planId || !$this->db()->value('SELECT 1 FROM plans WHERE id = ? AND is_active = 1', [$planId])) {
            $planId = (int) Settings::get('platform.default_plan_id', 0) ?: null;
        }
        try {
            $id = TenantProvisioner::create([
                'slug' => (string) $data['slug'],
                'name' => (string) $data['shop_name'],
                'owner_name' => (string) $data['owner_name'],
                'owner_email' => (string) $data['owner_email'],
                'owner_password' => (string) $_POST['password'],
                'plan_id' => $planId,
                'status' => 'trial',
                'locale' => locale(),
                'demo' => Request::input('demo') === '1',
            ]);
        } catch (\InvalidArgumentException $e) {
            flash('error', $e->getMessage());
            flash('old', Request::all());
            back();
        }
        $tenant = $this->db()->fetch('SELECT * FROM tenants WHERE id = ?', [$id]);
        flash('success', __('Your shop is ready! Sign in to start selling.'));
        redirect(Tenant::publicUrl($tenant) . '/login');
    }

    private function ensureSignup(): void
    {
        if (Settings::get('platform.allow_signup', '0') !== '1') {
            abort(404);
        }
    }
}
