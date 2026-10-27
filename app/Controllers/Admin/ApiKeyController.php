<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Crypto;
use App\Core\Session;
use App\Core\Settings;

final class ApiKeyController extends AdminController
{
    public function index(): string
    {
        return $this->view('admin/apikeys/index', [
            'title' => __('API & webhooks'),
            'keys' => $this->db()->fetchAll('SELECT * FROM api_keys ORDER BY revoked_at IS NULL DESC, id DESC'),
            'newKey' => Session::getFlash('new_key'),
            'apiBase' => full_url(url('/api/v1')),
            'webhookSecret' => (string) Settings::get('inventory.webhook_secret', ''),
        ]);
    }

    public function store(): never
    {
        $data = $this->validate(['name' => 'required|max:120']);
        $key = 'mk_' . Crypto::token(30);
        $this->db()->insert('api_keys', [
            'name' => $data['name'],
            'key_prefix' => substr($key, 0, 10),
            'key_hash' => hash('sha256', $key),
            'created_at' => now(),
        ]);
        flash('new_key', $key);
        flash('success', __('API key created. Copy it now, it will not be shown again.'));
        redirect(url('/admin/api-keys'));
    }

    public function revoke(string $id): never
    {
        $this->db()->update('api_keys', ['revoked_at' => now()], 'id = ? AND revoked_at IS NULL', [(int) $id]);
        flash('success', __('API key revoked.'));
        redirect(url('/admin/api-keys'));
    }
}
