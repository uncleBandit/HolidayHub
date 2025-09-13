<?php

namespace App\Models;

use App\Contracts\Interface\Bookable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

class Package extends Model implements Bookable
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'destination',
        'price',
        'currency',
        'duration_days',
        'start_date',
        'end_date',
        'max_guests',
        'agent_id',
        'image_url',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'price' => 'decimal:2',
    ];

    /** Relationships */

    /**
     * A package belongs to a travel agent.
     *
     * @return BelongsTo
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * A package can have many polymorphic bookings.
     *
     * @return MorphMany
     */
    public function bookings(): MorphMany
    {
        return $this->morphMany(Booking::class, 'bookable');
    }

    /**
     * A package can have many polymorphic reviews.
     *
     * @return MorphMany
     */
    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    /**
     * A package can have many polymorphic images.
     *
     * @return MorphMany
     */
    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

        /**
     * A package can have many polymorphic features.
     *
     * @return MorphMany
     */
    public function features(): MorphMany
    {
        return $this->morphMany(PackageFeature::class, 'featureable');
    }


    /**
     * A package can have many polymorphic offers.
     *
     * @return MorphMany
     */
    public function offers(): MorphMany
    {
        return $this->morphMany(Offer::class, 'offerable');
    }

    /** Bookable Interface Methods */

    /**
     * @inheritDoc
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return 'package';
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @inheritDoc
     */
    public function getImages(): array
    {
        // For a package with a single `image_url` attribute
        return $this->image_url ? [$this->image_url] : [];
    }

    /**
     * @inheritDoc
     */
    public function getBasePrice(): float
    {
        return (float) $this->price;
    }

    /**
     * @inheritDoc
     */
    public function getPriceForDate(string $date): float
    {
        // For packages, the price is generally fixed for the entire duration,
        // so we return the base price. Dynamic pricing would require a more complex check.
        return $this->getBasePrice();
    }

    /**
     * @inheritDoc
     */
    public function isAvailable(string $checkIn, string $checkOut): bool
    {
        $startDate = Carbon::parse($checkIn);
        $endDate = Carbon::parse($checkOut);

        // Check if the requested dates fall within the package's overall availability window.
        if ($startDate->lessThan($this->start_date) || $endDate->greaterThan($this->end_date)) {
            return false;
        }

        // Check if there are any existing bookings for the package within the date range
        // that would make it unavailable (e.g., if there is a 'max_guests' limit).
        // For this example, we assume unlimited availability, but in a real-world scenario
        // you would check against the 'max_guests' and the number of current bookings.
        return true;
    }

    /**
     * @inheritDoc
     */
    public function availabilities(): HasMany
    {
        // Packages don't have per-day availability in the same way as a hotel room.
        // You might have a specific `PackageAvailability` model if you manage a limited
        // number of slots or tours on specific days, but for a general package, this
        // method might be a placeholder or return a different type of relationship.
        throw new \BadMethodCallException("Packages do not have per-day availabilities in this implementation.");
    }

    /**
     * @inheritDoc
     */
    public function getIncludedGuests(): int
    {
        return $this->max_guests;
    }

    /**
     * @inheritDoc
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @inheritDoc
     */
    public function getDefaultMaxGuests(): int
    {
        return $this->max_guests;
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

     public function seasonalRates(): MorphMany
    {
        return $this->morphMany(SeasonalRate::class, 'seasonal_rateable');
    }

}
