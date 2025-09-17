<?php



namespace App\Models;

use App\Contracts\Bookable;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class RoomType extends Model implements Bookable
{
    use HasFactory;

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
        'capacity'        => 'integer',
        'beds'            => 'integer',
        'gallery_images'  => 'array',
        'amenities'       => 'array',
    ];

    /**
     * A RoomType belongs to a Hotel.
     *
     * @return BelongsTo
     */
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    /**
     * A RoomType can have many Rooms.
     * This defines the one-to-many relationship with the Room model.
     *
     * @return HasMany
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    /**
     * A RoomType can have many dynamic prices.
     *
     * @return HasMany
     */
    public function prices(): HasMany
    {
        return $this->hasMany(RoomPrice::class);
    }

    /**
     * Returns a default hero image for the room type.
     * This is a "mutator" that formats the attribute when it's accessed.
     *
     * @return string
     */
    public function getHeroImageUrlAttribute(): string
    {
        return $this->attributes['hero_image_url'] ?? 'https://via.placeholder.com/1200x800?text=' . Str::slug($this->name);
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
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the type of the bookable item.
     *
     * @return string
     */
    public function getType(): string
    {
        return 'room-type';
    }

    /**
     * Get the name of the bookable item.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the description of the bookable item.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Get an array of image URLs for the bookable item.
     *
     * @return array
     */
    public function getImages(): array
    {
        $images = [$this->hero_image_url];
        if (!empty($this->gallery_images)) {
            $images = array_merge($images, $this->gallery_images);
        }
        return $images;
    }

    /**
     * Get the base price of the bookable item.
     *
     * @return float
     */
    public function getBasePrice(): float
    {
        return $this->price_per_night;
    }

    /**
     * Get the price for a specific date, accounting for dynamic pricing.
     *
     * @param string $date
     * @return float
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
     *
     * @param string $checkIn
     * @param string $checkOut
     * @return bool
     */
    public function isAvailable(string $checkIn, string $checkOut): bool
    {
    // Get all room IDs for this room type
    $roomIds = $this->rooms()->pluck('id');

    if ($roomIds->isEmpty()) {
        return false;
    }

    $period = CarbonPeriod::create($checkIn, Carbon::parse($checkOut)->subDay());

    foreach ($this->rooms()->get() as $room) { // ✅ query directly
        $isRoomAvailable = true;

        foreach ($period as $date) {
            $bookedCount = Booking::where('bookable_id', $this->id)
                ->where('bookable_type', self::class)
                ->where('check_in_date', '<=', $date->toDateString())
                ->where('check_out_date', '>', $date->toDateString())
                ->count();

            if ($bookedCount > 0) {
                $isRoomAvailable = false;
                break;
            }
        }

        if ($isRoomAvailable) {
            return true;
        }
    }

    return false;
    }


    /**
     * Get the associated availabilities.
     *
     * @return MorphMany
     */
    public function availabilities(): MorphMany
    {
        return $this->morphMany(Availability::class, 'bookable');
    }

    public function getIncludedGuests(): int
    {
        return $this->max_guests;
    }

    /**
     * Get the currency for the bookable item.
     *
     * @inheritDoc
     */
    public function getCurrency(): string
    {
        return 'USD';
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
}

