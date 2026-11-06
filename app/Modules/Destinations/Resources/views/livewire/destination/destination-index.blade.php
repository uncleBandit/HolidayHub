<div>
    @php
        $slides = $expeditions ?: [['name' => $hero['place'], 'country' => $hero['region'], 'coords' => null, 'image' => $hero['image']]];
    @endphp

    {{-- ─────────────────────────────  Hero  ────────────────────────────── --}}
    <section
        class="dv-magic-hero"
        x-data="{
            slides: @js($slides),
            active: 0,
            init() {
                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || this.slides.length < 2) return;
                setInterval(() => this.active = (this.active + 1) % this.slides.length, 6500);
            },
        }"
        x-on:mousemove="const r = $el.getBoundingClientRect(); $el.style.setProperty('--mx', ($event.clientX - r.left) + 'px'); $el.style.setProperty('--my', ($event.clientY - r.top) + 'px')"
    >
        @foreach ($slides as $i => $slide)
            <img
                src="{{ $slide['image'] }}"
                alt="{{ $slide['name'] }}"
                @class(['dv-magic-slide', 'is-active' => $i === 0])
                :class="active === {{ $i }} && 'is-active'"
                loading="{{ $i === 0 ? 'eager' : 'lazy' }}"
            >
        @endforeach

        <div class="dv-magic-shade"></div>
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_center,transparent_35%,rgba(5,14,10,0.75)_100%)]" aria-hidden="true"></div>
        <div class="dv-magic-spotlight" aria-hidden="true"></div>

        <div class="dv-magic-particles" aria-hidden="true">
            @php mt_srand(11); @endphp
            @for ($p = 0; $p < 20; $p++)
                <span style="left: {{ mt_rand(0, 100) }}%; --size: {{ mt_rand(2, 5) }}px; --delay: -{{ mt_rand(0, 140) / 10 }}s; --duration: {{ mt_rand(120, 240) / 10 }}s; --drift: {{ mt_rand(-50, 50) }}px;"></span>
            @endfor
        </div>

        {{-- Compass rose --}}
        <svg class="pointer-events-none absolute -right-24 -top-24 hidden h-[26rem] w-[26rem] text-white/15 animate-[dv-spin_90s_linear_infinite] md:block" viewBox="0 0 200 200" fill="none" stroke="currentColor" aria-hidden="true">
            <circle cx="100" cy="100" r="96" stroke-width="0.6" />
            <circle cx="100" cy="100" r="78" stroke-width="0.4" stroke-dasharray="2 4" />
            <circle cx="100" cy="100" r="40" stroke-width="0.6" />
            <path d="M100 4 L108 92 L196 100 L108 108 L100 196 L92 108 L4 100 L92 92 Z" stroke-width="0.8" />
            <path d="M100 30 L104 96 L170 100 L104 104 L100 170 L96 104 L30 100 L96 96 Z" stroke-width="0.5" transform="rotate(45 100 100)" />
            <text x="100" y="20" fill="currentColor" stroke="none" font-size="9" text-anchor="middle" font-family="serif">N</text>
        </svg>

        <div class="relative z-10 flex h-full w-full flex-col justify-end px-[5vw] pb-24 md:justify-center md:pb-12">
            <p class="dv-eyebrow-light dv-magic-in" style="--i: 0">The atlas · {{ $resultCount ?: count($cards) }} places, endless stories</p>
            <h1 class="dv-display dv-magic-in max-w-[52rem]" style="--i: 1">
                The map still<br>has <span class="bg-gradient-to-r from-[#FFD7B5] via-[#F6A27F] to-clay bg-clip-text pr-2 italic text-transparent">secrets.</span>
            </h1>
            <p class="dv-hero-subtitle dv-magic-in" style="--i: 2">
                Migrations that shake the earth, islands without cars, temples lit by lanterns. Pick a direction and follow your curiosity.
            </p>

            <form class="dv-searchbar dv-magic-in" style="--i: 3" role="search" wire:submit="resetPage">
                <span class="dv-search-icon" aria-hidden="true"><x-line-icon name="compass" :size="20" /></span>
                <span class="dv-search-copy">
                    <input
                        type="search"
                        wire:model.live.debounce.500ms="search"
                        placeholder="Where does your curiosity point?"
                        aria-label="Search destinations"
                        class="h-auto"
                    >
                    <small wire:key="result-count">
                        {{ filled($search) && $resultCount > 0 ? $resultCount.' matches for “'.$search.'”' : 'A place, a country, a feeling' }}
                    </small>
                </span>
                <button type="submit" class="dv-search-go" aria-label="Search destinations"><x-line-icon name="arrow" :size="19" /></button>
            </form>
        </div>

        {{-- Navigator readout --}}
        <div class="absolute bottom-7 right-[5vw] z-10 hidden text-right text-white md:block">
            <p class="text-[10px] font-extrabold uppercase tracking-[0.3em] text-[#F6D6C9]" x-text="slides[active].coords ?? 'Uncharted'">{{ $slides[0]['coords'] ?? 'Uncharted' }}</p>
            <p class="mt-1 font-dm-serif text-[1.3rem]" x-text="slides[active].name">{{ $slides[0]['name'] }}</p>
            <p class="text-[11px] text-white/60" x-text="slides[active].country">{{ $slides[0]['country'] }}</p>
        </div>

        <a href="#atlas" class="dv-magic-cue" aria-label="Scroll to the atlas"><span></span></a>
    </section>

    {{-- ──────────────────────────  Expeditions  ────────────────────────── --}}
    <section id="atlas" class="dv-section !pb-0 scroll-mt-24" aria-label="Filter by expedition">
        <p class="dv-eyebrow">Choose your expedition</p>
        <div class="dv-category-row mt-5" data-no-reveal>
            @foreach ($moods as $value => $label)
                <button
                    type="button"
                    wire:click="$set('category', '{{ $value }}')"
                    @class(['dv-category', 'dv-category-active' => $category === $value])
                    aria-pressed="{{ $category === $value ? 'true' : 'false' }}"
                >
                    <x-line-icon
                        :name="match ($value) {
                            'safari' => 'map',
                            'beaches' => 'waves',
                            'mountains' => 'mountain',
                            'islands' => 'compass',
                            'culture' => 'leaf',
                            'adventure' => 'arrow-up-right',
                            default => 'grid',
                        }"
                        :size="18"
                    />
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </section>

    {{-- ─────────────────────────────  Atlas  ───────────────────────────── --}}
    <section class="dv-section !pt-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="dv-eyebrow">The full atlas</p>
                <h2 class="dv-title">
                    {{ $tab === 'featured' ? 'Legendary escapes' : ($tab === 'top-rated' ? 'Most loved by explorers' : 'Everywhere worth the journey') }}
                </h2>
            </div>

            <div class="flex items-center gap-2.5 pb-1">
                @foreach ([['field' => 'name', 'label' => 'A–Z'], ['field' => 'popularity_score', 'label' => 'Most loved']] as $sort)
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
                {{ $resultCount }} {{ Str::plural('destination', $resultCount) }}@if ($isFallback) · curated preview @endif
            </span>
        </div>

        <div
            class="mt-8 grid auto-rows-[24rem] gap-4 md:grid-cols-2 lg:grid-cols-3 lg:auto-rows-[27rem]"
            wire:loading.class="opacity-50"
            wire:target="search,category,tab,sortBy,setTab,gotoPage,nextPage,previousPage"
        >
            @forelse ($cards as $index => $card)
                <a
                    href="{{ $card['url'] }}"
                    wire:key="destination-{{ $card['name'] }}"
                    @class(['dv-card group', 'lg:col-span-2' => $index % 6 === 0])
                >
                    <img src="{{ $card['image'] }}" alt="{{ $card['alt'] }}" loading="{{ $index < 3 ? 'eager' : 'lazy' }}">
                    <div class="absolute inset-0 -z-10 bg-[linear-gradient(0deg,rgba(6,16,12,0.92)_0%,rgba(6,16,12,0.35)_45%,rgba(6,16,12,0.15)_100%)] transition-colors duration-500 group-hover:bg-[rgba(6,16,12,0.55)]"></div>

                    <div class="absolute inset-x-5 top-5 z-10 flex items-start justify-between gap-3 text-white">
                        <span class="font-dm-serif text-[1.05rem] italic text-[#F6D6C9]">No. {{ str_pad((string) ($index + 1 + ($paginator->currentPage() - 1) * $paginator->perPage()), 2, '0', STR_PAD_LEFT) }}</span>
                        @if ($card['coords'])
                            <span class="text-right text-[9px] font-extrabold uppercase tracking-[0.25em] text-white/70">{{ $card['coords'] }}</span>
                        @endif
                    </div>

                    <div class="dv-card-body pr-14">
                        <p class="dv-card-kicker">{{ $card['kicker'] }}</p>
                        <p @class(['font-dm-serif leading-[0.95] text-white', 'text-[clamp(2rem,4vw,3.4rem)]' => $index % 6 === 0, 'text-[2rem]' => $index % 6 !== 0])>{{ $card['name'] }}</p>
                        @if ($card['tagline'])
                            <p class="mt-2 max-w-[28rem] font-dm-serif text-[1.02rem] italic leading-snug text-white/80">{{ $card['tagline'] }}</p>
                        @endif

                        {{-- The secrets surface on hover (always shown on touch screens) --}}
                        @if ($card['secrets'] || $card['season'])
                            <div class="grid grid-rows-[1fr] transition-all duration-500 md:grid-rows-[0fr] md:opacity-0 md:group-hover:grid-rows-[1fr] md:group-hover:opacity-100">
                                <div class="overflow-hidden">
                                    <ul class="mt-3 space-y-1 border-t border-white/20 pt-3 text-[11px] text-white/85">
                                        @foreach ($card['secrets'] as $secret)
                                            <li class="flex items-center gap-2"><span class="text-clay">✦</span> {{ $secret }}</li>
                                        @endforeach
                                    </ul>
                                    @if ($card['season'])
                                        <p class="mt-2 text-[10px] font-extrabold uppercase tracking-[0.15em] text-[#F6D6C9]">Go {{ $card['season'] }}</p>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>

                    <span class="dv-card-arrow"><x-line-icon name="arrow" :size="19" /></span>
                </a>
            @empty
                <div class="dv-magic-night col-span-full flex flex-col items-center rounded-[1.4rem] px-8 py-16 text-center text-white">
                    <x-line-icon name="compass" :size="34" />
                    <p class="mt-4 font-dm-serif text-[1.9rem]">Uncharted territory</p>
                    <p class="mx-auto mt-2 max-w-md text-[14px] leading-relaxed text-white/70">
                        Nothing on the map matches that yet. Try another direction, or open the whole atlas.
                    </p>
                    <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                        <button type="button" wire:click="$set('search', ''); $set('category', 'all'); $set('tab', 'all')" class="dv-btn dv-btn-primary">Open the whole atlas</button>
                        <a href="{{ route('destination.index') }}" class="dv-btn dv-magic-glass">Reset</a>
                    </div>
                </div>
            @endforelse
        </div>

        @if ($paginator->hasPages())
            <div class="mt-10">{{ $paginator->links() }}</div>
        @endif
    </section>

    {{-- ──────────────────────  Mystery destination  ────────────────────── --}}
    @if ($mystery)
        <section class="px-5 pb-10 md:px-[max(5vw,calc((100vw-1350px)/2))]">
            <div class="dv-magic-night grid overflow-hidden rounded-[1.6rem] text-white lg:grid-cols-[1fr_1.1fr]" x-data="{ revealed: false }">
                <div class="relative z-10 flex flex-col justify-center px-8 py-12 md:px-14">
                    <p class="dv-eyebrow-light">Today’s mystery destination</p>
                    <h2 class="mt-3 font-dm-serif text-[clamp(2.2rem,4.5vw,3.8rem)] leading-[1]">
                        <span x-show="! revealed">Somewhere out there…</span>
                        <span x-show="revealed" x-cloak x-transition.opacity.duration.700ms>{{ $mystery['name'] }}</span>
                    </h2>

                    <ul class="mt-6 space-y-3 text-[13px] text-white/80">
                        @if ($mystery['coords'])
                            <li class="flex items-center gap-3"><span class="grid h-8 w-8 place-items-center rounded-full border border-white/20 text-[#F6D6C9]"><x-line-icon name="compass" :size="15" /></span> Clue one: {{ $mystery['coords'] }}</li>
                        @endif
                        @if ($mystery['clue'])
                            <li class="flex items-center gap-3"><span class="grid h-8 w-8 place-items-center rounded-full border border-white/20 text-[#F6D6C9]"><x-line-icon name="star" :size="15" /></span> Clue two: {{ $mystery['clue'] }}</li>
                        @endif
                        @if ($mystery['season'])
                            <li class="flex items-center gap-3"><span class="grid h-8 w-8 place-items-center rounded-full border border-white/20 text-[#F6D6C9]"><x-line-icon name="calendar" :size="15" /></span> Clue three: best between {{ $mystery['season'] }}</li>
                        @endif
                    </ul>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <button type="button" x-show="! revealed" x-on:click="revealed = true" class="dv-btn dv-btn-primary dv-magic-shimmer min-h-[3.2rem] px-6">
                            <x-line-icon name="compass" :size="17" /> Reveal the destination
                        </button>
                        <a x-show="revealed" x-cloak href="{{ $mystery['url'] }}" class="dv-btn dv-btn-primary min-h-[3.2rem] px-6">
                            Begin the journey <x-line-icon name="arrow" :size="17" />
                        </a>
                    </div>
                </div>

                <div class="relative min-h-[20rem] overflow-hidden">
                    <img
                        src="{{ $mystery['image'] }}"
                        alt="The mystery destination"
                        class="absolute inset-0 h-full w-full object-cover transition-all duration-[1400ms] ease-out"
                        :class="revealed ? 'blur-0 scale-100 saturate-100' : 'blur-2xl scale-125 saturate-50'"
                        loading="lazy"
                    >
                    <div class="absolute inset-0 grid place-items-center bg-[#0C1511]/30 transition-opacity duration-700" :class="revealed && 'opacity-0'">
                        <span class="font-dm-serif text-[9rem] leading-none text-white/80 animate-pulse">?</span>
                    </div>
                    <div class="absolute inset-x-6 bottom-6 text-white" x-show="revealed" x-cloak x-transition.opacity.duration.900ms>
                        <p class="text-[10px] font-extrabold uppercase tracking-[0.25em] text-[#F6D6C9]">{{ $mystery['country'] }}</p>
                        <p class="mt-1 font-dm-serif text-[1.4rem] italic">{{ $mystery['tagline'] }}</p>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- ────────────────────────────  Finale  ───────────────────────────── --}}
    <section class="dv-magic-finale">
        <div class="dv-magic-aurora" aria-hidden="true"></div>
        <div class="relative z-10 mx-auto max-w-[44rem] px-6 text-center">
            <p class="dv-eyebrow-light">Here be dragons</p>
            <h2 class="mt-5 font-dm-serif text-[clamp(2.4rem,6vw,4.6rem)] leading-[0.98] text-white">
                Somewhere you’ve never been<br>is waiting for you.
            </h2>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ route('discover') }}#moods" class="dv-btn dv-btn-primary min-h-[3.4rem] px-6 text-[13px]">
                    Travel by feeling <x-line-icon name="arrow" :size="18" />
                </a>
                <a href="{{ route('discover') }}" class="dv-btn dv-magic-glass min-h-[3.4rem] px-6 text-[13px]">
                    <x-line-icon name="compass" :size="18" /> Let the globe choose
                </a>
            </div>
        </div>
    </section>
</div>
