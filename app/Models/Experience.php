<?php

namespace App\Models;

use App\Contracts\Bookable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class Experience extends Model implements Bookable
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'city',
        'location',
        'category',
        'duration',
        'price',
        'capacity',
        'cover_image',
        'featured',
        'currency',
        'included_guests',
        'max_guests',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'featured' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function bookings()
    {
        return $this->morphMany(Booking::class, 'bookable');
    }

    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function availabilities(): Relation
    {
        return $this->hasMany(Availability::class);
    }

    public function seasonalRates(): Relation
    {
        return $this->hasMany(SeasonalRate::class);
    }

    public function offers(): Relation
    {
        return $this->morphMany(Offer::class, 'offerable');
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers & Computed
    |--------------------------------------------------------------------------
    */

    public function averageRating(): ?float
    {
        return $this->reviews()->avg('rating');
    }

    /*
    |--------------------------------------------------------------------------
    | Bookable Contract Implementation
    |--------------------------------------------------------------------------
    */

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return 'experience';
    }

    public function getName(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getImages(): array
    {
        return [$this->cover_image];
    }

    public function getBasePrice(): float
    {
        return (float) $this->price;
    }

    public function getPriceForDate(string $date): float
    {
        // Seasonal override
        $seasonalRate = $this->seasonalRates()
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();

        if ($seasonalRate) {
            return (float) $seasonalRate->price;
        }

        // Default base price
        return (float) $this->price;
    }

    public function isAvailable(string $checkIn, string $checkOut): bool
    {
        return $this->availabilities()
            ->whereDate('date', '>=', $checkIn)
            ->whereDate('date', '<=', $checkOut)
            ->where('available_slots', '>', 0)
            ->exists();
    }

    public function getIncludedGuests(): int
    {
        return $this->included_guests ?? 1;
    }

    public function getCurrency(): string
    {
        return $this->currency ?? 'USD';
    }

    public function getDefaultMaxGuests(): int
    {
        return $this->max_guests ?? $this->capacity;
    }
}
