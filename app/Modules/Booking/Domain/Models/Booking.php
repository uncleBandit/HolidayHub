<?php

namespace App\Modules\Booking\Domain\Models;

use App\Modules\Catalog\Domain\Models\Offer;
use App\Modules\Destinations\Domain\Models\Destination;
use App\Modules\Identity\Domain\Models\Guest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'guest_id',
        'bookable_id',
        'bookable_type',
        'offer_id',
        'destination_id',
        'check_in_date',
        'check_out_date',
        'guests_adults',
        'guests_children',
        'price_per_night',
        'total_amount',
        'currency',
        'payment_status',
        'payment_method',
        'status',
        'special_requests',
        'confirmation_code',
        'idempotency_key',
        'cancelled_at',
        'activity_session_id',
        'package_booking_id',
        'agent_commission',
    ];

    protected $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'price_per_night' => 'float',
        'total_amount' => 'float',
        'special_requests' => 'array',
        'cancelled_at' => 'datetime',
        'agent_commission' => 'float',
    ];

    /** The package booking this listing booking belongs to, if any. */
    public function packageBooking()
    {
        return $this->belongsTo(self::class, 'package_booking_id');
    }

    /** For a package booking: the bookings it made with each provider. */
    public function componentBookings()
    {
        return $this->hasMany(self::class, 'package_booking_id');
    }

    public function bookable()
    {
        return $this->morphTo();
    }

    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }

    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }

    public function destination()
    {
        return $this->belongsTo(Destination::class);
    }

    public function activitySession()
    {
        return $this->belongsTo(\App\Modules\Activities\Domain\Models\ActivitySession::class);
    }
}
