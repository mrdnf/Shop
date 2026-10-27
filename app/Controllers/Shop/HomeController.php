<?php
declare(strict_types=1);

namespace App\Controllers\Shop;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Lang;

final class HomeController extends Controller
{
    public function index(): string
    {
        $db = $this->db();
        return $this->view('shop/home', [
            'title' => company_name(),
            'categories' => $db->fetchAll(
                'SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.is_active = 1) AS products_count
                 FROM categories c WHERE c.is_active = 1 AND c.parent_id IS NULL ORDER BY c.sort, c.id LIMIT 8'
            ),
            'featured' => $db->fetchAll('SELECT * FROM products WHERE is_active = 1 AND is_featured = 1 ORDER BY id DESC LIMIT 8'),
            'latest' => $db->fetchAll('SELECT * FROM products WHERE is_active = 1 ORDER BY id DESC LIMIT 8'),
        ]);
    }

    public function lang(string $code): never
    {
        $code = isset(Lang::AVAILABLE[$code]) ? $code : 'en';
        setcookie('lang', $code, ['expires' => time() + 31536000, 'path' => '/', 'samesite' => 'Lax']);
        if (Auth::check()) {
            $this->db()->update('users', ['locale' => $code], 'id = ?', [Auth::id()]);
        }
        back();
    }
}
