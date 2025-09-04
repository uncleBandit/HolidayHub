<?php

namespace App\Contracts\Interface;

use Illuminate\Database\Eloquent\Relations\HasMany;

interface Bookable
{
    public function getBasePrice(): float;

    public function isAvailable(string $checkIn, string $checkOut): bool;

    public function getPriceForDate(string $date): float;

    public function availabilities(): HasMany;
}
