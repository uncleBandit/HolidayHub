<?php

namespace App\Modules\Accommodation\Domain\Models;

use App\Modules\Availability\Domain\Models\Availability;
use App\Modules\Booking\Domain\Models\Booking;
use App\Modules\Catalog\Domain\Models\Amenity;
use App\Modules\Catalog\Domain\Models\Offer;
use App\Modules\Destinations\Domain\Models\Destination;
use App\Modules\Identity\Domain\Models\Guest;
use App\Modules\Media\Domain\Models\Image;
use App\Modules\Media\Domain\Models\Traits\HasImages;
use App\Modules\Pricing\Domain\Models\SeasonalRate;
use App\Modules\Providers\Domain\Models\Provider;
use App\Modules\Reviews\Domain\Models\Review;
use App\Modules\Wishlist\Domain\Models\Wishlist;
use App\Shared\Domain\Contracts\AvailabilityAware;
use App\Shared\Domain\Contracts\Bookable;
use App\Shared\Domain\Contracts\HasUnitCapacity;
use App\Shared\Domain\Contracts\Pricable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Hotel extends Model implements AvailabilityAware, Bookable, HasUnitCapacity, Pricable
{
    use HasFactory, HasImages, SoftDeletes;

    protected $fillable = [
        'provider_id',
        'name',
        'slug',
        'description',
        'address',
        'city',
        'country',
        'latitude',
        'longitude',
        'stars',
        'is_featured',
        'is_active',
        'is_verified',
        'policies',
        'cover_image',
        'gallery',
        'avg_price_per_night',
        'avg_rating',
        'reviews_count',
    ];

    protected $casts = [
        'policies' => 'array',
        'gallery' => 'array',
        'avg_rating' => 'float',
        'avg_price_per_night' => 'float',
        'reviews_count' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
    ];

    /**
     * Number of separately bookable units, for occupancy-based pricing.
     *
     * Always at least one: a property with no rows is still bookable as a
     * whole, and returning 0 would make demand pricing divide by zero.
     */
    public function unitCapacity(): int
    {
        return max(1, $this->rooms()->count());
    }

    /** Relationships */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function roomTypes(): HasMany
    {
        return $this->hasMany(RoomType::class);
    }

    public function bookings(): MorphMany
    {
        return $this->morphMany(Booking::class, 'bookable');
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function manager()
    {
        return $this->belongsTo(Provider::class, 'manager_id');
    }

    public function amenities(): MorphToMany
    {
        return $this->morphToMany(
            Amenity::class,
            'amenable',
            'amenables',
            'amenable_id',
            'amenity_id'
        );
    }

    public function wishlistedByUsers()
    {
        return $this->belongsToMany(Guest::class, 'hotel_user_wishlist')
            ->withTimestamps()
            ->withPivot('added_at');
    }

    /**
     * Get the provider that owns the hotel.
     */
    public function provider()
    {
        return $this->belongsTo(Provider::class);
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

    public function offers(): MorphMany
    {
        return $this->morphMany(Offer::class, 'offerable');
    }

    /**
     * Get the currency for the bookable item.
     *
     * {@inheritDoc}
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

    /** Bookable Interface Methods */
    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return 'hotel';
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getImages(): array
    {
        return $this->images->pluck('path')->toArray();
    }

    public function getBasePrice(): float
    {
        // Get the minimum price of all room types associated with this hotel.
        // This makes sense as a hotel's "base price" is often its cheapest room.
        return (float) $this->roomtypes()->min('price_per_night');
    }

    public function getPriceForDate(string $date): float
    {
        // For a more advanced system, you would check for special pricing
        // or promotions on this specific date. For now, we'll return the base price.
        return (float) $this->roomTypes()->min('price_per_night');
    }

    /**
     * Checks if the hotel has at least one available room for the given date range.
     */
    public function isAvailable(string $checkIn, string $checkOut): bool
    {
        // Check each room type for availability. The logic is delegated to the RoomType model.
        foreach ($this->roomTypes as $roomType) {
            if ($roomType->isAvailable($checkIn, $checkOut)) {
                return true; // A single available room type means the hotel is available.
            }
        }

        return false;
    }

    /**
     * Get the associated availabilities.
     */
    public function availabilities(): MorphMany
    {
        return $this->morphMany(Availability::class, 'bookable');
    }

    public function seasonalRates(): MorphMany
    {
        return $this->morphMany(SeasonalRate::class, 'seasonal_rateable');
    }

    public function getIncludedGuests(): int
    {
        return $this->max_guests ?? 2;
    }

    public function accommodation(): MorphOne
    {
        return $this->morphOne(Accommodation::class, 'bookable');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getAllImagesAttribute(): array
    {
        $gallery = is_array($this->gallery)
            ? $this->gallery
            : json_decode($this->gallery ?? '[]', true);

        $all = array_merge([$this->cover_image], $gallery);

        return collect($all)
            ->filter()
            ->map(fn ($img) => $img
                ? asset('storage/'.ltrim($img, '/')) // ✅ prepend only once
                : null
            )
            ->toArray();
    }

    public function wishlists()
    {
        return $this->morphMany(Wishlist::class, 'wishlistable');
    }
}
