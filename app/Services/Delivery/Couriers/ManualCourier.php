<?php
declare(strict_types=1);

namespace App\Services\Delivery\Couriers;

use App\Services\Delivery\CourierDriver;

/**
 * Third-party courier without an API connection (Aramex, DHL, SMSA, local
 * companies ...): staff enter the courier's tracking number, customers get a
 * deep link to the courier's tracking page plus our own timeline.
 */
final class ManualCourier implements CourierDriver
{
    public function __construct(private array $config, private array $courier)
    {
    }

    public static function label(): string
    {
        return 'Third-party courier (manual)';
    }

    public static function description(): string
    {
        return 'Enter the courier tracking number; customers get a link to the courier tracking page.';
    }

    public static function fields(): array
    {
        return [];
    }

    public function create(array $shipment, array $order, array $items): array
    {
        return [];
    }

    public function track(array $shipment): array
    {
        return [];
    }
}
