<?php

namespace App\Modules\Audit\Application\Services;

use App\Modules\Audit\Domain\Models\AuditLog;
use App\Modules\Identity\Domain\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AuditRecorder
{
    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     * @param  array<string, mixed>  $meta
     */
    public function record(
        string $action,
        ?Model $subject = null,
        ?User $actor = null,
        ?array $before = null,
        ?array $after = null,
        ?string $reason = null,
        array $meta = [],
    ): AuditLog {
        $request = request();
        $requestId = $request?->attributes->get('request_id')
            ?? $request?->header('X-Request-Id');
        if (! is_string($requestId) || preg_match('/^[A-Za-z0-9._:-]{1,128}$/', $requestId) !== 1) {
            $requestId = null;
        }
        $correlationId = $request?->attributes->get('correlation_id');
        if (! is_string($correlationId) || preg_match('/^[A-Za-z0-9._:-]{1,128}$/', $correlationId) !== 1) {
            $correlationId = $requestId ?? (string) Str::uuid();
        }

        return AuditLog::forceCreate([
            'admin_id' => ($actor ?? auth()->user())?->getAuthIdentifier(),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'before' => $before,
            'after' => $after,
            'reason' => $reason,
            'meta' => $meta === [] ? null : $meta,
            'ip_address' => $request?->ip(),
            'user_agent' => mb_substr((string) $request?->userAgent(), 0, 1000),
            'request_id' => $requestId,
            'correlation_id' => $correlationId,
        ]);
    }
}
