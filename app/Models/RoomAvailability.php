<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomAvailability extends Model
{
    /** @use HasFactory<\Database\Factories\RoomAvailabilityFactory> */
    use HasFactory;

    protected $casts = [
        'date'        => 'date',
        'is_available'=> 'boolean',
        'price'       => 'decimal:2',
    ];
}
