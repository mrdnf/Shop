<?php
declare(strict_types=1);

namespace App\Services\Delivery\Couriers;

use App\Services\Delivery\CourierDriver;

/** The shop's own drivers: tracking is driven from the driver app (status + live GPS). */
final class InHouseCourier implements CourierDriver
{
    public function __construct(private array $config, private array $courier)
    {
    }

    public static function label(): string
    {
        return 'In-house fleet';
    }

    public static function description(): string
    {
        return 'Your own drivers update status and share live GPS location from the driver app.';
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
