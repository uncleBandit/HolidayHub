<?php

namespace App\Modules\Accommodation\Domain\Models;

use App\Modules\Accommodation\Domain\Enums\RoomStatus;
use App\Modules\Booking\Domain\Models\Booking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'hotel_id',
        'room_type_id',
        'room_number',
        'name',
        'description',
        'capacity',
        'beds',
        'bed_type',
        'status',
        'is_available',
        'has_ac',
        'has_wifi',
        'has_tv',
        'has_balcony',
        'thumbnail',
        'gallery',
    ];

    protected $casts = [
        'room_number' => 'integer',
        'capacity' => 'integer',
        'beds' => 'integer',
        'status' => RoomStatus::class,
        'is_available' => 'boolean',
        'has_ac' => 'boolean',
        'has_wifi' => 'boolean',
        'has_tv' => 'boolean',
        'has_balcony' => 'boolean',
        'gallery' => 'array',
    ];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function bookings(): MorphMany
    {
        return $this->morphMany(Booking::class, 'bookable');
    }

    public function amenities(): MorphToMany
    {
        return $this->morphToMany(
            \App\Modules\Catalog\Domain\Models\Amenity::class,
            'amenable',
            'amenables',
            'amenable_id',
            'amenity_id'
        );
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', RoomStatus::Available);
    }

    public function setIsAvailableAttribute(bool $available): void
    {
        $this->attributes['is_available'] = $available;
        $this->attributes['status'] = $available
            ? RoomStatus::Available->value
            : RoomStatus::Maintenance->value;
    }

    public function setStatusAttribute(RoomStatus|string $status): void
    {
        $status = $status instanceof RoomStatus ? $status->value : $status;

        $this->attributes['status'] = $status;
        $this->attributes['is_available'] = $status === RoomStatus::Available->value;
    }
}
