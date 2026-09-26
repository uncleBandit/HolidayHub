<?php

namespace App\Modules\Accommodation\Domain\Models;

use App\Modules\Availability\Domain\Models\Availability;
use App\Modules\Booking\Domain\Models\Booking;
use App\Modules\Catalog\Domain\Models\Amenity;
use App\Modules\Catalog\Domain\Models\Offer;
use App\Modules\Destinations\Domain\Models\Destination;
use App\Modules\Media\Domain\Models\Traits\HasImages;
use App\Modules\Packages\Domain\Models\PackageFeature;
use App\Modules\Pricing\Domain\Models\SeasonalRate;
use App\Modules\Providers\Domain\Models\Provider;
use App\Modules\Reviews\Domain\Models\Review;
use App\Modules\Wishlist\Domain\Models\Wishlist;
use App\Shared\Domain\Contracts\AvailabilityAware;
use App\Shared\Domain\Contracts\Bookable;
use App\Shared\Domain\Contracts\Pricable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BedAndBreakfast extends Model implements AvailabilityAware, Bookable, Pricable
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
        'price_per_night' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'has_breakfast' => 'boolean',
        'policies' => 'array',
        'gallery' => 'array',
        'seasonal_pricing' => 'array',
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

        return ! $hasOverlappingBooking;
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
     * {@inheritDoc}
     * Return all image URLs using the HasImages trait.
     */
    public function getImages(): array
    {
        return $this->galleryUrls; // This comes from the HasImages trait
    }

    protected static function booted()
    {
        static::creating(function ($bnb) {
            if (empty($bnb->slug)) {
                $slug = Str::slug($bnb->name);
                $originalSlug = $slug;
                $counter = 1;

                // ensure uniqueness
                while (static::where('slug', $slug)->exists()) {
                    $slug = "{$originalSlug}-{$counter}";
                    $counter++;
                }

                $bnb->slug = $slug;
            }
        });
    }

    public function getCoverImageUrlAttribute()
    {
        // Check if the cover_image is a full URL
        if (Str::startsWith($this->cover_image, ['http://', 'https://'])) {
            return $this->cover_image;
        }

        // Otherwise, assume it's a local storage path
        return $this->cover_image
            ? Storage::url($this->cover_image)
            : 'https://via.placeholder.com/1600x900';
    }

    public function getGalleryUrlsAttribute()
    {
        if (! $this->gallery || ! is_array($this->gallery)) {
            return [];
        }

        return array_map(function ($path) {
            // Check if the path is a full URL
            if (Str::startsWith($path, ['http://', 'https://'])) {
                return $path;
            }

            // Otherwise, assume it's a local storage path
            return Storage::url($path);
        }, $this->gallery);
    }
}
