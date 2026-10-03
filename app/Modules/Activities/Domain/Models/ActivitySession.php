<?php

namespace App\Modules\Activities\Domain\Models;

use App\Modules\Activities\Domain\Enums\ActivitySessionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class ActivitySession extends Model
{
    use HasFactory;

    protected $fillable = [
        'activity_id',
        'activity_schedule_id',
        'activity_option_id',
        'starts_at',
        'ends_at',
        'timezone',
        'capacity',
        'booked_capacity',
        'status',
        'booking_cutoff_at',
    ];

    protected $casts = [
        'starts_at' => 'immutable_datetime',
        'ends_at' => 'immutable_datetime',
        'booking_cutoff_at' => 'immutable_datetime',
        'capacity' => 'integer',
        'booked_capacity' => 'integer',
        'status' => ActivitySessionStatus::class,
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(ActivityOption::class, 'activity_option_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ActivitySchedule::class, 'activity_schedule_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(\App\Modules\Booking\Domain\Models\Booking::class, 'activity_session_id');
    }

    public function scopeBookable(Builder $query): Builder
    {
        return $query->where('status', ActivitySessionStatus::Scheduled)
            ->whereColumn('booked_capacity', '<', 'capacity')
            ->where(fn (Builder $query) => $query->whereNull('booking_cutoff_at')->orWhere('booking_cutoff_at', '>', now()));
    }

    public function availableCapacity(): int
    {
        return max(0, $this->capacity - $this->booked_capacity);
    }

    public function canFit(int $participants, ?Carbon $now = null): bool
    {
        return $participants > 0
            && $this->status === ActivitySessionStatus::Scheduled
            && $this->availableCapacity() >= $participants
            && ($this->booking_cutoff_at === null || $this->booking_cutoff_at->isAfter($now ?? now()));
    }
}
