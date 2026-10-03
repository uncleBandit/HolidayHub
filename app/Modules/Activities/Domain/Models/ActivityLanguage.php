<?php

namespace App\Modules\Activities\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLanguage extends Model
{
    use HasFactory;

    protected $fillable = ['language_code'];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
