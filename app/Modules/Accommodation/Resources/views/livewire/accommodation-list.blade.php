{{-- Accommodation list: a cream browse page — headline and search, grouping
     chips, filter bar, then stays as reel cards (grouped by type or place). --}}
@php
    $groupLabel = function (?string $group) use ($groupBy): string {
        if ($groupBy === 'type') {
            return match (\Illuminate\Support\Str::snake((string) $group)) {
                'hotel' => 'Hotels',
                'villa' => 'Villas',
                'bed_and_breakfast' => 'B&Bs',
                default => \Illuminate\Support\Str::plural(\Illuminate\Support\Str::headline((string) $group)) ?: 'Stays',
            };
        }

        return filled($group) ? $group : 'Elsewhere';
    };
    $hasFilters = filled($search) || filled($destinationId) || filled($minPrice) || filled($maxPrice) || filled($minRating) || $sortBy !== 'latest';
    $total = $accommodations->total();
    // The grouped payload is flattened to arrays by the component; map each item
    // back to its loaded model so the cards get relations and accessors.
    $byId = $accommodations->getCollection()->keyBy('id');
    $modelFor = fn ($item) => is_array($item) ? $byId->get($item['id'] ?? null) : $item;
@endphp

<div>
    {{-- ─────────────────────────────  Header  ───────────────────────────── --}}
    <section class="dv-section !pb-6">
        <div class="grid items-end gap-8 lg:grid-cols-[1fr_auto]">
            <div>
                <p class="dv-eyebrow dv-magic-in" style="--i: 0">Every stay on HolidayHub</p>
                <h1 class="dv-magic-in mt-3 font-dm-serif text-[clamp(2.4rem,5.5vw,4.6rem)] leading-[0.95] tracking-[-0.03em] text-forest" style="--i: 1">
                    Find your <span class="italic text-clay">perfect escape.</span>
                </h1>
                <p class="dv-magic-in mt-4 max-w-[36rem] text-[14px] leading-relaxed text-muted" style="--i: 2">
                    Curated hotels, villas and B&amp;Bs — filter by place, price and rating.
                </p>
            </div>

            <form class="dv-searchbar dv-magic-in !mt-0 lg:w-[30rem]" role="search" wire:submit="$refresh" style="--i: 3">
                <span class="dv-search-icon" aria-hidden="true"><x-line-icon name="search" :size="20" /></span>
                <span class="dv-search-copy">
                    <input type="search" wire:model.live.debounce.500ms="search" placeholder="Search for places..." aria-label="Search stays by name, city or country" class="h-auto">
                    <small>{{ $total }} {{ \Illuminate\Support\Str::plural('stay', $total) }}{{ filled($search) ? ' for “'.$search.'”' : ' to book' }}</small>
                </span>
                <button type="submit" class="dv-search-go" aria-label="Search"><x-line-icon name="arrow" :size="19" /></button>
            </form>
        </div>
    </section>

    {{-- ──────────────────────────  Grouping + filters  ───────────────────── --}}
    <section class="dv-section !pt-0 !pb-0">
        <div class="dv-category-row" role="tablist" aria-label="Group results">
            @foreach (['none' => ['All results', 'grid'], 'type' => ['Group by type', 'bed'], 'destination' => ['Group by destination', 'map']] as $value => [$label, $icon])
                <button
                    type="button"
                    role="tab"
                    wire:click="$set('groupBy', '{{ $value }}')"
                    @class(['dv-category !py-3', 'dv-category-active' => $groupBy === $value])
                    aria-selected="{{ $groupBy === $value ? 'true' : 'false' }}"
                >
                    <x-line-icon :name="$icon" :size="16" /> {{ $label }}
                </button>
            @endforeach
        </div>

        <div class="dv-filter-bar">
            <span class="dv-filter-count">
                {{ $total }} {{ \Illuminate\Support\Str::plural('result', $total) }}
                @if ($accommodations->hasPages()) · page {{ $accommodations->currentPage() }} of {{ $accommodations->lastPage() }} @endif
            </span>

            <div class="dv-select-wrap">
                <label for="stays-destination">Where</label>
                <select id="stays-destination" wire:model.live="destinationId" class="dv-select max-w-[12rem]">
                    <option value="">All Destinations</option>
                    @foreach ($destinations as $destination)
                        <option value="{{ $destination->id }}">{{ $destination->name }}</option>
                    @endforeach
                </select>
                <x-line-icon name="chevron-down" :size="15" />
            </div>

            <div class="dv-price">
                <label for="stays-min-price">Per night</label>
                <input id="stays-min-price" wire:model.live.debounce.500ms="minPrice" type="number" min="0" placeholder="Min" aria-label="Minimum price per night">
                <span aria-hidden="true">–</span>
                <input id="stays-max-price" wire:model.live.debounce.500ms="maxPrice" type="number" min="0" placeholder="Max" aria-label="Maximum price per night">
            </div>

            <div class="dv-price">
                <label for="stays-min-rating">Rating</label>
                <input id="stays-min-rating" wire:model.live.debounce.500ms="minRating" type="number" step="0.5" min="0" max="5" placeholder="e.g. 4.0" aria-label="Minimum rating">
            </div>

            <div class="dv-select-wrap">
                <label for="stays-sort">Sort</label>
                <select id="stays-sort" wire:model.live="sortBy" class="dv-select">
                    <option value="latest">Latest</option>
                    <option value="price_low">Price: Low to High</option>
                    <option value="price_high">Price: High to Low</option>
                    <option value="rating">Top Rated</option>
                </select>
                <x-line-icon name="chevron-down" :size="15" />
            </div>

            @if ($hasFilters)
                <button
                    type="button"
                    class="dv-filter-clear"
                    wire:click="$set('search', ''); $set('destinationId', null); $set('minPrice', null); $set('maxPrice', null); $set('minRating', null); $set('sortBy', 'latest')"
                >Clear filters</button>
            @endif
        </div>
    </section>

    {{-- ─────────────────────────────  Results  ─────────────────────────── --}}
    <section class="dv-section !pt-8">
        <div wire:loading.flex wire:target="search, destinationId, sortBy, minPrice, maxPrice, minRating, groupBy" class="mb-6 items-center gap-3 text-[13px] font-semibold text-muted">
            <span class="h-5 w-5 animate-spin rounded-full border-2 border-line border-t-clay" aria-hidden="true"></span>
            Searching for amazing places…
        </div>

        <div wire:loading.class="opacity-50" wire:target="search, destinationId, sortBy, minPrice, maxPrice, minRating, groupBy, gotoPage, nextPage, previousPage">
            @if ($accommodations->isEmpty())
                <div class="flex flex-col items-center rounded-[1.2rem] border border-dashed border-line bg-surface px-6 py-16 text-center">
                    <span class="grid h-14 w-14 place-items-center rounded-full bg-cream text-clay"><x-line-icon name="bed" :size="24" /></span>
                    <p class="mt-4 font-dm-serif text-[1.6rem] text-forest">No accommodations found</p>
                    <p class="mt-1.5 max-w-[26rem] text-[13px] leading-relaxed text-muted">Try another town or coastline, widen the price range, or clear the filters to see every stay.</p>
                    <div class="mt-6 flex flex-wrap justify-center gap-3">
                        @if ($hasFilters)
                            <button type="button" class="dv-btn dv-btn-primary" wire:click="$set('search', ''); $set('destinationId', null); $set('minPrice', null); $set('maxPrice', null); $set('minRating', null); $set('sortBy', 'latest')">Clear filters</button>
                        @endif
                        <a href="{{ route('destination.index') }}" class="dv-btn dv-btn-ghost">Browse destinations</a>
                    </div>
                </div>
            @elseif ($groupBy === 'none')
                <div class="dv-reel-grid !grid-cols-2 lg:!grid-cols-3 xl:!grid-cols-4">
                    @foreach ($accommodations as $accommodation)
                        <x-accommodation-card :accommodation="$accommodation" />
                    @endforeach
                </div>
            @else
                <div class="space-y-14">
                    @foreach ($groupedAccommodations as $group)
                        <div>
                            <div class="flex items-baseline gap-3">
                                <h2 class="dv-title">{{ $groupLabel($group['group']) }}</h2>
                                <span class="text-[13px] font-bold text-clay">{{ count($group['items']) }}</span>
                            </div>
                            <div class="dv-reel-grid mt-6 !grid-cols-2 lg:!grid-cols-3 xl:!grid-cols-4">
                                @foreach ($group['items'] as $item)
                                    @if ($accommodation = $modelFor($item))
                                        <x-accommodation-card :accommodation="$accommodation" />
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($accommodations->hasPages())
                <div class="mt-12">{{ $accommodations->links() }}</div>
            @endif
        </div>
    </section>
</div>
