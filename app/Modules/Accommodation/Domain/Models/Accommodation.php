<?php

namespace App\Modules\Accommodation\Domain\Models;

use App\Modules\Accommodation\Domain\Enums\AccommodationStatus;
use App\Modules\Accommodation\Domain\Enums\VerificationStatus;
use App\Modules\Booking\Domain\Models\Booking;
use App\Modules\Destinations\Domain\Models\Destination;
use App\Modules\Media\Domain\Models\Image;
use App\Modules\Providers\Domain\Models\Provider;
use App\Modules\Reviews\Domain\Models\Review;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Accommodation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'destination_id',
        'provider_id',
        'bookable_type',
        'bookable_id',
        'status',
        'verification_status',
        'booking_mode',
        'name',
        'slug',
        'description',
        'address',
        'city',
        'country',
        'latitude',
        'longitude',
        'policies',
        'is_featured',
        'avg_price_per_night',
        'avg_rating',
        'reviews_count',
        'submitted_at',
        'verified_at',
        'published_at',
        'suspended_at',
        'suspension_reason',
        'reviewed_by',
    ];

    protected $casts = [
        'status' => AccommodationStatus::class,
        'verification_status' => VerificationStatus::class,
        'policies' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
        'is_featured' => 'boolean',
        'avg_price_per_night' => 'decimal:2',
        'avg_rating' => 'decimal:2',
        'reviews_count' => 'integer',
        'submitted_at' => 'datetime',
        'verified_at' => 'datetime',
        'published_at' => 'datetime',
        'suspended_at' => 'datetime',
    ];

    public function bookable()
    {
        return $this->morphTo();
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function verificationHistory(): HasMany
    {
        return $this->hasMany(AccommodationVerification::class)->latest();
    }

    public function scopeHotels($query)
    {
        return $query->where('bookable_type', (new Hotel)->getMorphClass());
    }

    public function scopeBedAndBreakfasts($query)
    {
        return $query->where('bookable_type', (new BedAndBreakfast)->getMorphClass());
    }

    public function scopeVillas($query)
    {
        return $query->where('bookable_type', (new Villa)->getMorphClass());
    }

    public function scopeByDestination($query, $destinationId)
    {
        return $query->when($destinationId, fn ($q) => $q->where('destination_id', $destinationId));
    }

    public function scopeByPriceRange($query, $min, $max)
    {
        return $query->when($min !== null, fn ($q) => $q->where('avg_price_per_night', '>=', $min))
            ->when($max !== null, fn ($q) => $q->where('avg_price_per_night', '<=', $max));
    }

    public function scopeByRating($query, $minRating)
    {
        return $query->when($minRating !== null, fn ($q) => $q->where('avg_rating', '>=', $minRating));
    }

    public function scopePublished($query)
    {
        return $query->where('status', AccommodationStatus::Published)
            ->where('verification_status', VerificationStatus::Approved)
            ->whereNull('suspended_at');
    }

    public function isPublished(): bool
    {
        return $this->status === AccommodationStatus::Published
            && $this->verification_status === VerificationStatus::Approved
            && $this->suspended_at === null;
    }
}
