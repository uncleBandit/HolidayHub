<div>
    {{-- ────────────────────────────  Header  ───────────────────────────── --}}
    <section class="dv-section dv-foryou-head pb-8 md:pb-10">
        <div class="relative grid items-end gap-10 lg:grid-cols-[1.25fr_0.75fr]">
            <div>
                <p class="dv-eyebrow dv-magic-in" style="--i: 0">For you</p>
                <h1 class="dv-magic-in mt-3 font-dm-serif text-[clamp(2.6rem,6vw,5.2rem)] leading-[0.95] tracking-[-0.03em] text-forest" style="--i: 1">
                    Fresh finds for you,<br><span class="italic text-clay">{{ $firstName }}.</span>
                </h1>
                <p class="dv-magic-in mt-5 max-w-[36rem] text-[14px] leading-relaxed text-muted" style="--i: 2">{{ $tuning }}</p>
            </div>

            @if ($nextTrip)
                <a href="{{ $nextTrip['url'] }}" class="dv-magic-in group block w-full max-w-[26rem] overflow-hidden rounded-[1.1rem] border border-line bg-white shadow-[0_20px_60px_rgba(20,36,28,0.12)] lg:ml-auto" style="--i: 3">
                    <div class="grid grid-cols-[5.5rem_1fr] items-center gap-4 p-4">
                        <img src="{{ $nextTrip['image'] }}" alt="{{ $nextTrip['name'] }}" class="h-[5rem] w-full rounded-[0.7rem] object-cover">
                        <div class="min-w-0">
                            <p class="flex items-center justify-between gap-2 text-[10px] font-extrabold uppercase tracking-[0.09em] text-clay">
                                Your next escape
                                @if ($nextTrip['countdown'])
                                    <span class="rounded-full bg-forest px-2 py-0.5 normal-case tracking-normal text-white">
                                        {{ $nextTrip['countdown'] === 1 ? 'Tomorrow' : 'In '.$nextTrip['countdown'].' days' }}
                                    </span>
                                @endif
                            </p>
                            <p class="mt-1 truncate font-dm-serif text-[1.25rem] leading-tight text-ink">{{ $nextTrip['name'] }}</p>
                            <p class="mt-0.5 truncate text-[11px] text-muted">{{ $nextTrip['dates'] }}@if ($nextTrip['place']) · {{ $nextTrip['place'] }}@endif</p>
                        </div>
                    </div>
                    <div class="flex items-center justify-between border-t border-dashed border-line bg-cream px-4 py-2.5 text-[11px] font-extrabold text-forest">
                        <span>{{ $nextTrip['code'] }} · {{ $nextTrip['total'] }}</span>
                        <span class="transition-transform duration-200 group-hover:translate-x-1"><x-line-icon name="arrow" :size="16" /></span>
                    </div>
                </a>
            @endif
        </div>

        {{-- Mood stories --}}
        <div class="dv-magic-in -mx-5 mt-10 flex gap-5 overflow-x-auto px-5 pb-2 no-scrollbar md:mx-0 md:px-0" style="--i: 4" aria-label="Browse by mood">
            @foreach ($moods as $key => $mood)
                <a href="{{ $mood['url'] }}" class="dv-story" title="{{ $mood['line'] }}">
                    <span class="dv-story-ring"><img src="{{ $mood['image'] }}" alt="" loading="lazy"></span>
                    <span class="mt-2 block max-w-[5.5rem] truncate text-center text-[11px] font-bold text-ink">{{ $mood['label'] }}</span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ─────────────────────────  Sticky filters  ──────────────────────── --}}
    <div class="dv-foryou-filters">
        <div class="dv-category-row" role="tablist" aria-label="Feed filters" data-no-reveal>
            @foreach ($filters as $key => $item)
                <button
                    type="button"
                    role="tab"
                    wire:click="setFilter('{{ $key }}')"
                    @class(['dv-category py-3', 'dv-category-active' => $filter === $key])
                    aria-selected="{{ $filter === $key ? 'true' : 'false' }}"
                >
                    <x-line-icon :name="$item['icon']" :size="16" /> {{ $item['label'] }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- ─────────────────────────────  Feed  ────────────────────────────── --}}
    <section class="dv-section pt-8" wire:loading.class="opacity-60" wire:target="setFilter">
        @if ($items->isEmpty())
            <div class="flex flex-col items-center rounded-[1.2rem] border border-dashed border-line bg-surface px-6 py-16 text-center">
                <span class="grid h-14 w-14 place-items-center rounded-full bg-cream text-clay">
                    <x-line-icon :name="$filters[$filter]['icon']" :size="24" />
                </span>
                <p class="mt-4 font-dm-serif text-[1.6rem] text-forest">
                    @switch($filter)
                        @case('near') Nothing near you just yet @break
                        @case('trending') Nothing trending yet @break
                        @default No {{ Str::lower($filters[$filter]['label']) }} yet
                    @endswitch
                </p>
                <p class="mt-1.5 max-w-[26rem] text-[13px] leading-relaxed text-muted">
                    @if ($filter === 'near')
                        Add your city to your profile and we’ll surface stays and experiences close to home.
                    @else
                        New listings land every week. In the meantime, your full feed is full of ideas.
                    @endif
                </p>
                <button type="button" wire:click="setFilter('for-you')" class="dv-btn dv-btn-primary mt-6">Back to For you</button>
            </div>
        @else
            <div class="columns-1 gap-4 sm:columns-2 lg:columns-3 2xl:columns-4">
                @foreach ($items as $i => $item)
                    {{-- Trending chart, slotted into the mixed feed --}}
                    @if ($i === 3 && $trending->count() >= 3)
                        <div class="mb-4 break-inside-avoid" wire:key="trending-chart">
                            <div class="dv-panel p-5">
                                <p class="dv-eyebrow">Trending this week</p>
                                <p class="mt-1 font-dm-serif text-[1.5rem] leading-tight text-forest">What travellers are loving</p>
                                <ol class="mt-4 space-y-3">
                                    @foreach ($trending as $rank => $hit)
                                        <li>
                                            <a href="{{ $hit['url'] }}" class="group flex items-center gap-3">
                                                <span class="w-5 flex-none font-dm-serif text-[1.3rem] text-clay">{{ $rank + 1 }}</span>
                                                <img src="{{ $hit['image'] }}" alt="" class="h-11 w-11 flex-none rounded-[0.6rem] object-cover" loading="lazy">
                                                <span class="min-w-0">
                                                    <span class="block truncate text-[13px] font-bold text-ink transition-colors group-hover:text-clay">{{ $hit['title'] }}</span>
                                                    <span class="block truncate text-[11px] text-muted">{{ $hit['sub'] }}</span>
                                                </span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ol>
                            </div>
                        </div>
                    @endif

                    {{-- Mood of the week --}}
                    @if ($i === 8 && $filter === 'for-you')
                        @php $weekly = $moods->values()[now()->weekOfYear % $moods->count()]; @endphp
                        <div class="mb-4 break-inside-avoid" wire:key="mood-of-week">
                            <a href="{{ $weekly['url'] }}" class="dv-magic-night group block overflow-hidden rounded-[1.2rem] p-7 text-white">
                                <p class="dv-eyebrow-light">Mood of the week</p>
                                <p class="mt-3 font-dm-serif text-[1.7rem] italic leading-[1.15]">“{{ $weekly['line'] }}”</p>
                                <span class="mt-5 inline-flex items-center gap-2 text-[12px] font-extrabold text-[#F6D6C9]">
                                    Explore {{ $weekly['label'] }}
                                    <span class="transition-transform duration-200 group-hover:translate-x-1"><x-line-icon name="arrow" :size="16" /></span>
                                </span>
                            </a>
                        </div>
                    @endif

                    <div class="mb-4 break-inside-avoid" wire:key="{{ $item['key'] }}">
                        @if ($item['type'] === 'reel')
                            <a href="{{ $item['url'] }}" class="dv-reel-card group block aspect-[9/14] w-full">
                                @if ($item['video'])
                                    <video src="{{ $item['video'] }}" poster="{{ $item['image'] }}" muted loop playsinline preload="none" data-autoplay></video>
                                @else
                                    <img src="{{ $item['image'] }}" alt="{{ $item['title'] }}" loading="lazy">
                                @endif
                                <span class="dv-foryou-reason"><x-line-icon :name="$item['reason']['icon']" :size="13" /> {{ $item['reason']['label'] }}</span>
                                <span class="dv-reel-play !left-auto right-4"><x-line-icon name="play" :size="14" filled /></span>
                                <div class="dv-reel-card-body">
                                    <p class="dv-card-kicker">{{ $item['sub'] }}</p>
                                    <p class="dv-card-name text-[1.35rem]">{{ $item['title'] }}</p>
                                </div>
                            </a>
                        @else
                            <a
                                href="{{ $item['url'] }}"
                                @class(['dv-card group block', ['aspect-[4/5]', 'aspect-square', 'aspect-[3/4]', 'aspect-[5/6]'][$i % 4]])
                            >
                                <img src="{{ $item['image'] }}" alt="{{ $item['title'] }}" loading="lazy">
                                <div class="dv-card-gradient"></div>
                                <span class="dv-foryou-reason"><x-line-icon :name="$item['reason']['icon']" :size="13" /> {{ $item['reason']['label'] }}</span>
                                <div class="dv-card-body">
                                    @if ($item['kicker'])
                                        <p class="dv-card-kicker">{{ $item['kicker'] }}</p>
                                    @endif
                                    <p class="dv-card-name">{{ $item['title'] }}</p>
                                    @if ($item['sub'])
                                        <p class="dv-card-sub line-clamp-1">{{ $item['sub'] }}</p>
                                    @endif
                                    @if ($item['price'] || $item['rating'])
                                        <p class="mt-2.5 flex items-center gap-3 text-[11px] font-extrabold">
                                            @if ($item['rating'])
                                                <span class="flex items-center gap-1"><x-line-icon name="star" :size="12" filled /> {{ $item['rating'] }}</span>
                                            @endif
                                            @if ($item['price'])
                                                <span>{{ $item['price'] }}</span>
                                            @endif
                                        </p>
                                    @endif
                                </div>
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>

            @if ($hasMore)
                <div class="mt-6 flex justify-center" x-data x-intersect.margin.400px="$wire.loadMore()">
                    <button type="button" wire:click="loadMore" class="dv-btn dv-btn-ghost">
                        <span wire:loading.remove wire:target="loadMore">Show more</span>
                        <span wire:loading wire:target="loadMore" class="inline-flex items-center gap-2">
                            <span class="inline-grid animate-spin"><x-line-icon name="compass" :size="16" /></span> Finding more…
                        </span>
                    </button>
                </div>
            @else
                <p class="mt-8 text-center text-[12px] font-semibold text-muted">
                    You’re all caught up. <a href="{{ route('discover') }}" class="font-extrabold text-clay hover:underline">Discover something new</a>
                </p>
            @endif
        @endif
    </section>

    {{-- Agent journeys (renders nothing when there are none). --}}
    @include('packages::partials.journeys-rail', ['journeysFeatured' => true, 'journeysEyebrow' => 'Journeys by our agents', 'journeysTitle' => 'Whole trips, booked in one go', 'journeysClass' => 'dv-section !pt-0'])
</div>
