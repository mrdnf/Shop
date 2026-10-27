<?php
declare(strict_types=1);

namespace App\Services\Inventory\Providers;

use App\Core\Http;
use App\Services\Inventory\AbstractProvider;

/**
 * Generic JSON/REST connector. Works with most ERPs/POS systems (Odoo, ERPNext,
 * Zoho Inventory, SAP B1 Service Layer, Dynamics, custom middleware ...) by
 * mapping the response with dot-paths.
 */
final class RestApiProvider extends AbstractProvider
{
    public static function label(): string
    {
        return 'REST / JSON API';
    }

    public static function description(): string
    {
        return 'Pull balances from any HTTP endpoint returning JSON. Map the fields with dot-paths, supports paging.';
    }

    public static function fields(): array
    {
        return array_merge([
            ['name' => 'url', 'label' => 'Stock list URL', 'type' => 'text', 'placeholder' => 'https://erp.example.com/api/stock?page={page}', 'help' => 'Use {page} for paged APIs.', 'required' => true],
            ['name' => 'method', 'label' => 'HTTP method', 'type' => 'select', 'options' => ['GET' => 'GET', 'POST' => 'POST'], 'default' => 'GET'],
            ['name' => 'body', 'label' => 'Request body (POST)', 'type' => 'textarea', 'placeholder' => '{"warehouse": "MAIN"}'],
            ['name' => 'items_path', 'label' => 'Items path', 'type' => 'text', 'placeholder' => 'data.items', 'help' => 'Dot-path to the array of items. Leave empty if the response is the array.'],
            ['name' => 'sku_field', 'label' => 'SKU field', 'type' => 'text', 'default' => 'sku', 'placeholder' => 'sku'],
            ['name' => 'qty_field', 'label' => 'Quantity field', 'type' => 'text', 'default' => 'quantity', 'placeholder' => 'quantity or stock.available'],
            ['name' => 'max_pages', 'label' => 'Max pages', 'type' => 'number', 'default' => '1', 'help' => 'Pages to fetch when the URL contains {page}.'],
            ['name' => 'single_url', 'label' => 'Single SKU URL (optional)', 'type' => 'text', 'placeholder' => 'https://erp.example.com/api/stock/{sku}', 'help' => 'Used for real-time checks at checkout.'],
            ['name' => 'single_qty_path', 'label' => 'Quantity path in single response', 'type' => 'text', 'placeholder' => 'data.quantity'],
        ], self::authFields());
    }

    public function fetchAll(): array
    {
        $url = (string) $this->cfg('url', '');
        $paged = str_contains($url, '{page}');
        $maxPages = $paged ? max(1, (int) $this->cfg('max_pages', 1)) : 1;
        $all = [];

        for ($page = 1; $page <= $maxPages; $page++) {
            $res = Http::request(
                (string) $this->cfg('method', 'GET'),
                str_replace('{page}', (string) $page, $url),
                Http::authHeaders($this->config) + ['Content-Type' => 'application/json'],
                $this->cfg('method') === 'POST' ? (string) $this->cfg('body', '{}') : null
            );
            if ($res['status'] >= 400) {
                throw new \RuntimeException("Stock API returned HTTP {$res['status']}");
            }
            if (!is_array($res['json'])) {
                throw new \RuntimeException('Stock API did not return JSON.');
            }
            $items = $this->mapItems($res['json'], (string) $this->cfg('items_path', ''), (string) $this->cfg('sku_field', 'sku'), (string) $this->cfg('qty_field', 'quantity'));
            if (!$items) {
                break;
            }
            foreach ($items as $sku => $qty) {
                $all[$sku] = ($all[$sku] ?? 0) + $qty;
            }
        }
        return $all;
    }

    public function fetchOne(string $sku): ?int
    {
        $single = (string) $this->cfg('single_url', '');
        if ($single === '') {
            return parent::fetchOne($sku);
        }
        $res = Http::request('GET', str_replace('{sku}', rawurlencode($sku), $single), Http::authHeaders($this->config), null, 8);
        if ($res['status'] >= 400 || !is_array($res['json'])) {
            return null;
        }
        $qty = data_get($res['json'], (string) $this->cfg('single_qty_path', $this->cfg('qty_field', 'quantity')));
        return is_numeric($qty) ? (int) floor((float) $qty) : null;
    }
}
