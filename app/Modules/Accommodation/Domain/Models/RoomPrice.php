<?php

namespace App\Modules\Accommodation\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'date',
        'price',
    ];

    protected $casts = [
        'date' => 'date',
        'price' => 'float',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
