<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Upload;

final class CategoryController extends AdminController
{
    public function index(): string
    {
        return $this->view('admin/categories/index', [
            'title' => __('Categories'),
            'categories' => $this->db()->fetchAll(
                'SELECT c.*, p.name_en AS parent_en, (SELECT COUNT(*) FROM products x WHERE x.category_id = c.id) AS products_count
                 FROM categories c LEFT JOIN categories p ON p.id = c.parent_id ORDER BY c.sort, c.id'
            ),
        ]);
    }

    public function store(): never
    {
        $data = $this->payload();
        $data['image'] = Upload::image(Request::file('image'), 'categories');
        $data['created_at'] = now();
        $this->db()->insert('categories', $data);
        flash('success', __('Category created.'));
        redirect(url('/admin/categories'));
    }

    public function update(string $id): never
    {
        $category = $this->db()->fetch('SELECT * FROM categories WHERE id = ?', [(int) $id]) ?? abort(404);
        $data = $this->payload((int) $id);
        if ((int) $data['parent_id'] === (int) $id) {
            $data['parent_id'] = null;
        }
        if ($image = Upload::image(Request::file('image'), 'categories')) {
            Upload::delete($category['image']);
            $data['image'] = $image;
        }
        $this->db()->update('categories', $data, 'id = ?', [(int) $id]);
        flash('success', __('Category saved.'));
        redirect(url('/admin/categories'));
    }

    public function destroy(string $id): never
    {
        $category = $this->db()->fetch('SELECT * FROM categories WHERE id = ?', [(int) $id]) ?? abort(404);
        $this->db()->delete('categories', 'id = ?', [(int) $id]);
        Upload::delete($category['image']);
        flash('success', __('Category deleted.'));
        redirect(url('/admin/categories'));
    }

    private function payload(?int $id = null): array
    {
        $data = $this->validate([
            'name_en' => 'required|max:120',
            'name_ar' => 'nullable|max:120',
            'parent_id' => 'nullable|integer',
            'icon' => 'nullable|max:40',
            'sort' => 'nullable|integer',
        ]);
        $slug = slugify((string) $data['name_en']);
        $base = $slug;
        $n = 2;
        while ($this->db()->value('SELECT 1 FROM categories WHERE slug = ? AND id <> ?', [$slug, $id ?? 0])) {
            $slug = $base . '-' . $n++;
        }
        return [
            'name_en' => $data['name_en'],
            'name_ar' => $data['name_ar'] ?: null,
            'slug' => $slug,
            'parent_id' => $data['parent_id'] ? (int) $data['parent_id'] : null,
            'icon' => preg_replace('/[^a-z0-9-]/', '', strtolower((string) $data['icon'])) ?: null,
            'sort' => (int) ($data['sort'] ?? 0),
            'is_active' => Request::input('is_active') === '1' ? 1 : 0,
        ];
    }
}
