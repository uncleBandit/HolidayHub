<?php

namespace App\Modules\Administration\Filament\Concerns;

use App\Modules\Administration\Filament\Support\AdminAudit;
use Illuminate\Database\Eloquent\Model;

/**
 * Writes an audit entry for every admin create and edit made through a
 * Filament resource page, with the before and after values of what changed.
 *
 * The using page sets the action prefix, e.g. `destinations`, which yields
 * `destinations.created` and `destinations.updated`.
 */
trait AuditsRecordChanges
{
    /** @var array<string, mixed> */
    protected array $auditBefore = [];

    abstract protected static function auditPrefix(): string;

    protected function beforeSave(): void
    {
        $this->auditBefore = $this->getRecord()->getAttributes();
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();
        $changes = collect($record->getChanges())->except(['updated_at'])->all();

        if ($changes === []) {
            return;
        }

        AdminAudit::record(static::auditPrefix().'.updated', $record, array_intersect_key($this->auditBefore, $changes), $changes);
    }

    protected function afterCreate(): void
    {
        /** @var Model $record */
        $record = $this->getRecord();

        AdminAudit::record(static::auditPrefix().'.created', $record, null, collect($record->getAttributes())->except(['created_at', 'updated_at'])->all());
    }
}
