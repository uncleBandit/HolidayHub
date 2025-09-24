<?php

namespace App\Models;

use App\Contracts\Bookable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Storage;


class Villa extends Model implements Bookable
{
    use HasFactory;

    protected $table = 'villas';

    /**
     * Mass assignable attributes.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name', 'slug', 'description', 'avg_price_per_night', 'max_guests', 'bedrooms', 'bathrooms',
        'address', 'city', 'country', 'latitude', 'longitude', 'gallery', 'policies',
        'amenities', 'destination_id', 'provider_id', 'is_active', 'is_verified', 'is_featured', 'has_private_pool',
    ];

    protected $casts = [
        'gallery' => 'array',
        'policies' => 'array',
        'avg_price_per_night' => 'float',
        'latitude' => 'float',
        'longitude' => 'float',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'is_featured' => 'boolean',
        'has_private_pool' => 'boolean',
        'meta_data' => 'array',
    ];

    /** Relationships */




    /**
     * A Villa can have many reviews.
     *
     * @return MorphMany
     */
    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    /**
     * Polymorphic link to the accommodation table.
     *
     * @return MorphOne
     */
    public function accommodation(): MorphOne
    {
        return $this->morphOne(Accommodation::class, 'bookable');
    }

    /**
     * Polymorphic many-to-many relationship to Amenity.
     *
     * @return MorphToMany
     */
    public function amenities(): MorphToMany
    {
        return $this->morphToMany(
            Amenity::class,
            'amenable'
        );
    }

    /**
     * Get the provider that owns the Villa.
     *
     * @return BelongsTo
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    /** Accessors */

    /**
     * Get the main image from the gallery.
     *
     * @return string|null
     */
    public function getMainImageAttribute(): ?string
    {
        return $this->gallery[0] ?? null;
    }

    /**
     * Get the average rating for the Villa.
     *
     * @return float
     */
    public function getAverageRatingAttribute(): float
    {
        return round($this->reviews()->avg('rating') ?? 0, 1);
    }

    /** Bookable Interface Methods */

    /**
     * @inheritDoc
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return 'villa';
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @inheritDoc
     */
    public function getImages(): array
    {
        return $this->gallery ?? [];
    }

    /**
     * @inheritDoc
     */
    public function getBasePrice(): float
    {
        return (float) $this->base_price;
    }

    public function offers(): MorphMany
    {
        return $this->morphMany(Offer::class, 'offerable');
    }

    /**
     * @inheritDoc
     */
    public function getPriceForDate(string $date): float
    {
        // For a more advanced system, you would check for special pricing
        // or promotions on this specific date. For now, we'll return the base price.
        return (float) $this->base_price;
    }

    /**
     * Check if the Villa is available for a given date range.
     *
     * @inheritDoc
     */
    public function isAvailable(string $checkIn, string $checkOut): bool
    {
        $hasOverlappingBooking = $this->bookings()
            ->where(function ($query) use ($checkIn, $checkOut) {
                // A booking overlaps if its check-in is before the new checkout
                // AND its check-out is after the new check-in.
                $query->where('check_in', '<', $checkOut)
                    ->where('check_out', '>', $checkIn);
            })
            ->exists();

        return !$hasOverlappingBooking;
    }

    /**
     * Get the associated availabilities.
     *
     * @return HasMany
     */
    /**
     * Get the associated availabilities.
     *
     * @return MorphMany
     */
    public function availabilities(): MorphMany
    {
        return $this->morphMany(Availability::class, 'bookable');
    }

    public function getIncludedGuests(): int
    {
        return $this->max_guests;
    }


    /**
     * Get the currency for the bookable item.
     *
     * @inheritDoc
     */
    public function getCurrency(): string
    {
        return 'USD';
    }

    /**
     * Get the default number of guests for this bookable item.
     * This method is required by the Bookable interface.
     */
    public function getDefaultMaxGuests(): int
    {
        return 2;
    }


    public function seasonalRates(): MorphMany
    {
        return $this->morphMany(SeasonalRate::class, 'seasonal_rateable');
    }

    public function destination(): HasOneThrough
    {
        return $this->hasOneThrough(
            Destination::class,
            Accommodation::class,
            'bookable_id',      // Foreign key on accommodations table
            'id',               // Foreign key on destinations table
            'id',               // Local key on hotels table
            'destination_id'    // Local key on accommodations table
        )->where('bookable_type', self::class);
    }

    public function bookings(): MorphMany
    {
        return $this->morphMany(Booking::class, 'bookable');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

     public function getMainImageUrlAttribute()
    {
        return $this->main_image
            ? Storage::url($this->main_image)
            : 'https://via.placeholder.com/1600x900';
    }

    public function getGalleryUrlsAttribute()
    {
        return collect($this->gallery ?? [])
            ->map(fn ($path) => Storage::url($path))
            ->toArray();
    }

    public function wishlists()
    {
        return $this->morphMany(Wishlist::class, 'wishlistable');
    }


}
