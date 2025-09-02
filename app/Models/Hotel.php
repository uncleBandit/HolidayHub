<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Hotel extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'address',
        'city',
        'country',
        'latitude',
        'longitude',
        'rating',          // average guest rating
        'stars',           // 1–5 star system
        'email',
        'phone',
        'amenities',       // JSON or relation
        'policies',        // e.g. check-in/out, cancellations
        'cover_image',
        'status',          // active, inactive, under_review
        'is_featured'=> 'boolean',
    ];

    protected $casts = [
        'amenities' => 'array',
        'policies' => 'array',
        'rating'   => 'float',
        'latitude' => 'float',
        'longitude'=> 'float',
    ];

    /** Relationships */
    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * A Hotel can have many images.
     */
    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function reviews(): MorphMany
    {
    return $this->morphMany(Review::class, 'reviewable');
    }

    public function manager()
    {
        return $this->belongsTo(Provider::class, 'manager_id');
    }

    // Polymorphic link back to Accommodation
    public function accommodation(): MorphOne
    {
        return $this->morphOne(Accommodation::class, 'bookable');
    }

    public function hotelAmenities()
    {
    return $this->belongsToMany(Amenity::class, 'amenity_hotel', 'hotel_id', 'amenity_id');
    }


    public function wishlistedByUsers()
    {
    return $this->belongsToMany(Guest::class, 'hotel_user_wishlist')
        ->withTimestamps()
        ->withPivot('added_at');
    }


    /**
     * Get availability for each day in the next month.
     *
     * @return array<string, int>  // e.g. ['2025-09-01' => 3, '2025-09-02' => 5]
     */
    public function getAvailabilityForNextMonth(): array
    {
        $start = Carbon::today();
        $end   = $start->copy()->addMonth();

        $availability = [];

        // get all room ids for this hotel
        $roomIds = $this->rooms()->pluck('id');

        // if hotel has no rooms, return empty availability
        if ($roomIds->isEmpty()) {
            return [];
        }

        // iterate through each day
        $date = $start->copy();
        while ($date->lessThan($end)) {
            $day = $date->toDateString();

            // total rooms
            $totalRooms = $this->rooms()->count();

            // rooms already booked for this day
            $bookedRooms = \App\Models\Booking::query()
                ->whereIn('room_id', $roomIds)
                ->whereDate('check_in', '<=', $day)
                ->whereDate('check_out', '>', $day)
                ->count();

            // remaining availability
            $availability[$day] = max(0, $totalRooms - $bookedRooms);

            $date->addDay();
        }

        return $availability;
    }


}
