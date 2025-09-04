<?php

namespace App\Models;

use App\Contracts\Interface\Bookable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Villa extends Model implements Bookable
{
    use HasFactory;

    protected $table = 'villas';

    /**
     * Mass assignable attributes.
     */
    protected $fillable = [
        'name',
        'description',
        'base_price',      // default nightly rate
        'max_guests',
        'bedrooms',
        'bathrooms',
        'address',
        'city',
        'country',
        'latitude',
        'longitude',
        'gallery',
        'amenities',
        'destination_id',
        'status',          // available, maintenance, inactive
    ];

    /**
     * Casts for JSON-friendly data handling.
     */
    protected $casts = [
        'gallery' => 'array',
        'amenities' => 'array',
        'base_price' => 'float',
        'latitude' => 'float',
        'longitude' => 'float',
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
        return $this->hasMany(Review::class, 'villa_id');
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(RoomAvailability::class, 'villa_id');
    }

    // Polymorphic link to shared accommodation wrapper
    public function accommodation(): MorphOne
    {
        return $this->morphOne(Accommodation::class, 'bookable');
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

    /**
     * Bookable contract methods
     */
    public function getBasePrice(): float
    {
        return (float) $this->base_price;
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

        return $price ?? $this->base_price;
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

    public function scopeLuxury($query)
    {
        return $query->where('base_price', '>=', 500); // arbitrary luxury threshold
    }

    public function scopeAffordable($query, float $maxPrice)
    {
        return $query->where('base_price', '<=', $maxPrice);
    }
}
