<?php

namespace App\Modules\Activities\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityRequirement extends Model
{
    use HasFactory;

    protected $fillable = ['type', 'title', 'description', 'required'];

    protected $casts = ['required' => 'boolean'];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
