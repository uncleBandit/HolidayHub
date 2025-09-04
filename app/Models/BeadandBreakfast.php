<?php

namespace App\Models;

use App\Contracts\Interface\Bookable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class BedAndBreakfast extends Model implements Bookable
{
    use HasFactory;

    protected $table = 'bed_and_breakfasts';

    /**
     * Mass assignable attributes.
     */
    protected $fillable = [
        'name',
        'description',
        'price_per_night',
        'max_guests',
        'address',
        'city',
        'country',
        'latitude',
        'longitude',
        'gallery',
        'amenities',
        'destination_id',
    ];

    /**
     * Attribute casting for modern JSON-friendly structure.
     */
    protected $casts = [
        'gallery' => 'array',      // store images as JSON array
        'amenities' => 'array',    // store amenities as JSON array
        'price_per_night' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'bnb_id');
    }

    /**
     * Accessors
     */
    public function getMainImageAttribute(): ?string
    {
        return $this->gallery[0] ?? null;
    }

    public function getAverageRatingAttribute(): float
    {
        return round($this->reviews()->avg('rating') ?? 0, 1);
    }

    public function getBasePrice(): float
    {
        return (float) $this->base_price;
    }

    /**
     * Scopes
     */
    public function scopeAvailable($query, $startDate, $endDate)
    {
        return $query->whereDoesntHave('bookings', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('check_in', [$startDate, $endDate])
              ->orWhereBetween('check_out', [$startDate, $endDate]);
        });
    }

    public function scopeAffordable($query, float $maxPrice)
    {
        return $query->where('price_per_night', '<=', $maxPrice);
    }

    // Polymorphic link back to Accommodation
    public function accommodation(): MorphOne
    {
        return $this->morphOne(Accommodation::class, 'bookable');
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(RoomAvailability::class);
    }

    public function isAvailable(string $checkIn, string $checkOut): bool
    {
        return !$this->bookings()
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->whereBetween('check_in', [$checkIn, $checkOut])
                      ->orWhereBetween('check_out', [$checkIn, $checkOut])
                      ->orWhere(function ($q) use ($checkIn, $checkOut) {
                          $q->where('check_in', '<=', $checkIn)
                            ->where('check_out', '>=', $checkOut);
                      });
            })
            ->exists();
    }

    public function getPriceForDate(string $date): float
    {
        $price = $this->availabilities()
            ->where('date', $date)
            ->value('price');

        return $price ?? $this->price_per_night;
    }
}
