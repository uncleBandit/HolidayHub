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
        {{-- ──────────────────────────────  Hero  ────────────────────────── --}}
        <section class="dv-hero">
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

            <div class="dv-hero-shade" aria-hidden="true"></div>

            <div class="dv-hero-copy">
                <p class="dv-eyebrow-light">Stays, in motion</p>
                <h1 class="dv-display">
                    Find the place<br>you'd replay.
                </h1>
                <p class="dv-hero-subtitle">
                    Hotels, villas and B&Bs in one place — browsed like reels, booked
                    like a stay. Watch the room, the light and the water before you decide.
                </p>

                <form class="dv-searchbar" role="search" wire:submit="resetPage">
                    <span class="dv-search-icon" aria-hidden="true">
                        <x-line-icon name="search" :size="20" />
                    </span>

                    <span class="dv-search-copy">
                        <input
                            type="search"
                            wire:model.live.debounce.500ms="search"
                            placeholder="Search a stay, a town or a coastline"
                            aria-label="Search stays"
                            class="h-auto"
                        >
                        <small wire:key="stay-result-count">
                            @if (filled($search) && $resultCount > 0)
                                {{ $resultCount }} {{ Illuminate\Support\Str::plural('stay', $resultCount) }} for “{{ $search }}”
                            @else
                                Hotels, villas and B&Bs
                            @endif
                        </small>
                    </span>

                    <button type="submit" class="dv-search-go" aria-label="Search stays">
                        <x-line-icon name="arrow" :size="19" />
                    </button>
                </form>
            </div>

            <div class="dv-hero-meta">
                <x-line-icon name="waves" :size="17" />
                <div>
                    <b>Three ways to stay</b>
                    <span>Hotels · Villas · B&Bs</span>
                </div>
            </div>
        </section>

        {{-- ──────────────────────────  Stay type pills  ──────────────────── --}}
        <section class="dv-section !pb-0" aria-label="Filter by stay type">
            <div class="dv-category-row">
                @foreach ($types as $value => $label)
                    <button
                        type="button"
                        wire:click="setType('{{ $value }}')"
                        @class(['dv-category', 'dv-category-active' => $type === $value])
                        @if ($type === $value) aria-pressed="true" @else aria-pressed="false" @endif
                    >
                        <x-line-icon
                            :name="match ($value) {
                                'hotel' => 'bed',
                                'villa' => 'waves',
                                'bed_and_breakfast' => 'utensils',
                                default => 'compass',
                            }"
                            :size="19"
                        />

                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </section>

        {{-- ─────────────────────  Controls, grid & pagination  ───────────── --}}
        <section class="dv-section !pt-8">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="dv-eyebrow">{{ $types[$type] ?? 'All stays' }}</p>
                    <p class="dv-title">Stay somewhere you'll talk about</p>
                </div>

                <button
                    type="button"
                    wire:click="setView('reels')"
                    class="dv-btn dv-btn-primary !min-h-[2.4rem] !px-4"
                >
                    <x-line-icon name="play" :size="14" :filled="true" />
                    Watch reels
                </button>
            </div>

            {{-- Filters carried over from the per-type listings: search, price, sort. --}}
            <div class="dv-filter-bar" wire:loading.class="opacity-50" wire:target="minPrice,maxPrice,sortBy,clearFilters">
                <span class="dv-filter-count" wire:key="stay-total">
                    {{ $resultCount }} {{ Illuminate\Support\Str::plural('stay', $resultCount) }}
                    @if ($paginator->hasPages())
                        · page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}
                    @endif
                </span>

                <div class="dv-price">
                    <label for="stays-min-price">Nightly</label>

                    <input
                        id="stays-min-price"
                        type="number"
                        inputmode="numeric"
                        min="0"
                        step="500"
                        placeholder="From"
                        aria-label="Minimum nightly price"
                        wire:model.live.debounce.600ms="minPrice"
                    >

                    <span aria-hidden="true">–</span>

                    <input
                        id="stays-max-price"
                        type="number"
                        inputmode="numeric"
                        min="0"
                        step="500"
                        placeholder="To"
                        aria-label="Maximum nightly price"
                        wire:model.live.debounce.600ms="maxPrice"
                    >
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
                class="dv-reel-grid mt-8"
                wire:loading.class="opacity-50"
                wire:target="search,setType,sortBy,setView,clearFilters,gotoPage,nextPage,previousPage"
            >
                @forelse ($cards as $card)
                    <x-stay.reel-card :card="$card" />
                @empty
                    <div class="dv-panel col-span-full px-8 py-16 text-center">
                        <p class="font-dm-serif text-[1.6rem] text-forest">No stays found</p>
                        <p class="mx-auto mt-3 max-w-md text-[14px] leading-relaxed text-muted">
                            We couldn't match that search. Try another town or coastline, or
                            clear the filters to see every stay.
                        </p>

                        <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                            <button
                                type="button"
                                class="dv-btn dv-btn-primary"
                                wire:click="$set('search', ''); $set('type', 'all')"
                            >Show all stays</button>

                            <a href="{{ route('destination.index') }}" class="dv-btn dv-btn-ghost">Browse destinations</a>
                        </div>
                    </div>
                @endforelse
            </div>

            @if ($paginator->hasPages())
                <div class="mt-10">
                    {{ $paginator->links() }}
                </div>
            @endif
        </section>
    @endif
</div>