<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Upload;
use App\Services\Inventory\InventoryService;

final class ProductController extends AdminController
{
    public function index(): string
    {
        $where = ['1=1'];
        $params = [];
        if ($q = (string) Request::query('q', '')) {
            $where[] = '(p.name_en LIKE ? OR p.name_ar LIKE ? OR p.sku LIKE ?)';
            array_push($params, "%{$q}%", "%{$q}%", "%{$q}%");
        }
        if ($cat = (int) Request::query('category', 0)) {
            $where[] = 'p.category_id = ?';
            $params[] = $cat;
        }
        match (Request::query('stock')) {
            'low' => $where[] = 'p.track_stock = 1 AND p.stock <= p.low_stock_threshold AND p.stock > 0',
            'out' => $where[] = 'p.track_stock = 1 AND p.stock <= 0',
            default => null,
        };

        return $this->view('admin/products/index', [
            'title' => __('Products'),
            'page' => $this->db()->paginate(
                'SELECT p.*, c.name_en AS category_en, c.name_ar AS category_ar FROM products p
                 LEFT JOIN categories c ON c.id = p.category_id WHERE ' . implode(' AND ', $where) . ' ORDER BY p.id DESC',
                $params,
                15
            ),
            'categories' => $this->categories(),
            'provider' => InventoryService::providerKey(),
        ]);
    }

    public function create(): string
    {
        $limit = plan_limit('max_products');
        if ($limit !== null && (int) $this->db()->value('SELECT COUNT(*) FROM products') >= $limit) {
            flash('warning', __('Your plan allows up to :n products. Upgrade to add more.', ['n' => $limit]));
            redirect(url('/admin/products'));
        }
        return $this->view('admin/products/form', ['title' => __('New product'), 'product' => null, 'categories' => $this->categories(), 'logs' => []]);
    }

    public function edit(string $id): string
    {
        $product = $this->find((int) $id);
        return $this->view('admin/products/form', [
            'title' => tr($product, 'name'),
            'product' => $product,
            'categories' => $this->categories(),
            'logs' => $this->db()->fetchAll('SELECT * FROM inventory_logs WHERE product_id = ? ORDER BY id DESC LIMIT 10', [$product['id']]),
        ]);
    }

    public function store(): never
    {
        $limit = plan_limit('max_products');
        if ($limit !== null && (int) $this->db()->value('SELECT COUNT(*) FROM products') >= $limit) {
            abort(403, __('Your plan allows up to :n products. Upgrade to add more.', ['n' => $limit]));
        }
        $data = $this->payload();
        $data['image'] = Upload::image(Request::file('image'), 'products');
        $data['created_at'] = now();
        $id = $this->db()->insert('products', $data);
        if ((int) $data['stock'] !== 0) {
            $this->db()->insert('inventory_logs', ['product_id' => $id, 'change_qty' => $data['stock'], 'balance' => $data['stock'], 'reason' => 'Initial stock', 'source' => 'manual', 'created_at' => now()]);
        }
        flash('success', __('Product created.'));
        redirect(url('/admin/products/' . $id . '/edit'));
    }

    public function update(string $id): never
    {
        $product = $this->find((int) $id);
        $data = $this->payload((int) $id);
        unset($data['stock']); // stock changes go through stock() so they are logged
        if ($image = Upload::image(Request::file('image'), 'products')) {
            Upload::delete($product['image']);
            $data['image'] = $image;
        } elseif (Request::input('remove_image') === '1') {
            Upload::delete($product['image']);
            $data['image'] = null;
        }
        $data['updated_at'] = now();
        $this->db()->update('products', $data, 'id = ?', [$product['id']]);
        flash('success', __('Product saved.'));
        back();
    }

    public function stock(string $id): never
    {
        $product = $this->find((int) $id);
        $mode = Request::input('mode', 'set');
        $qty = (int) Request::input('qty', 0);
        $reason = (string) Request::input('reason', '') ?: __('Manual adjustment');
        if ($mode === 'set') {
            InventoryService::setBalance((int) $product['id'], $qty, 'manual', $reason, (int) $product['stock']);
        } else {
            InventoryService::adjust((int) $product['id'], $qty, 'manual', $reason);
        }
        flash('success', __('Stock updated.'));
        back();
    }

    public function destroy(string $id): never
    {
        $product = $this->find((int) $id);
        $this->db()->delete('products', 'id = ?', [$product['id']]);
        Upload::delete($product['image']);
        flash('success', __('Product deleted.'));
        redirect(url('/admin/products'));
    }

    private function payload(?int $id = null): array
    {
        $data = $this->validate([
            'name_en' => 'required|max:190',
            'name_ar' => 'nullable|max:190',
            'sku' => 'required|max:100',
            'category_id' => 'nullable|integer',
            'price' => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'weight' => 'nullable|numeric|min:0',
            'description_en' => 'nullable|max:20000',
            'description_ar' => 'nullable|max:20000',
            'stock_source' => 'required|in:auto,local',
        ]);
        $db = $this->db();
        if ($db->value('SELECT 1 FROM products WHERE sku = ? AND id <> ?', [$data['sku'], $id ?? 0])) {
            flash('error', __('Another product already uses this SKU.'));
            flash('old', Request::all());
            back();
        }
        $slug = slugify((string) (Request::input('slug') ?: $data['name_en']));
        $base = $slug;
        $n = 2;
        while ($db->value('SELECT 1 FROM products WHERE slug = ? AND id <> ?', [$slug, $id ?? 0])) {
            $slug = $base . '-' . $n++;
        }
        return [
            'name_en' => $data['name_en'],
            'name_ar' => $data['name_ar'] ?: null,
            'sku' => $data['sku'],
            'slug' => $slug,
            'category_id' => $data['category_id'] ? (int) $data['category_id'] : null,
            'price' => (float) $data['price'],
            'compare_price' => $data['compare_price'] !== null && $data['compare_price'] !== '' ? (float) $data['compare_price'] : null,
            'cost' => $data['cost'] !== null && $data['cost'] !== '' ? (float) $data['cost'] : null,
            'stock' => (int) ($data['stock'] ?? 0),
            'low_stock_threshold' => (int) ($data['low_stock_threshold'] ?? 5),
            'weight' => $data['weight'] !== null && $data['weight'] !== '' ? (float) $data['weight'] : null,
            'description_en' => $data['description_en'] ?: null,
            'description_ar' => $data['description_ar'] ?: null,
            'stock_source' => $data['stock_source'],
            'track_stock' => Request::input('track_stock') === '1' ? 1 : 0,
            'is_active' => Request::input('is_active') === '1' ? 1 : 0,
            'is_featured' => Request::input('is_featured') === '1' ? 1 : 0,
        ];
    }

    private function find(int $id): array
    {
        return $this->db()->fetch('SELECT * FROM products WHERE id = ?', [$id]) ?? abort(404);
    }

    private function categories(): array
    {
        return $this->db()->fetchAll('SELECT * FROM categories ORDER BY sort, name_en');
    }
}
