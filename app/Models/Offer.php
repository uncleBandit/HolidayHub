<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Offer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * Allows safe assignment for creating/updating offers.
     */
     protected $fillable = [
        'title',
        'slug',
        'description',
        'main_image', // Corrected to match migration
        'gallery_images', // Added to fillable
        'price',
        'discount_percent',
        'start_date',
        'end_date',
        'is_featured',
        'destination_id',
        'provider_id', // Added to fillable
        'active', // Added to fillable
        'max_capacity', // Added to fillable
        'rating', // Added to fillable
        'tags', // Added to fillable
    ];

    /**
     * The attributes that should be cast.
     * Ensures correct data types.
     */
    protected $casts = [
        'price' => 'decimal:2',
        'discount_percent' => 'integer',
        'is_featured' => 'boolean',
        'active' => 'boolean', // Added to casts
        'tags' => 'array', // Casting to an array for JSON column
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'rating' => 'decimal:2',
        'gallery_images' => 'array', // Casting to an array for JSON column
    ];

    /**
     * Automatically generate slug from title if not set.
     */
    protected static function booted()
    {
        static::creating(function ($offer) {
            if (empty($offer->slug)) {
                $offer->slug = Str::slug($offer->title);
            }
        });
    }

    /**
     * Relationships
     */

    // Offer belongs to a destination (optional)
    public function destination()
    {
        return $this->belongsTo(Destination::class);
    }

    public function bookable()
    {
    return $this->morphTo();
    }

    /**
     * Scopes
     */

    // Only currently active offers
    public function scopeActive($query)
    {
        return $query->where('start_date', '<=', now())
                     ->where('end_date', '>=', now());
    }

    // Only featured offers
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Accessors / Helper Methods
     */

    // Calculate discounted price if discount_percent is set
    protected function discountedPrice(): Attribute
    {
        return Attribute::get(function () {
            if ($this->discount_percent > 0) {
                return round($this->price * (1 - $this->discount_percent / 100), 2);
            }
            return $this->price;
        });
    }

    // Human-readable date range
    protected function dateRange(): Attribute
    {
        return Attribute::get(fn () => $this->start_date && $this->end_date
            ? $this->start_date->format('M d, Y') . ' - ' . $this->end_date->format('M d, Y')
            : null
        );
    }

    /**
     * One Offer can have many bookings
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
