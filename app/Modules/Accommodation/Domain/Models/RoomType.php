<?php

namespace App\Modules\Accommodation\Domain\Models;

use App\Modules\Availability\Domain\Models\Availability;
use App\Modules\Booking\Domain\Models\Booking;
use App\Modules\Catalog\Domain\Models\Offer;
use App\Modules\Media\Domain\Models\Traits\HasImages;
use App\Modules\Pricing\Domain\Models\SeasonalRate;
use App\Shared\Domain\Contracts\AvailabilityAware;
use App\Shared\Domain\Contracts\Bookable;
use App\Shared\Domain\Contracts\HasUnitCapacity;
use App\Shared\Domain\Contracts\Pricable;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class RoomType extends Model implements AvailabilityAware, Bookable, HasUnitCapacity, Pricable
{
    use HasFactory, HasImages;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'hotel_id',
        'name',
        'slug',
        'description',
        'price_per_night',
        'currency',
        'capacity',
        'beds',
        'hero_image_url',
        'gallery_images', // Stored as a JSON array of image URLs
        'amenities',      // Stored as a JSON array
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price_per_night' => 'float',
        'capacity' => 'integer',
        'beds' => 'integer',
        'gallery_images' => 'array',
        'amenities' => 'array',
    ];

    /**
     * A RoomType belongs to a Hotel.
     */
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    /**
     * A RoomType can have many Rooms.
     * This defines the one-to-many relationship with the Room model.
     */

    /**
     * Number of separately bookable units, for occupancy-based pricing.
     *
     * Always at least one: a property with no rows is still bookable as a
     * whole, and returning 0 would make demand pricing divide by zero.
     */
    public function unitCapacity(): int
    {
        return max(1, $this->rooms()->count());
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    /**
     * A RoomType can have many dynamic prices.
     */
    public function prices(): HasMany
    {
        return $this->hasMany(RoomPrice::class);
    }

    /**
     * Returns a default hero image for the room type.
     * This is a "mutator" that formats the attribute when it's accessed.
     */
    public function getHeroImageUrlAttribute(): string
    {
        return $this->attributes['hero_image_url'] ?? asset('storage/roomtypes/default-hero.jpg');
    }

    /**
     * Automatically generate a slug from the name when creating the model.
     */
    protected static function booted(): void
    {
        static::creating(function (self $roomType) {
            $roomType->slug = Str::slug($roomType->name);
        });
    }

    /**
     * Implement Bookable interface methods
     */

    /**
     * Get the ID of the bookable item.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the type of the bookable item.
     */
    public function getType(): string
    {
        return 'room-type';
    }

    /**
     * Get the name of the bookable item.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the description of the bookable item.
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Get an array of image URLs for the bookable item.
     */
    public function getImages(): array
    {
        $gallery = is_array($this->gallery_images)
            ? $this->gallery_images
            : json_decode($this->gallery_images ?? '[]', true);

        $all = array_merge([$this->hero_image_url], $gallery);

        return collect($all)
            ->filter()
            ->map(fn ($img) => $img
                ? asset('storage/'.ltrim($img, '/')) // prepend only once
                : null
            )
            ->toArray();
    }

    /**
     * Get the base price of the bookable item.
     */
    public function getBasePrice(): float
    {
        return $this->price_per_night;
    }

    /**
     * Get the price for a specific date, accounting for dynamic pricing.
     */
    public function getPriceForDate(string $date): float
    {
        $price = $this->prices()
            ->where('date', $date)
            ->value('price');

        return $price ?? $this->base_price;
    }

    /**
     * Check if the bookable item is available for a given date range.
     */
    public function isAvailable(string $checkIn, string $checkOut): bool
    {
        $rooms = $this->rooms()->with('bookings')->get(); // eager load bookings
        if ($rooms->isEmpty()) {
            return false;
        }

        $period = CarbonPeriod::create($checkIn, Carbon::parse($checkOut)->subDay());

        foreach ($rooms as $room) {
            $isRoomAvailable = true;

            foreach ($period as $date) {
                $booked = $room->bookings()
                    ->where('check_in_date', '<=', $date->toDateString())
                    ->where('check_out_date', '>', $date->toDateString())
                    ->exists();

                if ($booked) {
                    $isRoomAvailable = false;
                    break;
                }
            }

            if ($isRoomAvailable) {
                return true; // ✅ at least one room is free
            }
        }

        return false; // all rooms booked
    }

    /**
     * Get the associated availabilities.
     */
    public function availabilities(): MorphMany
    {
        return $this->morphMany(Availability::class, 'bookable');
    }

    public function getIncludedGuests(): int
    {
        return $this->capacity ?? 2; // fallback
    }

    /**
     * Get the currency for the bookable item.
     *
     * {@inheritDoc}
     */
    public function getCurrency(): string
    {
        return $this->currency ?? 'USD';
    }

    /**
     * Get the default number of guests for this bookable item.
     * This method is required by the Bookable interface.
     */
    public function getDefaultMaxGuests(): int
    {
        return 2;
    }

    public function seasonalRates(): MorphMany
    {
        return $this->morphMany(SeasonalRate::class, 'seasonal_rateable');
    }

    public function offers(): MorphMany
    {
        return $this->morphMany(Offer::class, 'offerable');
    }

    public function bookings(): MorphMany
    {
        return $this->morphMany(Booking::class, 'bookable');
    }
}
