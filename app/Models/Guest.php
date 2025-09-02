<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Guest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * Keep it clear and secure for modern APIs & Livewire usage.
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'date_of_birth',
        'nationality',
        'preferences',    // JSON: e.g., {"room_type": "deluxe", "diet": "vegan"}
        'loyalty_points', // Gamified experience for returning guests
        'profile_image',
        'user_id',        // Link if they also have an authenticated account
    ];

    /**
     * Casts for modern usage (API-friendly).
     */
    protected $casts = [
        'preferences' => 'array',
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
