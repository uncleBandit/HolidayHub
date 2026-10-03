<?php

namespace App\Modules\Audit\Domain\Models;

use App\Modules\Identity\Domain\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

/**
 * Platform-wide record of an administrative action.
 *
 * This answers "who did what to which record, and when" for the whole platform.
 * It is deliberately separate from a domain's own event history — tenant
 * verification decisions, for example, are also written to
 * TenantVerification with before/after statuses, whereas an audit log row is a
 * flat, uniform trail that can be searched across every subsystem.
 *
 * @property int $id
 * @property int|null $admin_id
 * @property string|null $action
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property array<string, mixed>|null $meta
 */
class AuditLog extends Model
{
    /** @use HasFactory<\App\Modules\Audit\Database\Factories\AuditLogFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'admin_id',
        'action',
        'subject_type',
        'subject_id',
        'meta',
        'before',
        'after',
        'reason',
        'ip_address',
        'user_agent',
        'request_id',
        'correlation_id',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'before' => 'array',
            'after' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Audit records are immutable.'));
        static::deleting(fn () => throw new LogicException('Audit records are immutable.'));
    }

    /**
     * The platform staff member who performed the action, if any.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * The record that was acted upon.
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
