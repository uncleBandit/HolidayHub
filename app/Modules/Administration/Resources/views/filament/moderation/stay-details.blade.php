@php
    use App\Modules\Accommodation\Domain\Models\BedAndBreakfast;
    use App\Modules\Accommodation\Domain\Models\Hotel;
    use App\Modules\Accommodation\Domain\Models\Villa;
    use App\Support\Currencies;

    $property = $getRecord()->bookable;
    $currency = $property?->currency;
    $yes = fn ($value) => $value === null ? '—' : ($value ? 'Yes' : 'No');

    $facts = match (true) {
        $property instanceof Hotel => [
            'Stars' => $property->stars ? str_repeat('★', (int) $property->stars) : '—',
            'Currency' => $currency ?: '—',
            'From (per night)' => $property->avg_price_per_night ? Currencies::format($property->avg_price_per_night, $currency, 2) : '—',
        ],
        $property instanceof Villa => [
            'Nightly price' => $property->avg_price_per_night ? Currencies::format($property->avg_price_per_night, $currency, 2) : '—',
            'Currency' => $currency ?: '—',
            'Bedrooms' => $property->bedrooms ?? '—',
            'Bathrooms' => $property->bathrooms ?? '—',
            'Max guests' => $property->max_guests ?? '—',
            'Private pool' => $yes($property->has_private_pool),
            'Size' => $property->size_sqm ? $property->size_sqm.' m²' : '—',
            'Minimum nights' => $property->min_nights ?? '—',
        ],
        $property instanceof BedAndBreakfast => [
            'Nightly price' => $property->price_per_night ? Currencies::format($property->price_per_night, $currency, 2) : '—',
            'Currency' => $currency ?: '—',
            'Rooms' => $property->rooms ?? '—',
            'Max guests' => $property->max_guests ?? '—',
            'Breakfast' => $yes($property->has_breakfast),
            'Breakfast details' => is_array($property->breakfast_details)
                ? (collect($property->breakfast_details)->flatten()->filter()->implode(', ') ?: '—')
                : ($property->breakfast_details ?: '—'),
        ],
        default => [],
    };

    $roomTypes = $property instanceof Hotel && $property->relationLoaded('roomTypes') ? $property->roomTypes : collect();
@endphp

@if (! $property)
    <p class="text-sm text-danger-600">The underlying property record is missing.</p>
@else
    <dl style="display:grid;grid-template-columns:repeat(auto-fill,minmax(11rem,1fr));gap:.75rem 1.25rem;margin:0">
        @foreach ($facts as $term => $value)
            <div>
                <dt style="font-size:.72rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase" class="text-gray-500 dark:text-gray-400">{{ $term }}</dt>
                <dd style="margin:0;font-weight:600" class="text-gray-950 dark:text-white">{{ $value }}</dd>
            </div>
        @endforeach
    </dl>

    @if ($property instanceof Hotel)
        <div style="margin-top:1.25rem">
            <div style="font-size:.72rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;margin-bottom:.4rem" class="text-gray-500 dark:text-gray-400">Room types ({{ $roomTypes->count() }})</div>
            @if ($roomTypes->isEmpty())
                <p class="text-sm text-warning-600">No room types yet, so nothing can be booked.</p>
            @else
                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse;font-size:.875rem">
                        <thead>
                            <tr class="text-gray-500 dark:text-gray-400" style="text-align:left;font-size:.72rem;text-transform:uppercase;letter-spacing:.05em">
                                <th style="padding:.4rem .5rem">Room type</th>
                                <th style="padding:.4rem .5rem;text-align:right">Price / night</th>
                                <th style="padding:.4rem .5rem">Sleeps</th>
                                <th style="padding:.4rem .5rem">Beds</th>
                                <th style="padding:.4rem .5rem">Size</th>
                                <th style="padding:.4rem .5rem">Breakfast</th>
                                <th style="padding:.4rem .5rem">Refundable</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($roomTypes as $roomType)
                                <tr class="border-t border-gray-100 dark:border-white/10">
                                    <td style="padding:.5rem;font-weight:600" class="text-gray-950 dark:text-white">
                                        {{ $roomType->name }}
                                        @if ($roomType->view)
                                            <span style="font-weight:400" class="text-gray-500"> · {{ $roomType->view }}</span>
                                        @endif
                                    </td>
                                    <td style="padding:.5rem;text-align:right;white-space:nowrap" class="{{ (float) $roomType->price_per_night > 0 ? 'text-gray-950 dark:text-white' : 'text-danger-600' }}">
                                        {{ Currencies::format($roomType->price_per_night, $roomType->currency ?: $currency, 2) }}
                                        <span class="text-gray-500" style="font-size:.75rem">{{ $roomType->currency ?: $currency }}</span>
                                    </td>
                                    <td style="padding:.5rem" class="text-gray-600 dark:text-gray-300">
                                        {{ $roomType->capacity ?? '—' }}
                                        @if ($roomType->max_adults !== null)
                                            <span class="text-gray-500" style="font-size:.75rem">({{ $roomType->max_adults }} adults, {{ $roomType->max_children ?? 0 }} children)</span>
                                        @endif
                                    </td>
                                    <td style="padding:.5rem" class="text-gray-600 dark:text-gray-300">{{ $roomType->bedSummary() ?: ($roomType->beds ? $roomType->beds.' '.\Illuminate\Support\Str::plural('bed', $roomType->beds) : '—') }}</td>
                                    <td style="padding:.5rem" class="text-gray-600 dark:text-gray-300">{{ $roomType->size_sqm ? $roomType->size_sqm.' m²' : '—' }}</td>
                                    <td style="padding:.5rem" class="text-gray-600 dark:text-gray-300">{{ $yes($roomType->breakfast_included) }}</td>
                                    <td style="padding:.5rem" class="text-gray-600 dark:text-gray-300">{{ $yes($roomType->refundable) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif
@endif
