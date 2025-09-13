<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Agent extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',            // Links to main User table for authentication
        'agency_name',        // Business/Agency name
        'license_number',     // Government/industry license/registration
        'tax_id',             // Optional for invoicing
        'phone',
        'email',
        'address',
        'website',
        'logo_path',          // Profile/logo image
        'commission_rate',    // % commission agreed upon
        'verification_status',// pending, verified, rejected
        'bio',                // About agent/agency
        'rating',             // Average client rating
        'total_bookings',     // Total bookings handled
        'response_time',      // Avg response time in minutes
        'preferred_currency', // For payouts and commissions
        'status',             // active, suspended, archived
    ];

    /********************
     * Relationships
     *******************/

    /**
     * Link to main User record (for login & permissions).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Hotels managed by the agent.
     */
    public function hotels(): HasMany
    {
        return $this->hasMany(Hotel::class);
    }

    /**
     * Activities/tours managed by the agent.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * Bookings managed by this agent.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Testimonials from guests.
     */
    public function testimonials(): MorphMany
    {
        return $this->morphMany(Review::class, 'testimonialable');
    }
    /**
     * Packages offered by this agent.
     */
    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }
}
