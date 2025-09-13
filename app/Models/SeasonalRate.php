<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SeasonalRate extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'seasonal_rates';

    /**
     * Mass assignable attributes.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'rate',
        'currency',
        'start_date',
        'end_date',
        'description',
        'active',
    ];

    /**
     * Cast attributes to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'datetime:Y-m-d',
        'end_date'   => 'datetime:Y-m-d',
        'rate'       => 'decimal:2',
        'active'     => 'boolean',
    ];

    /**
     * Get the owning seasonal rateable model.
     *
     * @return MorphTo
     */
    public function seasonalRateable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope: only currently active seasonal rates.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * Scope: seasonal rates valid for a given date range.
     */
    public function scopeForPeriod(Builder $query, string|\DateTimeInterface $start, string|\DateTimeInterface $end): Builder
    {
        return $query->where(function ($q) use ($start, $end) {
            $q->whereBetween('start_date', [$start, $end])
              ->orWhereBetween('end_date', [$start, $end])
              ->orWhere(function ($q) use ($start, $end) {
                  $q->where('start_date', '<=', $start)
                    ->where('end_date', '>=', $end);
              });
        });
    }

    /**
     * Helper: check if this rate is valid today.
     */
    public function isValidToday(): bool
    {
        $today = now();
        return $this->active &&
               $this->start_date->lte($today) &&
               $this->end_date->gte($today);
    }
}
