<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'hotel_id',
        'room_id',
        'user_id',
        'check_in',
        'check_out',
        'guests',
        'total_price',
        'status',  // pending, confirmed, cancelled, completed
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out'=> 'date',
        'total_price' => 'float',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }
}
