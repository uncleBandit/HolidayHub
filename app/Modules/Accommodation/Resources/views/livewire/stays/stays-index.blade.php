{{-- Visual Discovery stays: one front door for hotels, villas and B&Bs, presented
     as a browse grid of reel cards with a full-screen reel feed behind the same
     result set. --}}
<div>
    @if ($isReels)
        {{-- ────────────────────────  Full-screen feed  ───────────────────── --}}
        <section class="dv-reels-screen" aria-label="Stay reels">
            <div class="dv-feed">
                @forelse ($cards as $card)
                    <x-stay.reel-panel :card="$card" />
                @empty
                    <div class="flex h-full flex-col items-center justify-center px-8 text-center">
                        <p class="font-dm-serif text-[1.8rem]">No stays to watch yet</p>
                        <p class="mt-2 max-w-sm text-[12px] leading-relaxed text-white/70">
                            Nothing matches that filter in the feed. Clear the search to keep browsing.
                        </p>

                        <button
                            type="button"
                            class="dv-btn dv-btn-primary mt-6"
                            wire:click="$set('search', ''); $set('type', 'all')"
                        >Show every stay</button>
                    </div>
                @endforelse
            </div>

            {{-- Feed controls, floated over the footage. --}}
            <div class="pointer-events-none absolute inset-x-0 top-0 z-30 flex flex-col items-center gap-3 pt-28 md:pt-24">
                <div class="dv-tabs pointer-events-auto max-w-full" role="tablist" aria-label="Reel collections">
                    @foreach ($feedTabs as $tab)
                        <button
                            type="button"
                            role="tab"
                            wire:click="setType('{{ $tab['type'] }}')"
                            @class(['dv-tab', 'dv-tab-active' => $type === $tab['type']])
                            @class(['!text-cream hover:!text-white' => $type === $tab['type']])
                            aria-selected="{{ $type === $tab['type'] ? 'true' : 'false' }}"
                        >{{ $tab['label'] }}</button>
                    @endforeach
                </div>

                <div class="pointer-events-auto flex items-center gap-2">
                    <a href="{{ route('stays.index', array_filter(['q' => $search, 'type' => $type === 'all' ? null : $type, 'sort' => $sort === 'recommended' ? null : $sort])) }}"
                       class="dv-round !flex !border-white/25 !bg-black/35 !text-white backdrop-blur-md"
                    >
                        <x-line-icon name="grid" :size="18" />
                        <span class="ml-2 text-[11px] font-bold">Grid</span>
                    </a>

                    <button
                        type="button"
                        class="dv-round !flex !border-white/25 !bg-black/35 !text-white backdrop-blur-md"
                        wire:click="$set('search', '')"
                        x-show="{{ filled($search) ? 'true' : 'false' }}"
                    >
                        <x-line-icon name="close" :size="16" />
                        <span class="ml-2 text-[11px] font-bold">Clear</span>
                    </button>
                </div>
            </div>

            @if ($paginator->hasPages())
                <div class="absolute bottom-24 left-1/2 z-30 -translate-x-1/2 md:bottom-6">
                    <button
                        type="button"
                        class="dv-btn !border-white/25 !bg-black/45 !text-white backdrop-blur-md"
                        wire:click="nextPage"
                        wire:loading.attr="disabled"
                        x-on:click="$nextTick(() => $el.closest('.dv-reels-screen').querySelector('.dv-feed')?.scrollTo({ top: 0, behavior: 'smooth' }))"
                    >
                        More stays
                        <span wire:loading wire:target="nextPage">…</span>
                    </button>
                </div>
            @endif
        </section>
    @else
        @php
            $gold = 'bg-gradient-to-r from-[#F6E7C8] via-[#E2C48E] to-[#C49A5C] bg-clip-text text-transparent';
            $filtered = filled($search) || $type !== 'all' || filled($minPrice) || filled($maxPrice) || $sort !== 'recommended';
            $signature = ! $filtered && $paginator->currentPage() === 1 ? collect($cards)->firstWhere('featured', true) : null;
            $suites = $signature ? collect($cards)->reject(fn ($card) => $card['id'] === $signature['id'])->values() : collect($cards);
            $taglines = [
                'hotel' => 'Turned-down beds, ocean views and someone always at the desk.',
                'villa' => 'Your own pool, your own pace, nobody else’s schedule.',
                'bed_and_breakfast' => 'Courtyards, slow breakfasts and a host who knows the town.',
            ];
        @endphp

        {{-- ──────────────────────────────  Hero  ────────────────────────── --}}
        <section class="dv-hero min-h-[38rem] lg:min-h-[44rem]">
            @if (filled($hero))
                <video
                    x-ref="hero"
                    class="dv-hero-video"
                    autoplay
                    muted
                    loop
                    playsinline
                    preload="none"
                    poster="{{ $hero['poster'] }}"
                    aria-hidden="true"
                    x-init="if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) $refs.hero.pause()"
                >
                    <source src="{{ $hero['video'] }}" type="video/mp4">
                </video>
            @endif

            <div class="absolute inset-0 -z-10 bg-[linear-gradient(90deg,rgba(14,18,14,0.88)_0%,rgba(14,18,14,0.55)_50%,rgba(14,18,14,0.2)_100%),linear-gradient(0deg,rgba(14,18,14,0.6),transparent_45%)]" aria-hidden="true"></div>
            <div class="absolute inset-0 -z-10 bg-[radial-gradient(60rem_circle_at_85%_10%,rgba(226,196,142,0.22),transparent_60%)]" aria-hidden="true"></div>

            <div class="dv-hero-copy">
                <p class="dv-magic-in flex items-center gap-3 text-[11px] font-extrabold uppercase tracking-[0.3em] text-[#E9D3A6]" style="--i: 0">
                    <span class="h-px w-10 bg-[#E9D3A6]/70"></span> The HolidayHub collection
                </p>
                <h1 class="dv-display dv-magic-in" style="--i: 1">
                    Rest is the<br><span class="{{ $gold }} pr-2 italic">rarest luxury.</span>
                </h1>
                <p class="dv-hero-subtitle dv-magic-in" style="--i: 2">
                    Ocean-view suites, villas with their own pools and courtyard B&amp;Bs, chosen for the moment you finally exhale.
                </p>

                <form class="dv-searchbar dv-magic-in" style="--i: 3" role="search" wire:submit="resetPage">
                    <span class="dv-search-icon !bg-[#F6EEDD] !text-[#A8834F]" aria-hidden="true"><x-line-icon name="bed" :size="20" /></span>
                    <span class="dv-search-copy">
                        <input
                            type="search"
                            wire:model.live.debounce.500ms="search"
                            placeholder="Where shall we take you?"
                            aria-label="Search stays"
                            class="h-auto"
                        >
                        <small wire:key="stay-result-count">
                            @if (filled($search) && $resultCount > 0)
                                {{ $resultCount }} {{ Str::plural('stay', $resultCount) }} for “{{ $search }}”
                            @else
                                A stay, a town or a coastline
                            @endif
                        </small>
                    </span>
                    <button type="submit" class="dv-search-go" aria-label="Search stays"><x-line-icon name="arrow" :size="19" /></button>
                </form>
            </div>

            <button type="button" wire:click="setView('reels')" class="absolute bottom-8 right-[5vw] z-10 hidden items-center gap-3 text-left text-white transition-opacity hover:opacity-80 lg:flex">
                <span class="grid h-12 w-12 place-items-center rounded-full border border-[#E9D3A6]/60 text-[#E9D3A6]"><x-line-icon name="play" :size="16" filled /></span>
                <span class="flex flex-col">
                    <b class="text-[13px]">Step inside before you book</b>
                    <span class="text-[11px] text-white/65">Watch every stay as a reel</span>
                </span>
            </button>
        </section>

        {{-- ───────────────────────────  Collections  ─────────────────────── --}}
        @if (collect($collections)->sum('count') > 0)
            <section class="dv-section bg-surface">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="text-[11px] font-extrabold uppercase tracking-[0.22em] text-[#A8834F]">The collection</p>
                        <h2 class="dv-title">Three ways to be looked after</h2>
                    </div>
                    <p class="max-w-[22rem] text-[13px] leading-relaxed text-muted">
                        {{ collect($collections)->sum('count') }} stays, each one published only after our team has checked it.
                    </p>
                </div>

                <div class="mt-8 grid gap-4 md:grid-cols-3">
                    @foreach ($collections as $collection)
                        @continue($collection['count'] === 0)
                        <button
                            type="button"
                            wire:click="setType('{{ $collection['type'] }}')"
                            x-on:click="$nextTick(() => document.getElementById('suites')?.scrollIntoView({ behavior: 'smooth' }))"
                            @class([
                                'dv-card group h-[20rem] text-left md:h-[26rem]',
                                'ring-2 ring-[#C49A5C] ring-offset-4 ring-offset-surface' => $type === $collection['type'],
                            ])
                        >
                            @if ($collection['image'])
                                <img src="{{ $collection['image'] }}" alt="{{ $collection['label'] }}" loading="lazy">
                            @endif
                            <div class="dv-card-gradient"></div>
                            <div class="dv-card-body pr-14">
                                <p class="text-[10px] font-extrabold uppercase tracking-[0.22em] text-[#E9D3A6]">{{ $collection['count'] }} {{ Str::plural('stay', $collection['count']) }}@if ($collection['from']) · from {{ $collection['currency'] }} {{ number_format($collection['from']) }}@endif</p>
                                <p class="mt-1 font-dm-serif text-[2.2rem] leading-none">{{ $collection['label'] }}</p>
                                <p class="mt-2 max-w-[18rem] text-[12px] leading-relaxed text-white/75">{{ $taglines[$collection['type']] ?? '' }}</p>
                            </div>
                            <span class="dv-card-arrow"><x-line-icon name="arrow" :size="19" /></span>
                        </button>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ─────────────────────────  Signature stay  ─────────────────────── --}}
        @if ($signature)
            <section class="dv-section">
                <div class="grid items-center gap-10 lg:grid-cols-[1.25fr_1fr] lg:gap-16">
                    <x-stay.suite-card :card="$signature" tall bare />

                    <div>
                        <p class="text-[11px] font-extrabold uppercase tracking-[0.22em] text-[#A8834F]">Signature stay</p>
                        <h2 class="mt-3 font-dm-serif text-[clamp(2.4rem,4.5vw,4rem)] leading-[1] text-forest">{{ $signature['name'] }}</h2>
                        <p class="mt-3 font-dm-serif text-[1.15rem] italic text-muted">{{ $signature['type_label'] }} in {{ $signature['place'] }}</p>
                        @if ($signature['caption'])
                            <p class="mt-6 text-[15px] leading-[1.8] text-muted">{{ $signature['caption'] }}</p>
                        @endif

                        <dl class="mt-8 grid grid-cols-3 border-y border-line py-5 text-center">
                            <div class="border-r border-line">
                                <dt class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-muted">Rating</dt>
                                <dd class="mt-1 font-dm-serif text-[1.5rem] text-forest">{{ $signature['rating'] > 0 ? number_format($signature['rating'], 1) : 'New' }}</dd>
                            </div>
                            <div class="border-r border-line">
                                <dt class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-muted">{{ $signature['guests'] > 0 ? 'Guests' : 'Reviews' }}</dt>
                                <dd class="mt-1 font-dm-serif text-[1.5rem] text-forest">{{ $signature['guests'] > 0 ? $signature['guests'] : number_format($signature['reviews']) }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-muted">Per night</dt>
                                <dd class="mt-1 font-dm-serif text-[1.5rem] text-forest">{{ $signature['price'] > 0 ? $signature['currency'].' '.number_format($signature['price'] / 1000, 0).'k' : '—' }}</dd>
                            </div>
                        </dl>

                        <div class="mt-8 flex flex-wrap gap-3">
                            <a href="{{ $signature['url'] }}" class="dv-btn dv-btn-primary min-h-[3.3rem] px-6 text-[13px]">Reserve your stay <x-line-icon name="arrow" :size="17" /></a>
                            <button type="button" wire:click="setView('reels')" class="dv-btn dv-btn-ghost min-h-[3.3rem] px-6 text-[13px]"><x-line-icon name="play" :size="14" filled /> Watch the reels</button>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        {{-- ─────────────────────  Controls, grid & pagination  ───────────── --}}
        <section id="suites" class="dv-section scroll-mt-24 {{ $signature ? '!pt-4' : '' }}">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-[11px] font-extrabold uppercase tracking-[0.22em] text-[#A8834F]">{{ $types[$type] ?? 'All stays' }}</p>
                    <h2 class="dv-title">Suites, villas &amp; hideaways</h2>
                </div>

                <button type="button" wire:click="setView('reels')" class="dv-btn dv-btn-ghost !min-h-[2.6rem] !px-4">
                    <x-line-icon name="play" :size="14" :filled="true" /> Watch reels
                </button>
            </div>

            <div class="dv-category-row mt-6" data-no-reveal>
                @foreach ($types as $value => $label)
                    <button
                        type="button"
                        wire:click="setType('{{ $value }}')"
                        @class(['dv-category py-3', 'dv-category-active' => $type === $value])
                        aria-pressed="{{ $type === $value ? 'true' : 'false' }}"
                    >
                        <x-line-icon :name="match ($value) { 'hotel' => 'bed', 'villa' => 'waves', 'bed_and_breakfast' => 'utensils', default => 'compass' }" :size="17" />
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div class="dv-filter-bar" wire:loading.class="opacity-50" wire:target="minPrice,maxPrice,sortBy,clearFilters">
                <span class="dv-filter-count" wire:key="stay-total">
                    {{ $resultCount }} {{ Str::plural('stay', $resultCount) }}
                    @if ($paginator->hasPages())
                        · page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}
                    @endif
                </span>

                <div class="dv-price">
                    <label for="stays-min-price">Nightly</label>
                    <input id="stays-min-price" type="number" inputmode="numeric" min="0" step="500" placeholder="From" aria-label="Minimum nightly price" wire:model.live.debounce.600ms="minPrice">
                    <span aria-hidden="true">–</span>
                    <input id="stays-max-price" type="number" inputmode="numeric" min="0" step="500" placeholder="To" aria-label="Maximum nightly price" wire:model.live.debounce.600ms="maxPrice">
                </div>

                <div class="dv-select-wrap">
                    <label for="stays-sort">Sort</label>
                    <select id="stays-sort" class="dv-select" wire:model.live="sort">
                        @foreach ($sorts as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-line-icon name="chevron-down" :size="15" />
                </div>

                <button
                    type="button"
                    class="dv-filter-clear"
                    wire:click="clearFilters"
                    x-show="{{ filled($search) || filled($minPrice) || filled($maxPrice) || $sort !== 'recommended' ? 'true' : 'false' }}"
                    x-cloak
                >Clear filters</button>
            </div>

            <div
                class="mt-10 grid gap-x-6 gap-y-14 sm:grid-cols-2 lg:grid-cols-3"
                wire:loading.class="opacity-50"
                wire:target="search,setType,sortBy,setView,clearFilters,gotoPage,nextPage,previousPage"
            >
                @forelse ($suites as $card)
                    <x-stay.suite-card :card="$card" wire:key="suite-{{ $card['id'] }}" />
                @empty
                    @unless ($signature)
                        <div class="col-span-full flex flex-col items-center rounded-[1.4rem] border border-line bg-surface px-8 py-16 text-center">
                            <span class="grid h-14 w-14 place-items-center rounded-full bg-[#F6EEDD] text-[#A8834F]"><x-line-icon name="bed" :size="24" /></span>
                            <p class="mt-4 font-dm-serif text-[1.7rem] text-forest">Nothing quite right… yet</p>
                            <p class="mx-auto mt-2 max-w-md text-[14px] leading-relaxed text-muted">
                                No stay matches those preferences. Try another town or coastline, or let us show you the whole collection.
                            </p>
                            <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                                <button type="button" class="dv-btn dv-btn-primary" wire:click="$set('search', ''); $set('type', 'all')">See the whole collection</button>
                                <a href="{{ route('destination.index') }}" class="dv-btn dv-btn-ghost">Browse destinations</a>
                            </div>
                        </div>
                    @endunless
                @endforelse
            </div>

            @if ($paginator->hasPages())
                <div class="mt-12">{{ $paginator->links() }}</div>
            @endif
        </section>

        {{-- ─────────────────────────────  Closing  ───────────────────────── --}}
        <section class="px-5 pb-10 md:px-[max(5vw,calc((100vw-1350px)/2))]">
            <div class="relative isolate overflow-hidden rounded-[1.6rem] bg-[#121A15] px-8 py-16 text-center text-white md:py-24">
                <div class="absolute inset-0 -z-10 bg-[radial-gradient(40rem_circle_at_50%_120%,rgba(226,196,142,0.28),transparent_65%)]" aria-hidden="true"></div>
                <p class="text-[11px] font-extrabold uppercase tracking-[0.3em] text-[#E9D3A6]">The art of doing nothing</p>
                <p class="mx-auto mt-6 max-w-[40rem] font-dm-serif text-[clamp(1.9rem,4vw,3.2rem)] italic leading-[1.15]">
                    “The best stays don’t ask anything of you. They simply let you <span class="{{ $gold }} pr-1">arrive</span>.”
                </p>
                <div class="mt-9 flex flex-wrap justify-center gap-3">
                    <button type="button" wire:click="setView('reels')" class="dv-btn dv-btn-primary min-h-[3.3rem] px-6 text-[13px]"><x-line-icon name="play" :size="14" filled /> Step inside the reels</button>
                    <a href="{{ route('experiences.index') }}" class="dv-btn dv-magic-glass min-h-[3.3rem] px-6 text-[13px]">Pair it with an experience</a>
                </div>
            </div>
        </section>
    @endif
</div>
