<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Provider extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',               // Linked to main users table (login, auth, roles)
        'company_name',          // Legal / Brand name
        'contact_person_name',   // Main contact
        'contact_email',
        'contact_phone',
        'business_type',         // e.g., 'hotel', 'bnb', 'tour_operator', 'car_rental'
        'registration_number',   // For verified businesses
        'tax_id',                // Optional for billing
        'logo',                  // Brand/logo image
        'description',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'website_url',
        'social_links',          // JSON for Facebook, Instagram, etc.
        'status',                // pending, active, suspended
        'verified_at',           // KYC/Business verification date
    ];

    /**
     * Casts for specific fields.
     */
    protected $casts = [
        'social_links' => 'array',
        'verified_at' => 'datetime',
    ];

    /**
     * Relationships
     */

    // Each provider belongs to a system user account
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // A provider can own many hotels, bnbs, resorts, etc.
    public function hotels()
    {
        return $this->hasMany(Hotel::class);
    }

    // A provider can also list activities (tours, excursions)
    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    // A provider can manage rooms directly (if BnB style)
    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    // Provider reviews/testimonials
    public function testimonials()
    {
        return $this->morphMany(Review::class, 'testimonialable');
    }

    // Track payouts/earnings
    public function payouts()
    {
        return $this->hasMany(Payout::class);
    }

    // Offers relationship
    public function offers()
    {
        return $this->hasMany(Offer::class);
    }

    public function services()
    {
    return $this->hasMany(Service::class);
     }

     public function accommodations(){
        return $this->hasMany(Accommodation::class);
     }

}
