<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Accommodation extends Model
{
    use HasFactory,SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'destination_id',
        'provider_id',
        'bookable_type',
        'bookable_id',
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

    
}
