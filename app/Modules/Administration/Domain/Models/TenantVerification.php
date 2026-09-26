<?php

namespace App\Modules\Administration\Domain\Models;

use App\Modules\Administration\Domain\Enums\TenantStatus;
use App\Modules\Identity\Domain\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One recorded transition in a tenant's verification lifecycle.
 *
 * Rows are appended, never updated, so the full decision history of a tenant
 * survives even if the tenant is later suspended and reinstated.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int|null $admin_id
 * @property TenantStatus|null $from_status
 * @property TenantStatus $to_status
 * @property string|null $reason
 */
class TenantVerification extends Model
{
    /** @use HasFactory<\App\Modules\Administration\Database\Factories\TenantVerificationFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'admin_id',
        'from_status',
        'to_status',
        'reason',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => TenantStatus::class,
            'to_status' => TenantStatus::class,
            'metadata' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * The admin who made this decision, or null when the tenant acted alone
     * (applying, or resubmitting after a rejection).
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Short description for the admin timeline.
     */
    public function describe(): string
    {
        $target = $this->to_status->label();

        if ($this->from_status === null) {
            return "Applied — status set to {$target}";
        }

        return sprintf(
            '%s → %s%s',
            $this->from_status->label(),
            $target,
            $this->admin_id === null ? ' (tenant)' : '',
        );
    }
}
