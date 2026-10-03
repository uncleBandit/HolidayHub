<?php

namespace App\Modules\Administration\Filament\Resources\UserResource\Pages;

use App\Modules\Administration\Filament\Resources\UserResource;
use App\Modules\Audit\Application\Services\AuditRecorder;
use App\Modules\Identity\Domain\Models\User;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Edit a platform account's profile and role assignments.
 */
class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /** @var array<string, mixed> */
    private array $before = [];

    protected function beforeSave(): void
    {
        $this->before = [
            'roles' => $this->record->roles()->pluck('name')->all(),
            'name' => $this->record->name,
            'email' => $this->record->email,
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['email'] ?? null) !== $this->record->email) {
            $data['email_verified_at'] = null;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var User $record */
        $record = $this->record;
        $after = [
            'roles' => $record->roles()->pluck('name')->all(),
            'name' => $record->name,
            'email' => $record->email,
        ];

        if ($this->before !== $after) {
            $changedFields = [];
            foreach (['name', 'email'] as $field) {
                if ($this->before[$field] !== $after[$field]) {
                    $changedFields[] = $field;
                }
            }

            app(AuditRecorder::class)->record(
                'users.account_updated',
                $record,
                auth()->user(),
                before: ['roles' => $this->before['roles']],
                after: ['roles' => $after['roles']],
                meta: ['profile_fields_changed' => $changedFields],
            );
        }
    }

    /**
     * @return array<int, \Filament\Actions\Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
