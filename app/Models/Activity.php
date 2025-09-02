<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Activity extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Mass assignable fields for clarity & security.
     */
    protected $fillable = [
        'destination_id',
        'title',
        'slug',
        'description',
        'category',        // e.g. adventure, culture, relaxation
        'highlights',      // JSON (array of features)
        'duration',        // in hours or "Half-day", "Full-day"
        'start_time',      // nullable if flexible
        'end_time',
        'price',
        'currency',
        'max_group_size',
        'availability',    // JSON e.g. ["Mon", "Wed", "Fri"]
        'cover_image',
        'gallery',         // JSON array of image URLs
        'meta_data',       // flexible SEO or extra attributes
    ];

    /**
     * Cast JSON fields.
     */
    protected $casts = [
        'highlights'   => 'array',
        'gallery'      => 'array',
        'availability' => 'array',
        'meta_data'    => 'array',
    ];

    /**
     * Relationships
     */

    // Belongs to a destination
    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    // An activity can have many reviews
    public function reviews(): MorphMany
    {
    return $this->morphMany(Review::class, 'reviewable');
    }

    // An activity can have many bookings
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Computed / accessors
     */

    // Average rating from reviews
    public function getAverageRatingAttribute(): ?float
    {
        return $this->reviews()->avg('rating');
    }

    // Check if activity is available on a given day
    public function isAvailableOn(string $day): bool
    {
        return in_array($day, $this->availability ?? [], true);
    }

    // Check if spots are available for booking
    public function hasCapacity(int $people): bool
    {
        $booked = $this->bookings()->whereDate('date', today())->sum('people_count');
        return $booked + $people <= $this->max_group_size;
    }
}
