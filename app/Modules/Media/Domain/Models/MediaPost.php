<?php

namespace App\Modules\Media\Domain\Models;

use App\Modules\Media\Domain\Enums\MediaAssetStatus;
use App\Modules\Media\Domain\Enums\MediaAssetType;
use App\Modules\Media\Domain\Enums\MediaPostStatus;
use App\Modules\Media\Domain\Enums\MediaPostType;
use App\Modules\Providers\Domain\Models\Provider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MediaPost extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'provider_id',
        'type',
        'title',
        'caption',
        'status',
        'visibility',
        'published_at',
        'reviewed_by',
        'moderation_notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => MediaPostType::class,
            'status' => MediaPostStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Identity\Domain\Models\User::class, 'reviewed_by');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(MediaAsset::class);
    }

    public function targetable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopePubliclyPublished(Builder $query): Builder
    {
        return $query
            ->where('status', MediaPostStatus::Published->value)
            ->where('visibility', 'public')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereHas('provider', fn (Builder $provider) => $provider->where('active', true))
            ->whereHas('assets', fn (Builder $asset) => $asset
                ->where('type', MediaAssetType::Original->value)
                ->where('status', MediaAssetStatus::Ready->value));
    }

    public function asset(MediaAssetType $type): ?MediaAsset
    {
        return $this->assets->first(fn (MediaAsset $asset): bool => $asset->type === $type);
    }
}
