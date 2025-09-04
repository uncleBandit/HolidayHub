<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'title',
        'slug',
        'description',
        'destination',
        'price',
        'currency',
        'duration_days',
        'start_date',
        'end_date',
        'max_guests',
        'agent_id',     // the travel agent offering it
        'provider_id',  // hotel/resort/tour operator
        'image_url',    // main cover image
        'status',       // draft / published / archived
    ];

    /**
     * Relationships
     */

    // A package belongs to an agent (travel agency staff)
    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    // A package belongs to a provider (hotel/resort/tour operator)
    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    // A package can have many bookings
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    // A package can have many reviews
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // A package can have many images (gallery)
    public function images()
    {
        return $this->morphMany(Image::class, 'mediable');
    }

    // A package can have multiple features (meals, activities, inclusions)
    public function features()
    {
        return $this->hasMany(PackageFeature::class);
    }

    public function destination()
    {
        return $this->belongsTo(Destination::class);
    }
}
