<?php

namespace App\Modules\Activities\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivitySchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'activity_option_id',
        'day_of_week',
        'start_time',
        'end_time',
        'timezone',
        'capacity',
        'active_from',
        'active_until',
        'booking_cutoff_minutes',
        'is_active',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'capacity' => 'integer',
        'active_from' => 'date',
        'active_until' => 'date',
        'booking_cutoff_minutes' => 'integer',
        'is_active' => 'boolean',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(ActivityOption::class, 'activity_option_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ActivitySession::class);
    }
}
