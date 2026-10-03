@props(['card', 'featured' => false])

@php
    $tag = $card['is_record'] ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if ($card['is_record']) href="{{ $card['url'] }}" @else role="group" @endif
    @class(['group dv-card', 'md:row-span-2' => $featured])
>
    <img
        src="{{ $card['image'] }}"
        alt="{{ $card['alt'] }}"
        loading="{{ $featured ? 'eager' : 'lazy' }}"
    >

    <div class="dv-card-gradient" aria-hidden="true"></div>

    <div class="dv-card-body">
        <p class="dv-card-kicker">{{ $card['kicker'] }}</p>
        <p class="dv-card-name">{{ $card['name'] }}</p>

        @if ($card['sub'])
            <p class="dv-card-sub">
                <x-line-icon name="star" :size="12" filled />
                {{ $card['sub'] }}
            </p>
        @endif

        @if ($card['meta'])
            <p class="mt-2 hidden max-w-[22rem] text-[11px] leading-[1.5] text-white/65 md:block">
                {{ $card['meta'] }}
            </p>
        @endif
    </div>

    <span class="dv-card-arrow" aria-hidden="true">
        <x-line-icon name="arrow-up-right" :size="16" />
    </span>
</{{ $tag }}>