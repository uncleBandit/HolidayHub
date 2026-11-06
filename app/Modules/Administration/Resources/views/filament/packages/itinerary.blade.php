@php
    use App\Modules\Administration\Filament\Resources\AccommodationModerationResource;
    use App\Modules\Administration\Filament\Resources\ActivityModerationResource;
    use App\Modules\Packages\Domain\Models\PackageComponent;
    use App\Shared\Support\Currencies;

    $record = $getRecord();
    $review = \App\Modules\Administration\Filament\Resources\PackageModerationResource::review($record);
    $quote = $review['quote'];
    $lines = collect($quote['lines'] ?? [])->keyBy('component_id');
    $days = $record->components->groupBy(fn (PackageComponent $c) => (int) $c->day)->sortKeys();
    $kindColours = ['stay' => '#16392F', 'experience' => '#D85F3C', 'transfer' => '#3B4A6B', 'free' => '#6E9C83', 'note' => '#8A8A80'];

    // Where an admin goes to review the listing behind a component.
    $reviewUrl = function (PackageComponent $c) {
        $listing = $c->componentable;
        if (! $listing) {
            return null;
        }
        if ($listing instanceof \App\Modules\Activities\Domain\Models\Activity) {
            return ActivityModerationResource::canViewAny() ? ActivityModerationResource::getUrl('view', ['record' => $listing]) : null;
        }
        $owner = $listing instanceof \App\Modules\Accommodation\Domain\Models\RoomType ? $listing->hotel : $listing;
        $canonical = $owner?->accommodation?->id;

        return $canonical && AccommodationModerationResource::canViewAny() ? AccommodationModerationResource::getUrl('view', ['record' => $canonical]) : null;
    };
@endphp

@if ($days->isEmpty())
    <p class="text-sm text-warning-600">This journey has no days planned yet.</p>
@else
    <div style="display:flex;flex-direction:column;gap:1rem">
        @foreach ($days as $day => $components)
            <div>
                <div class="text-gray-500 dark:text-gray-400" style="font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;margin-bottom:.35rem">Day {{ $day }}</div>
                <ul style="display:flex;flex-direction:column;gap:.35rem">
                    @foreach ($components->sortBy('sequence') as $component)
                        @php
                            $line = $lines[$component->id] ?? null;
                            $url = $reviewUrl($component);
                            $name = $component->title ?: ($component->componentable->name ?? null) ?: str($component->kind)->headline();
                        @endphp
                        <li class="ring-1 ring-gray-950/5 dark:ring-white/10" style="display:flex;align-items:center;gap:.75rem;padding:.6rem .75rem;border-radius:.6rem">
                            <span style="flex:none;min-width:5.5rem;text-align:center;padding:.12rem .5rem;border-radius:999px;font-size:.68rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#fff;background:{{ $kindColours[$component->kind] ?? '#8A8A80' }}">{{ $component->kind }}</span>
                            <span style="flex:1;min-width:0">
                                @if ($url)
                                    <a href="{{ $url }}" class="font-medium text-primary-600 hover:underline dark:text-primary-400">{{ $name }}</a>
                                @else
                                    <span class="font-medium text-gray-950 dark:text-white">{{ $name }}</span>
                                @endif
                                <span class="block text-xs text-gray-500 dark:text-gray-400">
                                    @if ($component->kind === 'stay') {{ $component->nights }} {{ str('night')->plural((int) $component->nights) }} @endif
                                    @if ($component->kind === 'stay' && $component->componentable instanceof \App\Modules\Accommodation\Domain\Models\RoomType) · {{ $component->componentable->hotel->name ?? 'Hotel' }} @endif
                                    @if ($component->activityOption) · {{ $component->activityOption->name }} @endif
                                    @if ($component->componentable && method_exists($component->componentable, 'getAttribute') && $component->isListing() && ($component->componentable->status ?? null))
                                        · listing {{ str(is_object($component->componentable->status) ? $component->componentable->status->value : $component->componentable->status)->replace('_', ' ') }}
                                    @endif
                                    @if ($line && ! ($line['available'] ?? true)) · <span class="text-danger-600">{{ $line['reason'] ?? 'unavailable on the sample date' }}</span> @endif
                                </span>
                            </span>
                            <span style="flex:none;text-align:right;font-size:.85rem" class="text-gray-700 dark:text-gray-200">
                                @if ($line && isset($line['amount']))
                                    {{ Currencies::format($line['amount'], $quote['currency']) }}
                                    @if (! empty($line['converted']) && isset($line['listing_amount']))
                                        <span class="block text-xs text-gray-500">from {{ Currencies::format($line['listing_amount'], $line['listing_currency'] ?? null) }}</span>
                                    @endif
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach

        @if ($quote)
            <div class="bg-gray-50 dark:bg-white/5" style="border-radius:.6rem;padding:.8rem 1rem;font-size:.875rem;display:grid;grid-template-columns:1fr auto;gap:.25rem 1rem">
                <span class="text-gray-500">Sample: {{ $quote['guests'] }} guests from {{ \Illuminate\Support\Carbon::parse($quote['start'])->format('j M Y') }}</span><span></span>
                <span>Stays and experiences</span><span style="text-align:right">{{ Currencies::format($quote['listing_subtotal'], $quote['currency']) }}</span>
                <span>Agent's extras</span><span style="text-align:right">{{ Currencies::format($quote['extras_subtotal'], $quote['currency']) }}</span>
                <span>Agent margin</span><span style="text-align:right">{{ Currencies::format($quote['margin'], $quote['currency']) }}</span>
                <span class="font-semibold">Guest pays</span><span class="font-semibold" style="text-align:right">{{ Currencies::format($quote['total'], $quote['currency']) }}</span>
                @if (! $quote['available'])
                    <span class="text-danger-600" style="grid-column:1/-1">Not bookable on this date: {{ collect($quote['errors'])->implode(' ') ?: $quote['unavailable'].' part(s) unavailable.' }}</span>
                @endif
                @if ($quote['converted'])
                    <span class="text-xs text-gray-500" style="grid-column:1/-1">Some prices are converted from the listing's currency at approximate rates.</span>
                @endif
            </div>
        @elseif ($review['error'])
            <p class="text-sm text-danger-600">The live quote failed: {{ $review['error'] }}</p>
        @endif
    </div>
@endif
