@props(['activity'])

{{-- An experience (Activity) as a photo-led discovery card. Used on destination pages. --}}
@php
    $image = collect([$activity->thumbnail, (array) ($activity->gallery ?? [])])
        ->flatten()
        ->first(fn ($candidate) => is_string($candidate)
            && filled($candidate)
            && ! \Illuminate\Support\Str::contains($candidate, ['placeholder', 'placehold', 'picsum', 'lorempixel']));

    if ($image && ! \Illuminate\Support\Str::startsWith($image, ['http://', 'https://', '/'])) {
        $image = asset('storage/'.ltrim($image, '/'));
    }

    $image = $image ?: 'https://images.unsplash.com/photo-1585402969048-86f38f8bfc2e?auto=format&fit=crop&w=1000&q=84';
    $minutes = (int) $activity->duration_minutes;
    $duration = $minutes <= 0
        ? 'Flexible'
        : ($minutes >= 60 ? rtrim(rtrim(number_format($minutes / 60, 1), '0'), '.').' hr' : $minutes.' min');
    $rating = (float) $activity->rating;
@endphp

<a href="{{ route('activity.show', $activity->slug) }}" {{ $attributes->merge(['class' => 'dv-card group']) }}>
    <img src="{{ $image }}" alt="{{ $activity->name }}" loading="lazy" decoding="async">
    <span class="dv-card-gradient" aria-hidden="true"></span>

    @if ($rating > 0)
        <span class="dv-reel-badge !left-4 !right-auto">
            <x-line-icon name="star" :size="12" :filled="true" />
            {{ number_format($rating, 1) }}
        </span>
    @endif

    <span class="dv-card-mark" aria-hidden="true">
        <x-line-icon name="clock" :size="16" />
    </span>

    <div class="dv-card-body pr-12">
        <p class="dv-card-kicker">{{ $duration }}</p>
        <p class="dv-card-name">{{ $activity->name }}</p>
        <p class="dv-card-sub">
            @if ((float) $activity->base_price > 0)
                From {{ $activity->currency ?: 'KSh' }} {{ number_format((float) $activity->base_price) }} per person
            @else
                Ask the host for rates
            @endif
        </p>
    </div>

    <span class="dv-card-arrow" aria-hidden="true">
        <x-line-icon name="arrow-up-right" :size="17" />
    </span>
</a>
