<?php
declare(strict_types=1);

namespace App\Services\Delivery;

/**
 * A delivery method. Implement this to plug in any courier company API and
 * register it in DeliveryService::DRIVERS.
 */
interface CourierDriver
{
    public static function label(): string;

    public static function description(): string;

    /** Config form schema (same format as inventory providers). */
    public static function fields(): array;

    public function __construct(array $config, array $courier);

    /**
     * Register the shipment with the courier.
     * @return array{tracking_number?: string, external_id?: string, label_url?: string}
     */
    public function create(array $shipment, array $order, array $items): array;

    /**
     * Fetch tracking events from the courier.
     * @return list<array{status: string, note?: string, location?: string, time?: string, ref?: string}>
     */
    public function track(array $shipment): array;
}
