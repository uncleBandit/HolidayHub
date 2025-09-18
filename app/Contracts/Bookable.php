<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;

interface Bookable
{
    /**
     * Get the unique identifier for the bookable item.
     */
    public function getId(): int;

    /**
     * Get the type of the bookable item (e.g., 'hotel', 'villa', 'flight').
     */
    public function getType(): string;

    /**
     * Get the name of the bookable item.
     */
    public function getName(): string;

    /**
     * Get a short description of the bookable item.
     */
    public function getDescription(): string;

    /**
     * Get an array of image URLs for the bookable item.
     */
    public function getImages(): array;

    /**
     * Get the base price of the bookable item.
     */
    public function getBasePrice(): float;

    /**
     * Get the price for a specific date, accounting for dynamic pricing.
     */
    public function getPriceForDate(string $date): float;

    /**
     * Check the availability for a range of dates.
     * This method could be a boolean or return a more detailed object.
     * A simple boolean check is okay for now, but a more complex return type
     * could provide more information, such as the number of remaining units.
     */
    public function isAvailable(string $checkIn, string $checkOut): bool;

    /**
     * Get the availability relationships for the bookable item.
     */
    public function availabilities(): Relation;

    /**
     * Get the number of guests included in the base price.
     */
    public function getIncludedGuests(): int;

    /**
     * Get the currency code for the bookable item (e.g., 'USD', 'EUR').
     */
    public function getCurrency(): string;

    /**
     * Get the default maximum number of guests allowed for this bookable item.
     */
    public function getDefaultMaxGuests(): int;

    public function seasonalRates(): Relation;
    public function offers(): Relation;
    //public function holidaySurcharges(): ?HasMany;
}
