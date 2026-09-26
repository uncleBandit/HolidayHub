<?php

namespace App\Modules\Agents\Domain\Models;

use App\Modules\Accommodation\Domain\Models\Hotel;
use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Administration\Domain\Concerns\TracksVerification;
use App\Modules\Administration\Domain\Contracts\VerifiableProfile;
use App\Modules\Booking\Domain\Models\Booking;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Packages\Domain\Models\Package;
use App\Modules\Reviews\Domain\Models\Review;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agent extends Model implements VerifiableProfile
{
    use HasFactory, SoftDeletes, TracksVerification;

    /**
     * The attributes that are mass assignable.
     *
     * Mirrors the columns created by 2025_09_01_000006_create_agents_table. The
     * previous list named columns that do not exist (license_number, tax_id,
     * website, logo_path, verification_status, rating, total_bookings,
     * response_time, preferred_currency, status) while omitting the NOT NULL
     * first_name and last_name, plus is_verified and verified_at.
     *
     * is_verified is deliberately absent: only the verification workflow may set
     * it, via forceFill in TracksVerification.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',            // Links to main User table for authentication
        'first_name',
        'last_name',
        'email',
        'phone',
        'agency_name',        // Business/Agency name
        'bio',
        'agency_license',     // Government/industry licence/registration
        'specialization',     // Hotels, Tours, Flights, Cruises
        'country',
        'city',
        'address',
        'commission_rate',    // % commission agreed upon
        'business_type',      // individual, agency, corporate
        'profile_photo',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
            'is_verified' => 'boolean',
            'active' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

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
