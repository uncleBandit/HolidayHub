<?php

namespace App\Modules\Accommodation\Domain\Models;

use App\Modules\Booking\Domain\Models\Booking;
use App\Modules\Destinations\Domain\Models\Destination;
use App\Modules\Providers\Domain\Models\Provider;
use App\Modules\Reviews\Domain\Models\Review;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Accommodation extends Model
{
    use HasFactory,SoftDeletes;

    protected $fillable = [
        'destination_id',
        'provider_id',
        'bookable_type',
        'bookable_id',
        'is_featured',
        'avg_price_per_night',
        'avg_rating',
        'reviews_count',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'avg_price_per_night' => 'decimal:2',
        'avg_rating' => 'decimal:2',
        'reviews_count' => 'integer',
    ];

    /**
     * Get the specific accommodation type (Hotel, BnB, Villa, etc.)
     */
    public function bookable()
    {
        return $this->morphTo();
    }

    /**
     * An Accommodation belongs to a Provider.
     */
    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    /**
     * An Accommodation belongs to a Destination.
     */
    public function destination()
    {
        return $this->belongsTo(Destination::class);
    }

    /**
     * An Accommodation can have many Bookings.
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * An Accommodation can have many Reviews (polymorphic).
     */
    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    /**
     * Scope: Filter by type (e.g., hotels only).
     */
    public function scopeHotels($query)
    {
        return $query->where('bookable_type', Hotel::class);
    }

    public function scopeBedAndBreakfasts($query)
    {
        return $query->where('bookable_type', BedAndBreakfast::class);
    }

    public function scopeVillas($query)
    {
        return $query->where('bookable_type', Villa::class);
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
}
