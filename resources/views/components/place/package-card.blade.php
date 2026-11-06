@props(['package'])

{{-- A holiday package as a photo-led discovery card. --}}
@php
    $image = collect([$package->cover_image, (array) ($package->gallery ?? [])])
        ->flatten()
        ->first(fn ($candidate) => is_string($candidate)
            && filled($candidate)
            && ! \Illuminate\Support\Str::contains($candidate, ['placeholder', 'placehold', 'picsum', 'lorempixel', 'source.unsplash']));

    if ($image && ! \Illuminate\Support\Str::startsWith($image, ['http://', 'https://', '/'])) {
        $image = asset('storage/'.ltrim($image, '/'));
    }

    $image = $image ?: 'https://images.unsplash.com/photo-1664093671658-a2aac3a344a9?auto=format&fit=crop&w=1000&q=84';
    $price = (float) ($package->discount_price ?: $package->base_price);
    $days = (int) $package->duration_days;
    $nights = (int) $package->duration_nights;
@endphp

<a href="{{ route('packages-show', $package->slug) }}" {{ $attributes->merge(['class' => 'dv-card group']) }}>
    <img src="{{ $image }}" alt="{{ $package->name }}" loading="lazy" decoding="async">
    <span class="dv-card-gradient" aria-hidden="true"></span>

    @if ($days > 0 || $nights > 0)
        <span class="dv-reel-badge !left-4 !right-auto">
            <x-line-icon name="calendar" :size="12" />
            {{ collect([$days ? $days.' '.\Illuminate\Support\Str::plural('day', $days) : null, $nights ? $nights.' '.\Illuminate\Support\Str::plural('night', $nights) : null])->filter()->implode(' · ') }}
        </span>
    @endif

    <div class="dv-card-body pr-12">
        <p class="dv-card-kicker">Package{{ $package->destination ? ' · '.$package->destination->name : '' }}</p>
        <p class="dv-card-name">{{ $package->name }}</p>
        <p class="dv-card-sub">
            @if ($price > 0)
                From {{ $package->currency ?: 'KSh' }} {{ number_format($price) }}
            @else
                Ask the agent for rates
            @endif
        </p>
    </div>

    <span class="dv-card-arrow" aria-hidden="true">
        <x-line-icon name="arrow-up-right" :size="17" />
    </span>
</a>
