<?php

namespace App\Modules\Administration\Filament\Resources\TenantResource\Pages;

use App\Modules\Administration\Domain\Models\Tenant;
use App\Modules\Administration\Filament\Resources\TenantResource;
use Filament\Resources\Pages\ListRecords;

/**
 * The tenant review queue.
 */
class ListTenants extends ListRecords
{
    protected static string $resource = TenantResource::class;

    /**
     * @return array<int, \Filament\Actions\Action>
     */
    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * Surface the queue depth above the table, so an admin landing here can see
     * how much is waiting before scrolling.
     */
    public function getSubheading(): ?string
    {
        $pending = Tenant::query()
            ->awaitingReview()
            ->count();

        return $pending === 0
            ? 'No applications are waiting for review.'
            : "{$pending} application(s) awaiting review, oldest first.";
    }
}
