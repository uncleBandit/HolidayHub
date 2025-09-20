<?php

namespace App\Models;

use App\Contracts\Bookable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use App\Models\Traits\HasImages;

class BedAndBreakfast extends Model implements Bookable
{
    use HasFactory, HasImages;

    protected $table = 'bed_and_breakfasts';

    protected $fillable = [
        'provider_id',
        'name',
        'slug',
        'description',
        'rooms',
        'has_breakfast',
        'address',
        'city',
        'country',
        'latitude',
        'longitude',
        'is_featured',
        'policies',
        'is_active',
        'is_verified',
        'cover_image',
        'gallery',
        'price_per_night',
        'max_guests',
        'seasonal_pricing',
    ];

    protected $casts = [
        'price_per_night'   => 'decimal:2',
        'is_featured'       => 'boolean',
        'is_active'         => 'boolean',
        'is_verified'       => 'boolean',
        'has_breakfast'     => 'boolean',
        'policies'          => 'array',
        'gallery'           => 'array',
        'seasonal_pricing'  => 'array',
    ];

    /** Relationships */

    public function destination(): HasOneThrough
    {
        return $this->hasOneThrough(
            Destination::class,
            Accommodation::class,
            'bookable_id',
            'id',
            'id',
            'destination_id'
        )->where('bookable_type', self::class);
    }

    public function bookings(): MorphMany
    {
        return $this->morphMany(Booking::class, 'bookable');
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function accommodation(): MorphOne
    {
        return $this->morphOne(Accommodation::class, 'bookable');
    }

    public function amenities(): MorphToMany
    {
        return $this->morphToMany(Amenity::class, 'amenable');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function availabilities(): MorphMany
    {
        return $this->morphMany(Availability::class, 'bookable');
    }

    public function offers(): MorphMany
    {
        return $this->morphMany(Offer::class, 'offerable');
    }

    public function features(): MorphMany
    {
        return $this->morphMany(PackageFeature::class, 'featureable');
    }

    public function seasonalRates(): MorphMany
    {
        return $this->morphMany(SeasonalRate::class, 'seasonal_rateable');
    }

    public function wishlists(): MorphMany
    {
        return $this->morphMany(Wishlist::class, 'wishlistable');
    }

    public function isWishlistedBy($guest): bool
    {
        return $this->wishlists()->where('user_id', $guest->user_id)->exists();
    }

    /** Scopes */

    public function scopeBookable($query)
    {
        return $query->where('is_active', true)
                     ->where('is_verified', true)
                     ->whereNull('deleted_at');
    }

    /** Accessors */

    public function getAverageRatingAttribute(): float
    {
        return round($this->reviews()->avg('rating') ?? 0, 1);
    }

    /** Bookable Interface Implementation */

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return 'bed_and_breakfast';
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getBasePrice(): float
    {
        return (float) $this->price_per_night;
    }

    public function getPriceForDate(string $date): float
    {
        return $this->availabilities()->where('date', $date)->value('price')
               ?? $this->price_per_night;
    }

    public function isAvailable(string $checkIn, string $checkOut): bool
    {
        $hasOverlappingBooking = $this->bookings()
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->where('check_in', '<', $checkOut)
                      ->where('check_out', '>', $checkIn);
            })
            ->exists();

        return !$hasOverlappingBooking;
    }

    public function getIncludedGuests(): int
    {
        return $this->max_guests;
    }

    public function getCurrency(): string
    {
        return 'USD';
    }

    public function getDefaultMaxGuests(): int
    {
        return 2;
    }

    /** Route key */

    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * @inheritDoc
     * Return all image URLs using the HasImages trait.
     */
    public function getImages(): array
    {
        return $this->galleryUrls; // This comes from the HasImages trait
    }

}
