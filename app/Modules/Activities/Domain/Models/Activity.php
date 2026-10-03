<?php

namespace App\Modules\Activities\Domain\Models;

use App\Modules\Activities\Domain\Enums\ActivityStatus;
use App\Modules\Activities\Domain\Enums\ActivityVerificationStatus;
use App\Modules\Availability\Domain\Models\Availability;
use App\Modules\Booking\Domain\Models\Booking;
use App\Modules\Catalog\Domain\Models\Amenity;
use App\Modules\Catalog\Domain\Models\Offer;
use App\Modules\Destinations\Domain\Models\Destination;
use App\Modules\Media\Domain\Models\Image;
use App\Modules\Media\Domain\Models\MediaPost;
use App\Modules\Pricing\Domain\Models\SeasonalRate;
use App\Modules\Providers\Domain\Models\Provider;
use App\Modules\Reviews\Domain\Models\Review;
use App\Shared\Domain\Contracts\AvailabilityAware;
use App\Shared\Domain\Contracts\Bookable;
use App\Shared\Domain\Contracts\Pricable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Activity extends Model implements AvailabilityAware, Bookable, Pricable
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'hotel_id',
        'provider_id',
        'destination_id',
        'category_id',
        'name',
        'slug',
        'type',
        'description',
        'short_description',
        'highlights',
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
        'age_policy',
        'attributes',
        'inclusions',
        'exclusions',
        'accessibility',
        'booking_mode',
        'booking_cutoff_minutes',
        'minimum_notice_minutes',
        'timezone',
        'weather_dependent',
        'weather_cancellation_policy',
        'safety_instructions',
        'included_participants',
        'is_featured',
        'available_from',
        'available_to',
    ];

    protected $casts = [
        'status' => ActivityStatus::class,
        'verification_status' => ActivityVerificationStatus::class,
        'gallery' => 'array',
        'tags' => 'array',
        'highlights' => 'array',
        'age_policy' => 'array',
        'attributes' => 'array',
        'inclusions' => 'array',
        'exclusions' => 'array',
        'accessibility' => 'array',
        'base_price' => 'decimal:2',
        'rating' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'weather_dependent' => 'boolean',
        'available_from' => 'date',
        'available_to' => 'date',
        'submitted_at' => 'datetime',
        'published_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Activity $activity): void {
            $baseSlug = Str::slug($activity->slug ?: $activity->name) ?: 'activity';
            $slug = $baseSlug;
            $suffix = 2;

            while (static::withTrashed()->where('slug', $slug)->exists()) {
                $slug = $baseSlug.'-'.$suffix++;
            }

            $activity->slug = $slug;
            $activity->status ??= ActivityStatus::Draft;
            $activity->verification_status ??= ActivityVerificationStatus::Unverified;
            $activity->is_active = false;
        });

        static::updating(function (Activity $activity): void {
            $listingFields = [
                'provider_id',
                'destination_id',
                'category_id',
                'name',
                'description',
                'short_description',
                'highlights',
                'thumbnail',
                'gallery',
                'video_url',
                'tags',
                'duration_minutes',
                'capacity',
                'base_price',
                'min_age',
                'max_age',
                'age_policy',
                'inclusions',
                'exclusions',
                'accessibility',
                'booking_mode',
                'booking_cutoff_minutes',
                'minimum_notice_minutes',
                'timezone',
                'weather_dependent',
                'weather_cancellation_policy',
                'safety_instructions',
            ];

            if ($activity->status === ActivityStatus::Published && $activity->isDirty($listingFields)) {
                $activity->status = ActivityStatus::Draft;
                $activity->verification_status = ActivityVerificationStatus::Pending;
                $activity->is_active = false;
                $activity->published_at = null;
            }
        });

        static::updated(function (Activity $activity): void {
            if ($activity->wasChanged('status')
                && $activity->status === ActivityStatus::Draft
                && $activity->getRawOriginal('status') === ActivityStatus::Published->value) {
                $activity->verificationHistory()->create([
                    'status' => ActivityVerificationStatus::Pending->value,
                    'notes' => 'Published activity content changed and requires review.',
                ]);
            }
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ActivityStatus::Published)
            ->where('verification_status', ActivityVerificationStatus::Approved)
            ->where('is_active', true);
    }

    public function isPublished(): bool
    {
        return $this->status === ActivityStatus::Published
            && $this->verification_status === ActivityVerificationStatus::Approved
            && $this->is_active;
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ActivityCategory::class, 'category_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(ActivityOption::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ActivitySchedule::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ActivitySession::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(ActivityLocation::class)->orderBy('sequence');
    }

    public function itinerary(): HasMany
    {
        return $this->hasMany(ActivityItineraryItem::class)->orderBy('sequence');
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(ActivityRequirement::class);
    }

    public function languages(): HasMany
    {
        return $this->hasMany(ActivityLanguage::class);
    }

    public function verificationHistory(): HasMany
    {
        return $this->hasMany(ActivityVerification::class)->latest();
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function bookings(): MorphMany
    {
        return $this->morphMany(Booking::class, 'bookable');
    }

    public function availabilities(): MorphMany
    {
        return $this->morphMany(Availability::class, 'bookable');
    }

    public function offers(): MorphMany
    {
        return $this->morphMany(Offer::class, 'offerable');
    }

    public function seasonalRates(): MorphMany
    {
        return $this->morphMany(SeasonalRate::class, 'seasonal_rateable');
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')->ordered();
    }

    public function mediaPosts(): MorphMany
    {
        return $this->morphMany(MediaPost::class, 'targetable');
    }

    public function amenities(): MorphToMany
    {
        return $this->morphToMany(Amenity::class, 'amenable');
    }

    public function getId(): int
    {
        return (int) $this->id;
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
        return (string) $this->description;
    }

    public function getImages(): array
    {
        $images = $this->images->pluck('path')->filter()->values()->all();

        return $images !== [] ? $images : array_values(array_filter(array_merge(
            [$this->thumbnail],
            $this->gallery ?? []
        )));
    }

    public function getBasePrice(): float
    {
        return (float) ($this->base_price ?? 0);
    }

    public function getCurrency(): string
    {
        return $this->currency ?: 'USD';
    }

    public function getCapacity(): int
    {
        return (int) ($this->capacity ?? 0);
    }

    public function getIncludedGuests(): int
    {
        return max(1, (int) $this->included_participants);
    }

    public function getDefaultMaxGuests(): int
    {
        return max(1, (int) ($this->capacity ?? 1));
    }

    public function getPriceForDate(string $date): float
    {
        $seasonalRate = $this->seasonalRates()
            ->where('active', true)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->orderByDesc('start_date')
            ->first();

        return (float) ($seasonalRate?->rate ?? $this->getBasePrice());
    }

    public function isAvailable(string $checkIn, string $checkOut): bool
    {
        if (! $this->isPublished()) {
            return false;
        }

        $start = Carbon::parse($checkIn);
        $end = Carbon::parse($checkOut);

        if ($end->lessThanOrEqualTo($start)) {
            return false;
        }

        return $this->sessions()
            ->where('status', 'scheduled')
            ->where('starts_at', '>=', $start)
            ->where('starts_at', '<', $end)
            ->where('starts_at', '>=', now()->addMinutes($this->minimum_notice_minutes))
            ->whereColumn('booked_capacity', '<', 'capacity')
            ->where(fn (Builder $query) => $query->whereNull('booking_cutoff_at')->orWhere('booking_cutoff_at', '>', now()))
            ->exists();
    }
}
