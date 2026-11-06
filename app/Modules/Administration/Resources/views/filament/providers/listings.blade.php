@php
    use App\Modules\Accommodation\Domain\Models\Accommodation;
    use App\Modules\Activities\Domain\Models\Activity;
    use App\Modules\Administration\Filament\Resources\AccommodationModerationResource;
    use App\Modules\Administration\Filament\Resources\ActivityModerationResource;

    $provider = $getRecord();
    $canStays = AccommodationModerationResource::canViewAny();
    $canExperiences = ActivityModerationResource::canViewAny();

    $rows = Accommodation::query()->where('provider_id', $provider->id)->orderBy('name')->get()
        ->map(fn (Accommodation $stay) => [
            'name' => $stay->name,
            'kind' => match ($stay->bookable_type) { 'villa' => 'Villa', 'bed_and_breakfast' => 'B&B', default => 'Hotel' },
            'status' => $stay->status?->value,
            'moderation' => $canStays ? AccommodationModerationResource::getUrl('view', ['record' => $stay]) : null,
            'public' => AccommodationModerationResource::publicUrl($stay),
        ])
        ->concat(Activity::query()->where('provider_id', $provider->id)->orderBy('name')->get()
            ->map(fn (Activity $activity) => [
                'name' => $activity->name,
                'kind' => 'Experience',
                'status' => $activity->status?->value,
                'moderation' => $canExperiences ? ActivityModerationResource::getUrl('view', ['record' => $activity]) : null,
                'public' => ActivityModerationResource::publicUrl($activity),
            ]));
@endphp
@include('administration::filament.partials.listing-rows', ['rows' => $rows, 'empty' => 'This provider has no listings yet.'])
