<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Request;

/** Shop team: admins, staff (orders & catalogue) and delivery drivers. */
final class StaffController extends AdminController
{
    public function index(): string
    {
        return $this->view('admin/staff/index', [
            'title' => __('Team'),
            'users' => $this->db()->fetchAll("SELECT * FROM users WHERE role IN ('admin','staff','driver') ORDER BY FIELD(role,'admin','staff','driver'), name"),
            'limit' => plan_limit('max_staff'),
        ]);
    }

    public function store(): never
    {
        $limit = plan_limit('max_staff');
        if ($limit !== null && (int) $this->db()->value("SELECT COUNT(*) FROM users WHERE role IN ('admin','staff','driver')") >= $limit) {
            flash('warning', __('Your plan allows up to :n team members.', ['n' => $limit]));
            back();
        }
        $data = $this->validate([
            'name' => 'required|max:120',
            'email' => 'required|email|max:190',
            'phone' => 'nullable|max:40',
            'role' => 'required|in:admin,staff,driver',
            'password' => 'required|min:8',
        ]);
        $email = strtolower((string) $data['email']);
        if ($this->db()->value('SELECT 1 FROM users WHERE email = ?', [$email])) {
            flash('error', __('An account with this email already exists.'));
            back();
        }
        $this->db()->insert('users', [
            'name' => $data['name'], 'email' => $email, 'phone' => $data['phone'] ?: null, 'role' => $data['role'],
            'password' => password_hash((string) $_POST['password'], PASSWORD_DEFAULT), 'created_at' => now(),
        ]);
        flash('success', __('Team member added.'));
        redirect(url('/admin/staff'));
    }

    public function update(string $id): never
    {
        $user = $this->db()->fetch("SELECT * FROM users WHERE id = ? AND role <> 'customer'", [(int) $id]) ?? abort(404);
        $data = $this->validate(['name' => 'required|max:120', 'phone' => 'nullable|max:40', 'role' => 'required|in:admin,staff,driver', 'status' => 'required|in:active,blocked']);
        if ((int) $user['id'] === Auth::id() && ($data['role'] !== 'admin' || $data['status'] !== 'active')) {
            flash('error', __('You cannot demote or block yourself.'));
            back();
        }
        $row = ['name' => $data['name'], 'phone' => $data['phone'] ?: null, 'role' => $data['role'], 'status' => $data['status'], 'updated_at' => now()];
        if (($pw = (string) ($_POST['password'] ?? '')) !== '') {
            if (strlen($pw) < 8) {
                flash('error', __(':field must be at least :min.', ['field' => __('Password'), 'min' => 8]));
                back();
            }
            $row['password'] = password_hash($pw, PASSWORD_DEFAULT);
        }
        $this->db()->update('users', $row, 'id = ?', [$user['id']]);
        flash('success', __('Team member updated.'));
        redirect(url('/admin/staff'));
    }

    public function destroy(string $id): never
    {
        if ((int) $id === Auth::id()) {
            flash('error', __('You cannot delete yourself.'));
            back();
        }
        $this->db()->delete('users', "id = ? AND role <> 'customer'", [(int) $id]);
        flash('success', __('Team member removed.'));
        redirect(url('/admin/staff'));
    }
}
