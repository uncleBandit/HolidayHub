<?php

namespace App\Modules\Pricing\Domain\ValueObjects;

use InvalidArgumentException;

class PricingContext
{
    protected array $strategies = [
        'hotel' => RoomPricingStrategy::class,
        'villa' => RoomPricingStrategy::class,
        'package' => PackagePricingStrategy::class,
        'flight' => FlightPricingStrategy::class,
        'bed_and_breakfast' => BedAndBreakfastPricingStrategy::class,
    ];

    public function getStrategy(string $type): PricingStrategy
    {
        $type = strtolower($type);

        if (! isset($this->strategies[$type])) {
            throw new InvalidArgumentException("No pricing strategy defined for type: {$type}");
        }

        return app($this->strategies[$type]);
    }

    public function calculate(string $type, int $id, ?int $roomId, \Carbon\Carbon $checkIn, \Carbon\Carbon $checkOut, int $guests): float
    {
        return $this->getStrategy($type)->calculate($id, $roomId, $checkIn, $checkOut, $guests);
    }
}
