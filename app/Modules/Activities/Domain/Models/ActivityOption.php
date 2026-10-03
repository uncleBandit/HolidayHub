<?php

namespace App\Modules\Activities\Domain\Models;

use App\Modules\Availability\Domain\Models\Availability;
use App\Modules\Booking\Domain\Models\Booking;
use App\Modules\Catalog\Domain\Models\Offer;
use App\Modules\Pricing\Domain\Models\SeasonalRate;
use App\Shared\Domain\Contracts\AvailabilityAware;
use App\Shared\Domain\Contracts\Bookable;
use App\Shared\Domain\Contracts\Pricable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class ActivityOption extends Model implements AvailabilityAware, Bookable, Pricable
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'duration_minutes',
        'base_price',
        'currency',
        'included_participants',
        'max_participants',
        'booking_mode',
        'is_active',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'base_price' => 'float',
        'included_participants' => 'integer',
        'max_participants' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $option): void {
            $base = Str::slug($option->slug ?: $option->name) ?: 'option';
            $slug = $base;
            $suffix = 2;

            while (static::query()->where('activity_id', $option->activity_id)->where('slug', $slug)->exists()) {
                $slug = $base.'-'.$suffix++;
            }

            $option->slug = $slug;
        });
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ActivitySession::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ActivitySchedule::class);
    }

    public function bookings(): MorphMany
    {
        return $this->morphMany(Booking::class, 'bookable');
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

    public function getId(): int
    {
        return (int) $this->id;
    }

    public function getType(): string
    {
        return 'activity-option';
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
        return $this->activity?->getImages() ?? [];
    }

    public function getBasePrice(): float
    {
        return (float) $this->base_price;
    }

    public function getIncludedGuests(): int
    {
        return max(1, $this->included_participants);
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getDefaultMaxGuests(): int
    {
        return max(1, $this->max_participants ?? $this->activity?->getDefaultMaxGuests() ?? 1);
    }

    public function getPriceForDate(string $date): float
    {
        $seasonalRate = $this->seasonalRates()
            ->where('active', true)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->orderByDesc('start_date')
            ->first();

        return (float) ($seasonalRate?->rate ?? $this->base_price);
    }

    public function isAvailable(string $checkIn, string $checkOut): bool
    {
        $start = Carbon::parse($checkIn);
        $end = Carbon::parse($checkOut);
        $activity = $this->activity;

        return $this->is_active
            && $activity?->isPublished() === true
            && $end->greaterThan($start)
            && $this->sessions()
                ->where('status', 'scheduled')
                ->where('starts_at', '>=', $start)
                ->where('starts_at', '<', $end)
                ->where('starts_at', '>=', now()->addMinutes($activity?->minimum_notice_minutes ?? 0))
                ->whereColumn('booked_capacity', '<', 'capacity')
                ->where(fn ($query) => $query->whereNull('booking_cutoff_at')->orWhere('booking_cutoff_at', '>', now()))
                ->exists();
    }
}
