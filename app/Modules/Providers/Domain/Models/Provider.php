<?php

namespace App\Modules\Providers\Domain\Models;

use App\Modules\Accommodation\Domain\Models\Accommodation;
use App\Modules\Accommodation\Domain\Models\Hotel;
use App\Modules\Accommodation\Domain\Models\Room;
use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Administration\Domain\Concerns\TracksVerification;
use App\Modules\Administration\Domain\Contracts\VerifiableProfile;
use App\Modules\Catalog\Domain\Models\Offer;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Media\Domain\Models\MediaPost;
use App\Modules\Reviews\Domain\Models\Review;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Provider extends Model implements VerifiableProfile
{
    use HasFactory, SoftDeletes, TracksVerification;

    /**
     * The attributes that are mass assignable.
     *
     * Mirrors the columns created by
     * 2025_09_01_000005_create_providers_table. The previous list named columns
     * that do not exist (contact_person_name, business_type, registration_number,
     * tax_id, description, state, postal_code, website_url, social_links,
     * status) while omitting ones that do (contact_person, email, phone, bio,
     * business_license, tax_number, provider_type, latitude, longitude,
     * is_verified), so mass assignment silently discarded real data.
     *
     * is_verified is deliberately absent: only the verification workflow may set
     * it, via forceFill in TracksVerification.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',               // Linked to main users table (login, auth, roles)
        'company_name',          // Legal / Brand name
        'contact_person',        // Main contact
        'email',
        'phone',
        'bio',
        'business_license',      // Business registration / licence number
        'tax_number',            // Optional for billing
        'provider_type',         // hotel, bnb, resort, apartment, hostel, villa, camp, other
        'country',
        'city',
        'address',
        'latitude',
        'longitude',
        'logo',                  // Brand/logo image
        'active',
    ];

    /**
     * Casts for specific fields.
     */
    protected $casts = [
        'verified_at' => 'datetime',
        'is_verified' => 'boolean',
        'active' => 'boolean',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
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

    public function mediaPosts()
    {
        return $this->hasMany(MediaPost::class);
    }

    public function accommodations()
    {
        return $this->hasMany(Accommodation::class);
    }
}
