{{-- Stay detail title block: host kicker, serif title, rating + location meta,
     and the floating facts strip that overlaps the gallery. --}}
@props([
    'name',
    'kind' => 'Stay',
    'host' => null,
    'verified' => false,
    'rating' => 0,
    'reviews' => 0,
    'location' => null,
    'facts' => [],
])

@php
    $rating = (float) $rating;
    $reviews = (int) $reviews;
    $initials = collect(preg_split('/\s+/', trim((string) ($host ?: $name))))
        ->filter()
        ->take(2)
        ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
        ->implode('');
@endphp

<div class="relative z-10 flex flex-col gap-8 lg:grid lg:grid-cols-[minmax(0,1fr)_minmax(0,30rem)] lg:items-start lg:gap-12">
    {{-- Floating facts strip --}}
    <dl class="dv-panel-shadow -mt-10 grid grid-cols-3 rounded-[1.1rem] bg-white px-2 py-5 sm:px-6 lg:order-2 lg:-mt-12" data-no-reveal>
        @foreach ($facts as $fact)
            <div @class(['flex min-w-0 flex-col items-center gap-1 px-2 text-center', 'border-r border-line' => ! $loop->last])>
                <dt class="order-2 text-[10.5px] font-semibold leading-tight text-muted sm:text-[11px]">{{ $fact['label'] }}</dt>
                <dd class="order-1 flex items-center gap-1 font-dm-serif text-[1.35rem] leading-none text-forest sm:text-[1.55rem]">
                    @if (! empty($fact['star']))
                        <x-line-icon name="star" :size="15" filled class="text-clay" />
                    @endif
                    {{ $fact['value'] }}
                </dd>
            </div>
        @endforeach
    </dl>

    <div class="min-w-0 lg:order-1 lg:pt-10">
        <div class="flex items-center gap-3">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-clay font-dm-serif text-[13px] text-white">{{ $initials ?: 'HH' }}</span>
            <p class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1 text-[12px] font-bold text-ink">
                <span class="truncate">{{ $host ? 'Hosted by '.$host : $kind }}</span>
                @if ($verified)
                    <span class="grid h-4 w-4 place-items-center rounded-full bg-forest-light text-white" title="Verified host">
                        <x-line-icon name="check" :size="10" :stroke="3" />
                    </span>
                @endif
                @if ($host)
                    <span class="dv-eyebrow !text-[10px]">{{ $kind }}</span>
                @endif
            </p>
        </div>

        <h1 class="mt-5 max-w-[50rem] font-dm-serif text-[clamp(2.2rem,4.6vw,4rem)] leading-[1.02] tracking-[-0.02em] text-forest">{{ $name }}</h1>

        <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-[13px] text-muted">
            <span class="inline-flex items-center gap-1.5">
                <x-line-icon name="star" :size="15" filled class="text-clay" />
                @if ($rating > 0)
                    <b class="text-ink">{{ number_format($rating, 1) }}</b>
                    ({{ number_format($reviews) }} {{ \Illuminate\Support\Str::plural('review', $reviews) }})
                @else
                    New on HolidayHub
                @endif
            </span>
            @if ($location)
                <span class="inline-flex items-center gap-1.5">
                    <x-line-icon name="map" :size="15" class="text-clay" />
                    {{ $location }}
                </span>
            @endif
        </div>
    </div>
</div>
