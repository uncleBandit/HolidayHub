@props([
    'card',
])

@php
    $minutes = (int) $card['duration'];
    $length = match (true) {
        $minutes <= 0 => null,
        $minutes >= 1440 => round($minutes / 1440).' '.Str::plural('day', (int) round($minutes / 1440)),
        $minutes >= 900 => 'Overnight',
        $minutes < 60 => $minutes.' min',
        default => rtrim(rtrim(number_format($minutes / 60, 1), '0'), '.').' hrs',
    };
@endphp

{{-- Moment card: an experience as a portrait still that comes alive. Its reel
     plays on hover (on touch screens resources/js/motion.js replays the hover
     as the card scrolls in) and its highlights rise up like a promise. --}}
<article
    {{ $attributes->merge(['class' => 'dv-reel-card group aspect-[3/4]']) }}
    x-data="{ hover: false }"
    x-on:mouseenter="if (! window.matchMedia('(prefers-reduced-motion: reduce)').matches && $refs.reel) { hover = true; $refs.reel.play().catch(() => {}) }"
    x-on:mouseleave="hover = false; $refs.reel && $refs.reel.pause()"
>
    <img src="{{ $card['image'] }}" alt="{{ $card['alt'] }}" loading="lazy" decoding="async" class="transition-opacity duration-700" x-bind:class="hover ? 'opacity-0' : 'opacity-100'">

    @if ($card['video'])
        <video
            x-ref="reel"
            class="opacity-0 transition-opacity duration-700"
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

    <div class="absolute inset-x-4 top-4 z-10 flex items-start justify-between gap-2">
        @if ($length)
            <span class="inline-flex items-center gap-1.5 rounded-full border border-white/25 bg-black/35 px-2.5 py-1.5 text-[10px] font-extrabold text-white backdrop-blur-md">
                <x-line-icon name="clock" :size="12" /> {{ $length }}
            </span>
        @else
            <span></span>
        @endif

        @if ($card['rating'] > 0)
            <span class="inline-flex items-center gap-1 rounded-full bg-white/90 px-2.5 py-1.5 text-[10px] font-extrabold text-forest">
                <span class="text-clay"><x-line-icon name="star" :size="11" filled /></span> {{ number_format($card['rating'], 1) }}
            </span>
        @endif
    </div>

    <div class="dv-reel-card-body">
        <p class="dv-card-kicker">{{ $card['category_label'] }} · {{ $card['place'] }}</p>
        <h3 class="mt-1.5 font-dm-serif text-[1.55rem] leading-[1.05] text-white">
            <a href="{{ $card['url'] }}" class="after:absolute after:inset-0 after:content-['']">{{ $card['name'] }}</a>
        </h3>

        @if ($card['highlights'])
            <div class="grid grid-rows-[0fr] opacity-0 transition-all duration-500 group-hover:grid-rows-[1fr] group-hover:opacity-100">
                <ul class="overflow-hidden">
                    @foreach ($card['highlights'] as $highlight)
                        <li class="mt-1.5 flex items-center gap-2 text-[11.5px] text-white/85"><span class="text-[#F6A27F]">✦</span> {{ $highlight }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <p class="mt-3 flex items-center justify-between gap-3 border-t border-white/20 pt-3 text-[11px] text-white/75">
            <span>
                @if ($card['price'] > 0)
                    From <b class="text-[13px] text-white">{{ in_array(strtoupper($card['currency']), ['KES', 'KSH']) ? 'KSh' : $card['currency'] }} {{ number_format($card['price']) }}</b>
                @else
                    Price on request
                @endif
            </span>
            @if ($card['min_age'] > 0)
                <span>Ages {{ $card['min_age'] }}+</span>
            @endif
        </p>
    </div>
</article>
