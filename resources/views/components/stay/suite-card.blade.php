@props([
    'card',
    'tall' => false,
    // Photo only, for spreads that set their own caption beside it.
    'bare' => false,
])

{{-- Suite card: a quiet, editorial stay card. The photograph sits above a calm
     caption; on hover the stay's reel footage dissolves in slowly. On touch
     screens resources/js/motion.js replays the hover as the card scrolls in. --}}
<article
    {{ $attributes->merge(['class' => 'group']) }}
    data-video-card
    x-data="{ hover: false }"
    x-on:mouseenter="if (! window.matchMedia('(prefers-reduced-motion: reduce)').matches && $refs.reel) { hover = true; $refs.reel.play().catch(() => {}) }"
    x-on:mouseleave="hover = false; $refs.reel && $refs.reel.pause()"
>
    <a href="{{ $card['url'] }}" @class([
        'relative block overflow-hidden rounded-[1.4rem] bg-forest shadow-[0_24px_60px_-30px_rgba(20,36,28,0.55)] transition-shadow duration-700 group-hover:shadow-[0_40px_80px_-30px_rgba(20,36,28,0.7)]',
        'aspect-[4/5]' => ! $tall,
        'aspect-[3/4] lg:aspect-[4/6]' => $tall,
    ])>
        <img
            src="{{ $card['image'] }}"
            alt="{{ $card['alt'] }}"
            loading="lazy"
            decoding="async"
            class="absolute inset-0 h-full w-full object-cover transition-[transform,opacity] duration-[1600ms] ease-out group-hover:scale-[1.06]"
            x-bind:class="hover && 'opacity-0'"
        >

        @if ($card['video'])
            <video
                x-ref="reel"
                class="absolute inset-0 h-full w-full object-cover opacity-0 transition-opacity duration-[1200ms]"
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

        <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(180deg,rgba(12,21,17,0.35)_0%,transparent_30%,transparent_70%,rgba(12,21,17,0.45)_100%)]"></div>

        <div class="absolute inset-x-4 top-4 flex items-start justify-between gap-3">
            <span class="rounded-full border border-white/25 bg-[#0C1511]/40 px-3 py-1.5 text-[9px] font-extrabold uppercase tracking-[0.22em] text-[#F3E4C4] backdrop-blur-md">{{ $card['type_label'] }}</span>

            @if ($card['rating'] > 0)
                <span class="flex items-center gap-1 rounded-full bg-white/90 px-2.5 py-1.5 text-[10px] font-extrabold text-forest backdrop-blur-md">
                    <span class="text-[#B8925A]"><x-line-icon name="star" :size="11" filled /></span>
                    {{ number_format($card['rating'], 1) }}
                </span>
            @endif
        </div>

        @if ($card['video'])
            <span class="absolute bottom-4 left-4 inline-flex items-center gap-1.5 rounded-full border border-white/25 bg-black/30 px-2.5 py-1.5 text-[9px] font-extrabold uppercase tracking-[0.14em] text-white/90 opacity-0 backdrop-blur-md transition-opacity duration-500 group-hover:opacity-100">
                <x-line-icon name="play" :size="10" filled /> Now playing
            </span>
        @endif
    </a>

    @unless ($bare)
    <div class="px-1 pt-5">
        <p class="text-[10px] font-extrabold uppercase tracking-[0.22em] text-[#A8834F]">
            {{ $card['type_label'] }} · {{ $card['place'] }}
        </p>
        <h3 class="mt-2 font-dm-serif text-[1.55rem] leading-[1.1] text-forest">
            <a href="{{ $card['url'] }}" class="bg-[linear-gradient(currentColor,currentColor)] bg-[length:0%_1px] bg-left-bottom bg-no-repeat transition-[background-size] duration-500 group-hover:bg-[length:100%_1px]">{{ $card['name'] }}</a>
        </h3>

        @if ($card['caption'])
            <p class="mt-2 line-clamp-2 text-[13px] leading-relaxed text-muted">{{ $card['caption'] }}</p>
        @endif

        <div class="mt-4 flex items-end justify-between gap-4 border-t border-line pt-4">
            <p class="text-[12px] text-muted">
                @if ($card['price'] > 0)
                    <span class="block text-[10px] font-bold uppercase tracking-[0.14em]">From</span>
                    <span class="font-dm-serif text-[1.3rem] text-ink">{{ $card['currency'] }} {{ number_format($card['price']) }}</span> / night
                @else
                    <span class="font-dm-serif text-[1.15rem] text-ink">Rates on request</span>
                @endif
            </p>
            <p class="text-right text-[11px] text-muted">
                @if ($card['guests'] > 0)
                    Up to {{ $card['guests'] }} guests<br>
                @endif
                @if ($card['reviews'] > 0)
                    {{ number_format($card['reviews']) }} {{ Str::plural('review', $card['reviews']) }}
                @endif
            </p>
        </div>
    </div>
    @endunless
</article>
