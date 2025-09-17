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

class BedAndBreakfast extends Model implements Bookable
{
    use HasFactory;

    protected $table = 'bed_and_breakfasts';

    /**
     * Mass assignable attributes.
     *
     * @var array<string>
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
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'gallery' => 'array',
        'amenities' => 'array',
        'price_per_night' => 'decimal:2',
    ];

    /** Relationships */

    /**
     * A Bed & Breakfast belongs to a single destination.
     *
     * @return BelongsTo
     */
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

    /**
     * A Bed & Breakfast can have many bookings.
     *
     * @return HasMany
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * A Bed & Breakfast can have many reviews.
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
     * Accessor for the average rating based on reviews.
     *
     * @return float
     */
    public function getAverageRatingAttribute(): float
    {
        return round($this->reviews()->avg('rating') ?? 0, 1);
    }

    /**
     * Accessor for the main image from the gallery.
     *
     * @return string|null
     */
    public function getMainImageAttribute(): ?string
    {
        return $this->gallery[0] ?? null;
    }

    /**
     * Get the provider that owns the Bed & Breakfast.
     *
     * @return BelongsTo
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
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
        return 'bed_and_breakfast';
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
        return (float) $this->price_per_night;
    }

    /**
     * @inheritDoc
     */
    public function getPriceForDate(string $date): float
    {
        $price = $this->availabilities()
            ->where('date', $date)
            ->value('price');

        return $price ?? $this->price_per_night;
    }

    /**
     * Check if the Bed & Breakfast is available for a given date range.
     *
     * @inheritDoc
     */
    public function isAvailable(string $checkIn, string $checkOut): bool
    {
        $hasOverlappingBooking = $this->bookings()
            ->where(function ($query) use ($checkIn, $checkOut) {
                // A booking overlaps if its check-in is before the new checkout AND its check-out is after the new check-in.
                $query->where('check_in', '<', $checkOut)
                    ->where('check_out', '>', $checkIn);
            })
            ->exists();

        return !$hasOverlappingBooking;
    }

    /**
     * Get the associated availabilities.
     *
     * @return MorphMany
     */
    public function availabilities(): MorphMany
    {
        return $this->morphMany(Availability::class, 'bookable');
    }

    /**
     * Get the number of guests included in the base price.
     *
     * @inheritDoc
     */
    public function getIncludedGuests(): int
    {
        return $this->max_guests;
    }

    public function offers(): MorphMany
    {
        return $this->morphMany(Offer::class, 'offerable');
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

    /**
     * A bed and breakfast can have many polymorphic features.
     *
     * @return MorphMany
     */
    public function features(): MorphMany
    {
        return $this->morphMany(PackageFeature::class, 'featureable');
    }


     public function seasonalRates(): MorphMany
    {
        return $this->morphMany(SeasonalRate::class, 'seasonal_rateable');
    }


}
