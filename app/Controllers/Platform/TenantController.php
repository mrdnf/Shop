<?php
declare(strict_types=1);

namespace App\Controllers\Platform;

use App\Core\Request;
use App\Core\Tenant;
use App\Services\TenantProvisioner;

/** Shops of the SaaS. Each one is backed by its own database. */
final class TenantController extends PlatformController
{
    public function index(): string
    {
        $where = ['1=1'];
        $params = [];
        if ($q = (string) Request::query('q', '')) {
            $where[] = '(t.name LIKE ? OR t.slug LIKE ? OR t.owner_email LIKE ? OR t.domain LIKE ?)';
            array_push($params, "%{$q}%", "%{$q}%", "%{$q}%", "%{$q}%");
        }
        if (in_array($status = (string) Request::query('status', ''), ['active', 'trial', 'suspended'], true)) {
            $where[] = 't.status = ?';
            $params[] = $status;
        }
        $page = $this->db()->paginate(
            'SELECT t.*, p.name AS plan_name FROM tenants t LEFT JOIN plans p ON p.id = t.plan_id WHERE ' . implode(' AND ', $where) . ' ORDER BY t.id DESC',
            $params,
            20
        );
        foreach ($page['items'] as &$t) {
            $t['url'] = Tenant::publicUrl($t);
        }
        unset($t);
        return $this->view('platform/tenants/index', ['title' => __('Shops'), 'page' => $page, 'status' => $status]);
    }

    public function create(): string
    {
        return $this->view('platform/tenants/form', ['title' => __('New shop'), 'tenant' => null, 'plans' => $this->plans()]);
    }

    public function store(): never
    {
        $data = $this->validate([
            'name' => 'required|max:150',
            'slug' => 'required|slug|min:2|max:40',
            'domain' => 'nullable|max:190',
            'plan_id' => 'nullable|integer',
            'status' => 'required|in:active,trial,suspended',
            'owner_name' => 'required|max:120',
            'owner_email' => 'required|email|max:190',
            'owner_password' => 'required|min:8',
            'locale' => 'required|in:en,ar',
        ]);
        try {
            $id = TenantProvisioner::create([
                'slug' => (string) $data['slug'],
                'name' => (string) $data['name'],
                'domain' => $data['domain'] ?: null,
                'plan_id' => $data['plan_id'] ? (int) $data['plan_id'] : null,
                'status' => (string) $data['status'],
                'owner_name' => (string) $data['owner_name'],
                'owner_email' => (string) $data['owner_email'],
                'owner_password' => (string) $_POST['owner_password'],
                'locale' => (string) $data['locale'],
                'demo' => Request::input('demo') === '1',
            ]);
        } catch (\InvalidArgumentException $e) {
            flash('error', $e->getMessage());
            flash('old', Request::all());
            back();
        }
        flash('success', __('Shop created with its own database.'));
        redirect(base_url('/platform/shops/' . $id . '/edit'));
    }

    public function edit(string $id): string
    {
        $tenant = $this->find((int) $id);
        $tenant['url'] = Tenant::publicUrl($tenant);
        return $this->view('platform/tenants/form', ['title' => $tenant['name'], 'tenant' => $tenant, 'plans' => $this->plans()]);
    }

    public function update(string $id): never
    {
        $tenant = $this->find((int) $id);
        $data = $this->validate([
            'name' => 'required|max:150',
            'domain' => 'nullable|max:190',
            'plan_id' => 'nullable|integer',
            'status' => 'required|in:active,trial,suspended',
            'trial_ends_at' => 'nullable|date',
        ]);
        $domain = $data['domain'] ? strtolower((string) $data['domain']) : null;
        if ($domain && $this->db()->value('SELECT 1 FROM tenants WHERE domain = ? AND id <> ?', [$domain, $tenant['id']])) {
            flash('error', __('This domain is already used by another shop.'));
            back();
        }
        $this->db()->update('tenants', [
            'name' => $data['name'],
            'domain' => $domain,
            'plan_id' => $data['plan_id'] ? (int) $data['plan_id'] : null,
            'status' => $data['status'],
            'trial_ends_at' => $data['trial_ends_at'] ?: null,
            'updated_at' => now(),
        ], 'id = ?', [$tenant['id']]);
        flash('success', __('Shop updated.'));
        back();
    }

    public function destroy(string $id): never
    {
        $tenant = $this->find((int) $id);
        if (Request::input('confirm') !== $tenant['slug']) {
            flash('error', __('Type the shop address to confirm deletion.'));
            back();
        }
        TenantProvisioner::delete($tenant);
        flash('success', __('Shop and its database were deleted.'));
        redirect(base_url('/platform/shops'));
    }

    private function find(int $id): array
    {
        return $this->db()->fetch('SELECT * FROM tenants WHERE id = ?', [$id]) ?? abort(404);
    }

    private function plans(): array
    {
        return $this->db()->fetchAll('SELECT * FROM plans ORDER BY sort, price');
    }
}
