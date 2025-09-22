<?php

namespace App\Models;

use App\Contracts\Bookable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;

class Activity extends Model implements Bookable
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'hotel_id',
        'provider_id',
        'destination_id',
        'name',
        'slug',
        'type',
        'description',
        'thumbnail',
        'gallery',
        'video_url',
        'meta_title',
        'meta_description',
        'tags',
        'base_price',
        'currency',
        'duration_minutes',
        'capacity',
        'min_age',
        'max_age',
        'is_featured',
        'is_active',
        'available_from',
        'available_to',
        'rating',
        'reviews_count',
        'bookings_count',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'gallery' => 'array',
        'tags' => 'array',
        'base_price' => 'decimal:2',
        'rating' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'available_from' => 'date',
        'available_to' => 'date',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        // Generate a unique slug before saving the model.
        static::creating(function (Activity $activity) {
            $activity->slug = Str::slug($activity->name);
        });
    }

    /*
     * Implementation of the Bookable interface.
     */
    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return 'activity';
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
        return $this->gallery ?? [];
    }

    public function getBasePrice(): float
    {
        return $this->base_price;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getCapacity(): int
    {
        return $this->capacity;
    }

    public function getPriceForDate(string $date): float
    {
        // Implement logic for dynamic pricing based on date.
        // For now, return the base price.
        return $this->base_price;
    }

    public function isAvailable(string $checkIn, string $checkOut): bool
    {
        // Implement availability logic based on dates and capacity.
        // For now, assume it's always available within the general range.
        return true;
    }

    /*
     * Relationships
     */
    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(Availability::class, 'bookable_id')
                    ->where('bookable_type', Activity::class);
    }


    public function offers(): MorphMany
    {
        return $this->morphMany(Offer::class, 'offerable');
    }

    public function getIncludedGuests(): int
    {
        // Assuming activities include 1 guest by default.
        return 1;
    }

    public function getDefaultMaxGuests(): int
    {
        return $this->capacity ?? 1;
    }

    public function seasonalRates(): MorphMany
    {
        return $this->morphMany(SeasonalRate::class, 'seasonal_rateable');
    }

     /**
     * Polymorphic images relationship.
     */
    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')->ordered();
    }

    /**
     * Optional: helper for primary image
     */
    public function primaryImage(): MorphMany
    {
        return $this->images()->primary();
    }

      public function amenities(): MorphToMany
    {
        return $this->morphToMany(Amenity::class, 'amenable');
    }
}
