<?php

namespace App\Modules\Destinations\Domain\Models;

use App\Modules\Accommodation\Domain\Models\Accommodation;
use App\Modules\Accommodation\Domain\Models\BedAndBreakfast;
use App\Modules\Accommodation\Domain\Models\Hotel;
use App\Modules\Accommodation\Domain\Models\Villa;
use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Packages\Domain\Models\Package;
use App\Modules\Reviews\Domain\Models\Review;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
    /**
     * Casts for JSON fields & structured data.
     */
    protected $casts = [
        'highlights' => 'array',
        'gallery' => 'array',
        'meta_data' => 'array',
        'tags' => 'array',
        'average_cost' => 'double',
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

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
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

    // convenience accessors:
    public function hotels()
    {
        return $this->accommodations()->where('bookable_type', Hotel::class);
    }

    public function bedAndBreakfasts()
    {
        return $this->accommodations()->where('bookable_type', BedAndBreakfast::class);
    }

    public function villas()
    {
        return $this->accommodations()->where('bookable_type', Villa::class);
    }

    public function bookings()
    {
        return $this->hasMany(\App\Modules\Booking\Domain\Models\Booking::class);
    }

    public function experiences()
    {
        return $this->hasMany(\App\Modules\Activities\Domain\Models\Experience::class);

    }
}
