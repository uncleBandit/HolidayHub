<?php

namespace App\Modules\Availability\Domain\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Availability extends Model
{
    use HasFactory;

    protected $fillable = [
        'bookable_id',
        'bookable_type',
        'start_date',
        'end_date',
        'quantity',
        'price_per_night',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'quantity' => 'integer',
        'price_per_night' => 'decimal:2',
    ];

    /**
     * Polymorphic relation to any bookable model (Room, Villa, Hotel, Tour, etc.)
     */
    public function bookable()
    {
        return $this->morphTo();
    }

    /**
     * Scope: only currently available slots.
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', 'available')->where('quantity', '>', 0);
    }

    /**
     * Scope: availability within a specific date range.
     */
    public function scopeWithinDates(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start);
    }

    /**
     * Check if this availability covers a given period.
     */
    public function coversPeriod(Carbon $start, Carbon $end): bool
    {
        return $this->start_date->lte($start) && $this->end_date->gte($end);
    }

    /**
     * Reduce quantity when a booking is made.
     */
    public function reserve(int $count = 1): bool
    {
        if ($this->quantity < $count || $this->status !== 'available') {
            return false;
        }

        $this->decrement('quantity', $count);

        return true;
    }
}
