<?php

namespace App\Modules\Packages\Domain\Models;

use App\Modules\Accommodation\Domain\Models\Concerns\HasScenes;

use App\Modules\Agents\Domain\Models\Agent;
use App\Modules\Availability\Domain\Models\Availability;
use App\Modules\Booking\Domain\Models\Booking;
use App\Modules\Catalog\Domain\Models\Offer;
use App\Modules\Destinations\Domain\Models\Destination;
use App\Modules\Media\Domain\Models\Image;
use App\Modules\Media\Domain\Models\Traits\HasImages;
use App\Modules\Pricing\Domain\Models\SeasonalRate;
use App\Modules\Reviews\Domain\Models\Review;
use App\Shared\Domain\Contracts\AvailabilityAware;
use App\Shared\Domain\Contracts\Bookable;
use App\Shared\Domain\Contracts\Pricable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Package extends Model implements AvailabilityAware, Bookable, Pricable
{
    use HasFactory, HasImages, HasScenes, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'short_description',
        'full_description',
        'destination_id',
        'agent_id',
        'base_price',
        'discount_price',
        'currency',
        'duration_days',
        'duration_nights',
        'inclusions',
        'exclusions',
        'itinerary',
        'cover_image',
        'gallery',
        'avg_rating',
        'reviews_count',
        'is_featured',
        'views',
        'active',
        'available_from',
        'available_to',
        'meta_data',
        'status',
        'submitted_at',
        'published_at',
        'rejection_reason',
        'setup_step',
        'cover_video',
        'markup_type',
        'markup_value',
        'min_guests',
        'max_guests',
    ];

    /** Lifecycle, like listings: built as a draft, reviewed, then live. */
    public const STATUSES = ['draft', 'pending_review', 'published', 'rejected', 'suspended'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'base_price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'available_from' => 'date',
        'available_to' => 'date',
        'inclusions' => 'array',
        'exclusions' => 'array',
        'itinerary' => 'array',
        'gallery' => 'array',
        'meta_data' => 'array',
        'is_featured' => 'boolean',
        'active' => 'boolean',
        'submitted_at' => 'datetime',
        'published_at' => 'datetime',
        'markup_value' => 'float',
        'min_guests' => 'integer',
        'max_guests' => 'integer',
    ];

    /** Relationships */

    /**
     * A package belongs to a travel agent.
     */
    /** The journey, day by day. */
    public function components(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PackageComponent::class)->orderBy('day')->orderBy('sequence');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published' && $this->active;
    }

    /** Live packages guests can see and book. */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')->where('active', true);
    }

    /**
     * Packages whose journey includes a listing. A hotel matches through any
     * of its room types; villas, B&Bs and experiences match directly.
     */
    public function scopeIncluding($query, Model $listing)
    {
        return $query->whereHas('components', function ($components) use ($listing) {
            if ($listing instanceof \App\Modules\Accommodation\Domain\Models\Hotel) {
                $components->where('componentable_type', 'room_type')
                    ->whereIn('componentable_id', \App\Modules\Accommodation\Domain\Models\RoomType::query()->where('hotel_id', $listing->getKey())->select('id'));
            } else {
                $components->where('componentable_type', $listing->getMorphClass())
                    ->where('componentable_id', $listing->getKey());
            }
        });
    }

    /** Stored path or URL of the cover photo, as a URL (null when none). */
    public function coverUrl(): ?string
    {
        return \App\Support\Media::url($this->cover_image);
    }

    /** The cover film as a URL, if the agent added one. */
    public function coverFilmUrl(): ?string
    {
        return \App\Support\Media::url($this->cover_video);
    }

    /** Destination ids beyond the main one ("Zanzibar and the Serengeti"). */
    public function extraDestinationIds(): array
    {
        return array_values(array_map('intval', (array) (($this->meta_data ?? [])['destinations'] ?? [])));
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * A package can have many polymorphic bookings.
     */
    public function bookings(): MorphMany
    {
        return $this->morphMany(Booking::class, 'bookable');
    }

    /**
     * A package can have many polymorphic reviews.
     */
    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    /**
     * A package can have many polymorphic images.
     */
    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    /**
     * A package can have many polymorphic features.
     */
    public function features(): MorphMany
    {
        return $this->morphMany(PackageFeature::class, 'featureable');
    }

    /**
     * A package can have many polymorphic offers.
     */
    public function offers(): MorphMany
    {
        return $this->morphMany(Offer::class, 'offerable');
    }

    /** Bookable Interface Methods */

    /**
     * {@inheritDoc}
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * {@inheritDoc}
     */
    public function getType(): string
    {
        return 'package';
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        return (string) ($this->short_description ?: $this->full_description);
    }

    /**
     * {@inheritDoc}
     */
    public function getImages(): array
    {
        return array_values(array_filter(array_merge([$this->cover_image], (array) ($this->gallery ?? []))));
    }

    /**
     * {@inheritDoc}
     */
    public function getBasePrice(): float
    {
        // The cached "from" price of a live quote; see PackagePricing.
        return (float) $this->base_price;
    }

    /**
     * {@inheritDoc}
     */
    public function getPriceForDate(string $date): float
    {
        // For packages, the price is generally fixed for the entire duration,
        // so we return the base price. Dynamic pricing would require a more complex check.
        return $this->getBasePrice();
    }

    /**
     * Check availability by ensuring package date window + booking conflicts.
     */
    public function isAvailable(string $checkIn, string $checkOut): bool
    {
        $startDate = Carbon::parse($checkIn);
        $endDate = Carbon::parse($checkOut);

        if ($startDate->lessThan($this->start_date) || $endDate->greaterThan($this->end_date)) {
            return false;
        }

        // Check overlapping bookings (respecting max guests)
        $overlapping = $this->bookings()
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('check_in', [$startDate, $endDate])
                    ->orWhereBetween('check_out', [$startDate, $endDate]);
            })
            ->count();

        return $overlapping < $this->max_guests;
    }

    /**
     * {@inheritDoc}
     */
    public function availabilities(): MorphMany
    {
        return $this->morphMany(Availability::class, 'bookable');
    }

    /**
     * {@inheritDoc}
     */
    public function getIncludedGuests(): int
    {
        return (int) ($this->max_guests ?: 2);
    }

    /**
     * {@inheritDoc}
     */
    public function getCurrency(): string
    {
        return $this->currency ?: 'USD';
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultMaxGuests(): int
    {
        return (int) ($this->max_guests ?: 2);
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function seasonalRates(): MorphMany
    {
        return $this->morphMany(SeasonalRate::class, 'seasonal_rateable');
    }

    /**
     * Calculate dynamic price based on date range, seasonal rates & guests.
     */
    public function calculateDynamicPrice(Carbon $checkIn, Carbon $checkOut, int $guests = 1): float
    {
        $days = $checkIn->diffInDays($checkOut);

        $total = 0;

        $date = $checkIn->copy();
        while ($date->lessThan($checkOut)) {
            $dayPrice = $this->getPriceForDate($date->toDateString());

            // Add guest surcharges
            if ($guests > $this->max_guests) {
                $extraGuests = $guests - $this->max_guests;
                $dayPrice += $extraGuests * ((float) $this->base_price * 0.1); // 10% surcharge per extra guest
            }

            $total += $dayPrice;
            $date->addDay();
        }

        return $total;
    }

    public function getMainImageAttribute(): ?string
    {
        if (! $this->cover_image) {
            return null;
        }

        // If already a full URL, return as-is
        return \App\Support\Media::url($this->cover_image);
    }

    public function getGalleryImagesAttribute(): array
    {
        $gallery = is_array($this->gallery)
            ? $this->gallery
            : json_decode($this->gallery ?? '[]', true);

        return collect($gallery)
            ->filter()
            ->map(fn ($img) => str_starts_with($img, 'http')
                    ? $img
                    : asset('storage/'.ltrim($img, '/'))
            )
            ->toArray();
    }

    public function getAllImagesAttribute(): array
    {
        $gallery = is_array($this->gallery)
            ? $this->gallery
            : json_decode($this->gallery ?? '[]', true);

        $all = array_merge([$this->cover_image], $gallery);

        return collect($all)
            ->filter()
            ->map(fn ($img) => str_starts_with($img, 'http')
                    ? $img
                    : asset('storage/'.ltrim($img, '/'))
            )
            ->toArray();
    }
}
