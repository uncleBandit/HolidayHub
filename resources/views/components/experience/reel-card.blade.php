@props([
    'card',
])

{{-- Reel card: the experience's own photograph with its reel footage crossfaded
     in on hover, so the browse view stays photography-led while the section
     keeps its video character. --}}
<article
    class="dv-reel-card"
    x-data="{ hover: false }"
    x-on:mouseenter="if (! window.matchMedia('(prefers-reduced-motion: reduce)').matches && $refs.reel) { hover = true; $refs.reel.play().catch(() => {}) }"
    x-on:mouseleave="hover = false; $refs.reel && $refs.reel.pause()"
>
    <img
        src="{{ $card['image'] }}"
        alt="{{ $card['alt'] }}"
        loading="lazy"
        decoding="async"
        x-bind:class="hover ? 'opacity-0' : 'opacity-100'"
    >

    @if ($card['video'])
        <video
            x-ref="reel"
            class="opacity-0"
            x-bind:class="hover ? 'opacity-100' : 'opacity-0'"
            src="{{ $card['video'] }}"
            poster="{{ $card['poster'] }}"
            muted
            loop
            playsinline
            preload="none"
            disablepictureinpicture
            aria-hidden="true"
            tabindex="-1"
        ></video>
    @endif

    @if ($card['featured'])
        <span class="dv-reel-badge">
            <x-line-icon name="star" :size="12" :filled="true" />
            Guest favourite
        </span>
    @elseif ($card['rating'] > 0)
        <span class="dv-reel-badge">
            <x-line-icon name="star" :size="12" :filled="true" />
            {{ number_format($card['rating'], 1) }}
        </span>
    @endif

    <span class="dv-reel-duration">
        <x-line-icon name="clock" :size="12" />
        {{ $card['duration'] >= 60
            ? rtrim(rtrim(number_format($card['duration'] / 60, 1), '0'), '.').' hr'
            : $card['duration'].' min' }}
    </span>

    @if ($card['video'])
        <span class="dv-reel-play" aria-hidden="true">
            <x-line-icon name="play" :size="15" :filled="true" />
        </span>
    @endif

    <div class="dv-reel-card-body">
        <p class="dv-card-kicker">{{ $card['category_label'] }} · {{ $card['place'] }}</p>
        <h3 class="dv-card-name">
            <a href="{{ $card['url'] }}" class="after:absolute after:inset-0 after:content-['']">
                {{ $card['name'] }}
            </a>
        </h3>

        @if ($card['highlights'] !== [])
            <p class="dv-reel-card-highlights">
                {{ Illuminate\Support\Str::limit(implode(' · ', $card['highlights']), 62) }}
            </p>
        @endif

        <p class="dv-reel-card-price">
            @if ($card['price'] > 0)
                <span>{{ $card['currency'] }} {{ number_format($card['price']) }}</span>
                <span class="text-white/60">/ person</span>
            @else
                <span class="text-white/70">Free to join</span>
            @endif
        </p>

        <p class="dv-reel-meta">
            @if ($card['group'] > 0)
                <span>· Up to {{ $card['group'] }} per session</span>
            @endif

            @if ($card['min_age'] > 0)
                <span>· Age {{ $card['min_age'] }}+</span>
            @endif

            @if ($card['reviews'] > 0)
                <span>· {{ number_format($card['reviews']) }} {{ Illuminate\Support\Str::plural('review', $card['reviews']) }}</span>
            @endif
        </p>
    </div>
</article>