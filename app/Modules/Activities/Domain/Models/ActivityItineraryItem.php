<?php

namespace App\Modules\Activities\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityItineraryItem extends Model
{
    use HasFactory;

    protected $fillable = ['sequence', 'title', 'description', 'duration_minutes'];

    protected $casts = [
        'sequence' => 'integer',
        'duration_minutes' => 'integer',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
