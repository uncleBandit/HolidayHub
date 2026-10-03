<?php

namespace App\Modules\Media\Domain\Models;

use App\Modules\Media\Domain\Enums\MediaAssetStatus;
use App\Modules\Media\Domain\Enums\MediaAssetType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MediaAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'media_post_id',
        'type',
        'disk',
        'path',
        'mime_type',
        'size_bytes',
        'width',
        'height',
        'duration_ms',
        'metadata',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'type' => MediaAssetType::class,
            'status' => MediaAssetStatus::class,
            'metadata' => 'array',
        ];
    }

    public function mediaPost(): BelongsTo
    {
        return $this->belongsTo(MediaPost::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)->temporaryUrl($this->path, now()->addHour());
    }
}
