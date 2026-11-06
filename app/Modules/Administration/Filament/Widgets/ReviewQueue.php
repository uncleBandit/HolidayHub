<?php

namespace App\Modules\Administration\Filament\Widgets;

use App\Modules\Accommodation\Domain\Enums\AccommodationStatus;
use App\Modules\Accommodation\Domain\Models\Accommodation;
use App\Modules\Activities\Domain\Enums\ActivityStatus;
use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Administration\Filament\Resources\AccommodationModerationResource;
use App\Modules\Administration\Filament\Resources\ActivityModerationResource;
use App\Modules\Packages\Domain\Models\Package;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

/**
 * One queue of everything waiting for a moderator's decision, across stays,
 * experiences and journeys, oldest first, so nothing waits because it sits on
 * a page nobody opened. Each kind only appears to roles that may review it.
 */
class ReviewQueue extends Widget
{
    protected static ?int $sort = 4;

    protected string $view = 'administration::admin.widgets.review-queue';

    private const LIMIT = 8;

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user?->can('accommodations.view') || $user?->can('activities.view') || $user?->can('packages.view');
    }

    protected function getViewData(): array
    {
        $user = auth()->user();
        $items = collect();

        if ($user?->can('accommodations.view')) {
            $items = $items->concat(
                Accommodation::query()
                    ->with('provider')
                    ->where('status', AccommodationStatus::PendingReview)
                    ->oldest('submitted_at')
                    ->limit(self::LIMIT)
                    ->get()
                    ->map(fn (Accommodation $stay) => [
                        'kind' => str(class_basename((string) $stay->bookable_type))->headline()->toString() ?: 'Stay',
                        'name' => $stay->name,
                        'by' => $stay->provider?->company_name,
                        'waiting' => $stay->submitted_at,
                        'url' => AccommodationModerationResource::getUrl('view', ['record' => $stay]),
                    ])
            );
        }

        if ($user?->can('activities.view')) {
            $items = $items->concat(
                Activity::query()
                    ->with('provider')
                    ->whereIn('status', [ActivityStatus::Submitted, ActivityStatus::UnderReview])
                    ->oldest('submitted_at')
                    ->limit(self::LIMIT)
                    ->get()
                    ->map(fn (Activity $activity) => [
                        'kind' => 'Experience',
                        'name' => $activity->name,
                        'by' => $activity->provider?->company_name,
                        'waiting' => $activity->submitted_at,
                        'url' => ActivityModerationResource::getUrl('view', ['record' => $activity]),
                    ])
            );
        }

        $packageResource = 'App\\Modules\\Administration\\Filament\\Resources\\PackageModerationResource';
        if ($user?->can('packages.view') && class_exists($packageResource)) {
            $items = $items->concat(
                Package::query()
                    ->with('agent')
                    ->where('status', 'pending_review')
                    ->oldest('submitted_at')
                    ->limit(self::LIMIT)
                    ->get()
                    ->map(fn (Package $package) => [
                        'kind' => 'Journey',
                        'name' => $package->name,
                        'by' => $package->agent?->agency_name,
                        'waiting' => $package->submitted_at,
                        'url' => $packageResource::getUrl('view', ['record' => $package]),
                    ])
            );
        }

        /** @var Collection $items */
        $items = $items->sortBy(fn ($item) => $item['waiting']?->timestamp ?? PHP_INT_MAX)->values();

        return [
            'items' => $items->take(self::LIMIT),
            'total' => $items->count(),
        ];
    }
}
