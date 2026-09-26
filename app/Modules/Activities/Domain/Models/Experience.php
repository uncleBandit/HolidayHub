<?php

namespace App\Modules\Activities\Domain\Models;

use App\Modules\Availability\Domain\Models\Availability;
use App\Modules\Booking\Domain\Models\Booking;
use App\Modules\Catalog\Domain\Models\Offer;
use App\Modules\Pricing\Domain\Models\SeasonalRate;
use App\Modules\Providers\Domain\Models\Provider;
use App\Modules\Reviews\Domain\Models\Review;
use App\Shared\Domain\Contracts\AvailabilityAware;
use App\Shared\Domain\Contracts\Bookable;
use App\Shared\Domain\Contracts\Pricable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Experience extends Model implements AvailabilityAware, Bookable, Pricable
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

    public function availabilities(): MorphMany
    {
        return $this->morphMany(Availability::class, 'bookable');
    }

    public function seasonalRates(): MorphMany
    {
        return $this->morphMany(SeasonalRate::class, 'seasonal_rateable');
    }

    public function offers(): MorphMany
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
