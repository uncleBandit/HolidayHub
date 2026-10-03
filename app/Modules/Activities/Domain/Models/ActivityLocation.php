<?php

namespace App\Modules\Activities\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'address',
        'latitude',
        'longitude',
        'instructions',
        'sequence',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'sequence' => 'integer',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
