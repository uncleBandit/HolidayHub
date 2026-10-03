{{-- Visual Discovery destination index: hero search, mood pills, editorial tabs. --}}
<div class="dv-shell">
    {{-- ──────────────────────────────  Hero  ────────────────────────────── --}}
    <section class="dv-hero">
        <img src="{{ $hero['image'] }}" alt="{{ $hero['alt'] }}">
        <div class="dv-hero-shade" aria-hidden="true"></div>

        <div class="dv-hero-copy">
            <p class="dv-eyebrow-light">Curated escapes</p>
            <h1 class="dv-display">
                Where are you<br>escaping to?
            </h1>
            <p class="dv-hero-subtitle">
                Browse the destinations our travellers love — coastlines, savannah,
                highlands and cities that stay with you long after you leave.
            </p>

            <form
                class="dv-searchbar"
                role="search"
                wire:submit="resetPage"
            >
                <span class="dv-search-icon" aria-hidden="true">
                    <x-line-icon name="search" :size="20" />
                </span>

                <span class="dv-search-copy">
                    <input
                        type="search"
                        wire:model.live.debounce.500ms="search"
                        placeholder="Search your next escape"
                        aria-label="Search destinations"
                        class="h-auto"
                    >
                    <small wire:key="result-count">
                        {{ filled($search) && $resultCount > 0 ? $resultCount.' matches for “'.$search.'”' : 'Places, stays and experiences' }}
                    </small>
                </span>

                <button type="submit" class="dv-search-go" aria-label="Search destinations">
                    <x-line-icon name="arrow" :size="19" />
                </button>
            </form>
        </div>

        <div class="dv-hero-meta">
            <x-line-icon name="map" :size="17" />
            <div>
                <b>{{ $hero['place'] }}</b>
                <span>{{ $hero['region'] }}</span>
            </div>
        </div>
    </section>

    {{-- ───────────────────────────  Mood pills  ─────────────────────────── --}}
    <section class="dv-section !pb-0" aria-label="Filter by travel mood">
        <div class="dv-category-row">
            @foreach ($moods as $value => $label)
                <button
                    type="button"
                    wire:click="$set('category', '{{ $value }}')"
                    @class(['dv-category', 'dv-category-active' => $category === $value])
                    @if ($category === $value) aria-pressed="true" @else aria-pressed="false" @endif
                >
                    @if ($value !== 'all')
                        <x-line-icon
                            :name="match ($value) {
                                'beaches' => 'waves',
                                'wild' => 'map',
                                'mountains' => 'mountain',
                                'islands' => 'compass',
                                default => 'users',
                            }"
                            :size="19"
                        />
                    @endif

                    {{ $label }}
                </button>
            @endforeach
        </div>
    </section>

    {{-- ─────────────────────────  Collections & grid  ───────────────────── --}}
    <section class="dv-section !pt-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="dv-eyebrow">The full atlas</p>
                <p class="dv-title">
                    {{ $tab === 'featured' ? 'Featured escapes' : ($tab === 'top-rated' ? 'Highest rated' : 'Everywhere worth going') }}
                </p>
            </div>

            <div class="flex items-center gap-2.5 pb-1">
                @php
                    $sorts = [
                        ['field' => 'name', 'label' => 'A–Z'],
                        ['field' => 'popularity_score', 'label' => 'Top rated'],
                    ];
                @endphp

                @foreach ($sorts as $sort)
                    <button
                        type="button"
                        wire:click="sortBy('{{ $sort['field'] }}')"
                        @class([
                            'dv-btn dv-btn-ghost !min-h-[2.4rem] !px-4',
                            '!border-forest !bg-forest !text-white' => $sortField === $sort['field'],
                        ])
                        aria-pressed="{{ $sortField === $sort['field'] ? 'true' : 'false' }}"
                    >
                        {{ $sort['label'] }}

                        @if ($sortField === $sort['field'])
                            <span class="text-[10px]">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>

        <div class="dv-tabs mt-8" role="tablist" aria-label="Collections">
            @foreach ($tabs as $value => $label)
                <button
                    type="button"
                    role="tab"
                    wire:click="setTab('{{ $value }}')"
                    @class(['dv-tab', 'dv-tab-active' => $tab === $value])
                    aria-selected="{{ $tab === $value ? 'true' : 'false' }}"
                >{{ $label }}</button>
            @endforeach

            <span class="ml-auto hidden items-center pb-4 text-[11px] text-muted md:flex">
                {{ $resultCount }} {{ Illuminate\Support\Str::plural('destination', $resultCount) }}
                @if ($isFallback)
                    · curated preview
                @endif
            </span>
        </div>

        <div
            class="dv-grid mt-8"
            wire:loading.class="opacity-50"
            wire:target="search,category,tab,sortBy,setTab,gotoPage,nextPage,previousPage"
        >
            @forelse ($cards as $index => $card)
                <x-destination.discovery-card :card="$card" :featured="$index === 0" />
            @empty
                <div class="dv-panel col-span-full px-8 py-16 text-center">
                    <p class="font-dm-serif text-[1.6rem] text-forest">No destinations found</p>
                    <p class="mx-auto mt-3 max-w-md text-[14px] leading-relaxed text-muted">
                        We couldn’t match that search. Try a different place, or clear the
                        filters to see the full atlas.
                    </p>

                    <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                        <button
                            type="button"
                            wire:click="$set('search', ''); $set('category', 'all'); $set('tab', 'all')"
                            class="dv-btn dv-btn-primary"
                        >Show all destinations</button>

                        <a href="{{ route('destination.index') }}" class="dv-btn dv-btn-ghost">Reset</a>
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
</div>