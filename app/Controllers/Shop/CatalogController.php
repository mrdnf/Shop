<?php
declare(strict_types=1);

namespace App\Controllers\Shop;

use App\Core\Controller;
use App\Core\Request;
use App\Services\Inventory\InventoryService;

final class CatalogController extends Controller
{
    public function index(?array $category = null): string
    {
        $where = ['p.is_active = 1'];
        $params = [];

        $q = (string) Request::query('q', '');
        if ($q !== '') {
            $where[] = '(p.name_en LIKE ? OR p.name_ar LIKE ? OR p.sku LIKE ?)';
            array_push($params, "%{$q}%", "%{$q}%", "%{$q}%");
        }
        if ($category) {
            $where[] = '(p.category_id = ? OR p.category_id IN (SELECT id FROM categories WHERE parent_id = ?))';
            array_push($params, $category['id'], $category['id']);
        }
        if (is_numeric(Request::query('min'))) {
            $where[] = 'p.price >= ?';
            $params[] = (float) Request::query('min');
        }
        if (is_numeric(Request::query('max'))) {
            $where[] = 'p.price <= ?';
            $params[] = (float) Request::query('max');
        }
        if (Request::query('in_stock') === '1') {
            $where[] = '(p.track_stock = 0 OR p.stock > 0)';
        }
        $sort = match (Request::query('sort')) {
            'price_asc' => 'p.price ASC',
            'price_desc' => 'p.price DESC',
            'name' => 'p.name_en ASC',
            'featured' => 'p.is_featured DESC, p.id DESC',
            default => 'p.id DESC',
        };

        $page = $this->db()->paginate(
            'SELECT p.* FROM products p WHERE ' . implode(' AND ', $where) . " ORDER BY {$sort}",
            $params,
            12
        );

        return $this->view('shop/catalog', [
            'title' => $category ? tr($category, 'name') : __('All products'),
            'category' => $category,
            'categories' => $this->db()->fetchAll('SELECT * FROM categories WHERE is_active = 1 ORDER BY sort, id'),
            'page' => $page,
            'q' => $q,
        ]);
    }

    public function category(string $slug): string
    {
        $category = $this->db()->fetch('SELECT * FROM categories WHERE slug = ? AND is_active = 1', [$slug]);
        if (!$category) {
            abort(404);
        }
        return $this->index($category);
    }

    public function show(string $slug): string
    {
        $product = $this->db()->fetch('SELECT * FROM products WHERE slug = ? AND is_active = 1', [$slug]);
        if (!$product) {
            abort(404);
        }
        $available = InventoryService::available($product);
        $category = $product['category_id'] ? $this->db()->fetch('SELECT * FROM categories WHERE id = ?', [$product['category_id']]) : null;
        $related = $this->db()->fetchAll(
            'SELECT * FROM products WHERE is_active = 1 AND id <> ? AND category_id <=> ? ORDER BY RAND() LIMIT 4',
            [$product['id'], $product['category_id']]
        );
        return $this->view('shop/product', [
            'title' => tr($product, 'name'),
            'product' => $product,
            'available' => $available,
            'category' => $category,
            'related' => $related,
        ]);
    }
}
