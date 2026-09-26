<?php

namespace App\Modules\Administration\Domain\Models;

use App\Modules\Administration\Domain\Enums\TenantStatus;
use App\Modules\Identity\Domain\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A party that supplies services on the platform: an accommodation provider,
 * a B&B owner, or a travel agent.
 *
 * The tenant row owns the verification decision. `is_verified` on the
 * underlying Provider/Agent profile is a denormalised copy kept in sync by
 * {@see \App\Modules\Administration\Application\Services\TenantVerificationService}.
 *
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property TenantStatus $status
 * @property string|null $rejection_reason
 * @property string|null $suspension_reason
 * @property \Illuminate\Support\Carbon|null $verified_at
 * @property \Illuminate\Support\Carbon|null $suspended_at
 */
class Tenant extends Model
{
    /** @use HasFactory<\App\Modules\Administration\Database\Factories\TenantFactory> */
    use HasFactory;

    // The table carries deleted_at. Without this trait every query would keep
    // returning withdrawn tenants, including the review queue.
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'type',
        'tenantable_type',
        'tenantable_id',
        'display_name',
        'contact_email',
        'status',
        'rejection_reason',
        'suspension_reason',
        'admin_notes',
        'submitted_at',
        'verified_at',
        'verified_by',
        'suspended_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TenantStatus::class,
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    /**
     * The login account behind the business.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The business profile this application belongs to (Provider or Agent).
     */
    public function tenantable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The admin who granted approval, if any.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Append-only decision history, oldest first.
     */
    public function verifications(): HasMany
    {
        return $this->hasMany(TenantVerification::class)->oldest();
    }

    /**
     * Tenants the platform has cleared to list services.
     *
     * @param  Builder<Tenant>  $query
     */
    public function scopeApproved(Builder $query): void
    {
        $query->where('status', TenantStatus::Approved->value);
    }

    /**
     * Applications still waiting on an admin decision.
     *
     * These are what the review queue surfaces first, so this deliberately
     * excludes UnderReview and Suspended.
     *
     * @param  Builder<Tenant>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->where('status', TenantStatus::Pending->value);
    }

    /**
     * Everything requiring admin attention, oldest first, so the longest-waiting
     * applicant is never buried under a stream of new ones.
     *
     * @param  Builder<Tenant>  $query
     */
    public function scopeAwaitingReview(Builder $query): void
    {
        $query->whereIn('status', [TenantStatus::Pending->value, TenantStatus::UnderReview->value])
            ->oldest('created_at');
    }

    /**
     * Whether this tenant may currently list services.
     */
    public function grantsMarketplaceAccess(): bool
    {
        return $this->status->grantsMarketplaceAccess();
    }

    /**
     * Whether the platform has an outstanding decision to make.
     */
    public function isActionable(): bool
    {
        return $this->status->isActionable();
    }
}
