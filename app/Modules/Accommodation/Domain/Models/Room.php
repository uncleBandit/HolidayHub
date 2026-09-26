<?php

namespace App\Modules\Accommodation\Domain\Models;

use App\Modules\Booking\Domain\Models\Booking;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'hotel_id',
        'room_type_id', // This links the physical room to its RoomType
        'room_number',  // A unique identifier for the physical room
        'status',       // e.g., 'available', 'under_maintenance', 'occupied'
    ];

    /** Relationships */

    /**
     * A physical room belongs to a single hotel.
     */
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    /**
     * A physical room belongs to a single RoomType.
     */
    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    /**
     * A Room can have many Bookings.
     * This uses a polymorphic relationship.
     */
    public function bookings(): MorphMany
    {
        return $this->morphMany(Booking::class, 'bookable');
    }
}
