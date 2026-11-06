<?php

namespace App\Modules\Administration\Filament\Support;

use App\Modules\Audit\Application\Services\AuditRecorder;
use Illuminate\Database\ClassMorphViolationException;
use Illuminate\Database\Eloquent\Model;

/**
 * AuditRecorder for admin screens, tolerant of models outside the morph map.
 *
 * The morph map is enforced, so a model without an alias (e.g.
 * ActivityCategory) cannot be an audit subject; it is identified in meta.
 */
final class AdminAudit
{
    public static function record(string $action, Model $record, ?array $before, ?array $after, ?string $reason = null, array $meta = []): void
    {
        try {
            $record->getMorphClass();
            $subject = $record;
        } catch (ClassMorphViolationException) {
            $subject = null;
            $meta += ['subject_class' => $record::class, 'subject_id' => $record->getKey()];
        }

        app(AuditRecorder::class)->record($action, $subject, auth()->user(), before: $before, after: $after, reason: $reason, meta: $meta);
    }
}
