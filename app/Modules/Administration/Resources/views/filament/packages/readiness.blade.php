@php
    $review = \App\Modules\Administration\Filament\Resources\PackageModerationResource::review($getRecord());
@endphp
@if ($review['requirements'] === [])
    <p class="text-sm text-danger-600">The checklist could not be worked out for this journey.</p>
@else
    <ul style="display:grid;grid-template-columns:repeat(auto-fit,minmax(17rem,1fr));gap:.5rem 1.25rem">
        @foreach ($review['requirements'] as $item)
            <li style="display:flex;gap:.55rem;align-items:flex-start;font-size:.875rem">
                <x-filament::icon :icon="$item['met'] ? 'heroicon-s-check-circle' : 'heroicon-s-exclamation-circle'"
                    @class(['text-success-600' => $item['met'], 'text-warning-600' => ! $item['met']])
                    style="width:1.15rem;height:1.15rem;flex:none;margin-top:.1rem" />
                <span>
                    <span class="font-medium text-gray-950 dark:text-white">{{ $item['label'] }}</span>
                    @if (! $item['met'] && filled($item['hint'] ?? null))
                        <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $item['hint'] }}</span>
                    @endif
                </span>
            </li>
        @endforeach
    </ul>
@endif
