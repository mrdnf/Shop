<?php
declare(strict_types=1);

namespace App\Services\Inventory;

/**
 * Contract for any external system that knows stock balances (ERP, POS,
 * warehouse system, spreadsheet, another database ...).
 *
 * To integrate a new system, implement this interface (or extend
 * AbstractProvider) and register the class in InventoryService::PROVIDERS.
 */
interface InventoryProvider
{
    public static function label(): string;

    public static function description(): string;

    /**
     * Configuration form schema rendered automatically in the admin panel.
     * Each field: name, label, type (text|password|number|select|textarea), options?, help?, placeholder?, default?
     */
    public static function fields(): array;

    public function __construct(array $config);

    /** Full stock list: [sku => quantity]. */
    public function fetchAll(): array;

    /** Balance for a single SKU (used for real-time checks), null when unknown. */
    public function fetchOne(string $sku): ?int;
}
