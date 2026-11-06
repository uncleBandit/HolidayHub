{{-- Location panel plus house policies. --}}
@props([
    'address' => null,
    'city' => null,
    'country' => null,
    'latitude' => null,
    'longitude' => null,
    'policies' => [],
])

@php
    $place = collect([$city, $country])->filter()->implode(', ');
    $hasCoords = is_numeric($latitude) && is_numeric($longitude) && ((float) $latitude !== 0.0 || (float) $longitude !== 0.0);
    $mapsUrl = $hasCoords
        ? 'https://www.google.com/maps/search/?api=1&query='.$latitude.','.$longitude
        : (filled($address) || filled($place) ? 'https://www.google.com/maps/search/?api=1&query='.urlencode(collect([$address, $place])->filter()->implode(', ')) : null);

    $policies = is_array($policies) ? $policies : (json_decode((string) $policies, true) ?: []);
    $labels = [
        'check_in' => ['Check-in', 'clock', 'From '],
        'check_out' => ['Check-out', 'clock', 'By '],
        'cancellation' => ['Cancellation', 'calendar', ''],
        'children' => ['Children', 'users', ''],
        'pets' => ['Pets', 'leaf', ''],
        'smoking' => ['Smoking', 'compass', ''],
    ];
    $rules = collect($policies)
        ->filter(fn ($value) => is_scalar($value) && filled($value))
        ->map(fn ($value, $key) => [
            'label' => $labels[$key][0] ?? \Illuminate\Support\Str::headline((string) $key),
            'icon' => $labels[$key][1] ?? 'check',
            'value' => ($labels[$key][2] ?? '').$value,
        ]);
@endphp

<div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]">
    <div class="dv-magic-night relative flex min-h-[15rem] flex-col justify-between overflow-hidden rounded-[1.2rem] p-7 text-white">
        <span class="grid h-12 w-12 place-items-center rounded-full border border-white/25 bg-white/10 text-white backdrop-blur-md">
            <x-line-icon name="map" :size="20" />
        </span>
        <div class="mt-8">
            <p class="dv-eyebrow-light">Where you'll be</p>
            <p class="mt-2 font-dm-serif text-[1.7rem] leading-tight">{{ $place ?: 'Location shared after booking' }}</p>
            @if (filled($address))
                <p class="mt-1 text-[13px] text-white/70">{{ $address }}</p>
            @endif
            @if ($mapsUrl)
                <a href="{{ $mapsUrl }}" target="_blank" rel="noopener" class="mt-5 inline-flex items-center gap-2 rounded-full border border-white/30 bg-white/10 px-4 py-2 text-[11px] font-extrabold text-white backdrop-blur-md transition hover:bg-white/20">
                    Open in maps <x-line-icon name="arrow-up-right" :size="14" />
                </a>
            @endif
        </div>
    </div>

    <div class="rounded-[1.2rem] border border-line bg-surface p-6">
        <p class="font-dm-serif text-[1.35rem] text-forest">House rules</p>
        @if ($rules->isNotEmpty())
            <ul class="mt-4 divide-y divide-line">
                @foreach ($rules as $rule)
                    <li class="flex gap-3 py-3 text-[13px]">
                        <x-line-icon :name="$rule['icon']" :size="17" class="mt-0.5 text-clay" />
                        <p class="min-w-0"><b class="text-ink">{{ $rule['label'] }}</b> <span class="block text-muted">{{ $rule['value'] }}</span></p>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="mt-3 text-[13px] leading-relaxed text-muted">The host hasn't published house rules yet. Check-in details arrive with your booking confirmation.</p>
        @endif
    </div>
</div>
