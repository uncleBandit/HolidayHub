<?php

namespace App\Models;

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
        'cancelled_at',
    ];

    protected $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'price_per_night' => 'float',
        'total_amount' => 'float',
        'special_requests' => 'array',
        'cancelled_at' => 'datetime',
    ];

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
}
