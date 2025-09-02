<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Destination extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * Always define fillable for security + clarity.
     */
    protected $fillable = [
        'name',
        'slug',
        'country',
        'city',
        'description',
        'highlights',     // JSON (array of top attractions)
        'best_season',    // e.g., "June–August"
        'latitude',
        'longitude',
        'currency',
        'cover_image',    // hero image for marketing
        'gallery',        // JSON array of images
        'meta_data',      // JSON for SEO / flexible data
    ];

    /**
     * Casts for JSON fields & structured data.
     */
    protected $casts = [
        'highlights' => 'array',
        'gallery'    => 'array',
        'meta_data'  => 'array',
    ];

    /**
     * Relationships
     */

    // A destination can have many accommodations
   public function accommodations()
    {
    return $this->hasMany(Accommodation::class);
    }

    // A destination can have many activities/tours
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    // A destination can have many reviews (aggregated)
    public function reviews(): MorphMany
    {
    return $this->morphMany(Review::class, 'reviewable');
    }

    // A destination can belong to a region/parent destination (hierarchy)
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Destination::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Destination::class, 'parent_id');
    }

    /**
     * Example: Computed attributes
     */

    // Average rating from reviews
    public function getAverageRatingAttribute(): ?float
    {
        return $this->reviews()->avg('rating');
    }

    // Number of bookings tied to this destination
    public function getBookingCountAttribute(): int
    {
        return $this->activities()->withCount('bookings')->get()->sum('bookings_count')
            + $this->hotels()->withCount('bookings')->get()->sum('bookings_count');
    }
}
