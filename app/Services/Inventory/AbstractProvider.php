<?php
declare(strict_types=1);

namespace App\Services\Inventory;

abstract class AbstractProvider implements InventoryProvider
{
    public function __construct(protected array $config)
    {
    }

    public function fetchOne(string $sku): ?int
    {
        $all = $this->fetchAll();
        return isset($all[$sku]) ? (int) $all[$sku] : null;
    }

    protected function cfg(string $key, mixed $default = null): mixed
    {
        $value = $this->config[$key] ?? null;
        return $value === null || $value === '' ? $default : $value;
    }

    /**
     * Map a decoded payload into [sku => qty] using configurable field paths.
     * Supports a list of objects ([{sku, qty}, ...]) or a plain map ({"SKU1": 5}).
     */
    protected function mapItems(mixed $payload, string $itemsPath, string $skuField, string $qtyField): array
    {
        $items = $itemsPath !== '' ? data_get($payload, $itemsPath, []) : $payload;
        if (!is_array($items)) {
            throw new \RuntimeException('Items not found at path "' . $itemsPath . '" in the response.');
        }
        $out = [];
        $isList = array_is_list($items);
        foreach ($items as $key => $item) {
            if (!$isList && !is_array($item)) {
                $out[(string) $key] = (int) floor((float) $item);
                continue;
            }
            if (!is_array($item)) {
                continue;
            }
            $sku = data_get($item, $skuField);
            $qty = data_get($item, $qtyField);
            if ($sku === null || $sku === '' || !is_numeric($qty)) {
                continue;
            }
            // Same SKU in several warehouses/locations -> sum the balances.
            $out[(string) $sku] = ($out[(string) $sku] ?? 0) + (int) floor((float) $qty);
        }
        return $out;
    }

    protected static function authFields(): array
    {
        return [
            ['name' => 'auth_type', 'label' => 'Authentication', 'type' => 'select', 'options' => ['none' => 'None', 'bearer' => 'Bearer token', 'basic' => 'Basic (user:password)', 'header' => 'Custom header'], 'default' => 'none'],
            ['name' => 'auth_header', 'label' => 'Header name', 'type' => 'text', 'placeholder' => 'X-API-Key', 'help' => 'Only for "Custom header".'],
            ['name' => 'auth_value', 'label' => 'Token / credentials', 'type' => 'password', 'help' => 'Stored encrypted.'],
            ['name' => 'extra_headers', 'label' => 'Extra headers', 'type' => 'textarea', 'placeholder' => "Accept-Language: en\nX-Company: 1", 'help' => 'One "Name: value" per line.'],
        ];
    }
}
