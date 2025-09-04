<?php

namespace App\Models;

use App\Contracts\Interface\Bookable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model implements Bookable
{
    use HasFactory;

    protected $fillable = [
        'hotel_id',
        'name',               // e.g. "Deluxe Suite"
        'description',
        'capacity',           // max number of guests
        'beds',               // number of beds
        'base_price',         // default price per night
        'currency',           // e.g. "USD", "KES"
        'amenities',          // JSON: aircon, balcony, minibar
        'images',             // JSON or relation
        'status',             // available, under_maintenance
    ];

    protected $casts = [
        'amenities' => 'array',
        'images'    => 'array',
        'base_price'=> 'float',
    ];

    /** Relationships */
    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * @inheritDoc
     */
    public function getBasePrice(): float
    {
        return $this->base_price;
    }

    public function prices()
    {
        return $this->hasMany(RoomPrice::class);
    }

    /** Check availability for given dates */
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

    /** Dynamic price calculation (seasonal / weekend logic) */
    public function getPriceForDate(string $date): float
    {
        $price = $this->prices()
            ->where('date', $date)
            ->value('price');

        return $price ?? $this->base_price;
    }

        public function availabilities(): HasMany
    {
        return $this->hasMany(RoomAvailability::class);
    }
}
