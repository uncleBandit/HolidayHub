<?php

namespace App\Modules\Identity\Domain\Models;

use App\Modules\Accommodation\Domain\Models\Hotel;
use App\Modules\Booking\Domain\Models\Booking;
use App\Modules\Reviews\Domain\Models\Review;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Guest extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * This list now aligns with the guest migration table.
     */
    protected $fillable = [
        // Basic details
        'first_name',
        'last_name',
        'email',
        'phone',
        'user_id',

        // Demographics
        'date_of_birth',
        'gender',
        'nationality',

        // Identity & Verification
        'passport_number',
        'id_number',
        'verified',

        // Preferences & Loyalty
        'preferred_language',
        'preferred_currency',
        'loyalty_tier',
        'loyalty_points',

        // Contact & Emergency
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relation',

        // Travel Information
        'frequent_flyer_number',
        'special_requests',

        // Address
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'country',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'verified' => 'boolean',
        'date_of_birth' => 'date',
    ];

    /**
     * Relationships
     */

    // A guest can have many bookings
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    // A guest may have one user account (for login & advanced features)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id'); // guests.user_id → users.id
    }

    // A guest can leave reviews
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    // A guest may join loyalty programs
    /**
     * A guest may join loyalty programs
     */
    /** public function loyaltyTransactions(): HasMany
     * {
     *     return $this->hasMany(LoyaltyTransaction::class);
     * }
     */

    /**
     * Accessors & Helpers
     */

    // Full name accessor
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    // Preferred room type accessor (from preferences JSON)
    public function getPreferredRoomTypeAttribute(): ?string
    {
        return $this->preferences['room_type'] ?? null;
    }

    // Add loyalty points easily
    public function addLoyaltyPoints(int $points): void
    {
        $this->increment('loyalty_points', $points);
    }

    public function testimonials()
    {
        return $this->morphMany(Review::class, 'testimonialable');
    }

    public function wishlistHotels()
    {
        return $this->belongsToMany(Hotel::class, 'hotel_user_wishlist')
            ->withTimestamps()
            ->withPivot('added_at');
    }
}
