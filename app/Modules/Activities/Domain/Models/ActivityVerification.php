<?php

namespace App\Modules\Activities\Domain\Models;

use App\Modules\Identity\Domain\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'reviewer_id',
        'status',
        'submitted_at',
        'reviewed_at',
        'notes',
        'provider_documents',
        'checks',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'provider_documents' => 'array',
        'checks' => 'array',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
