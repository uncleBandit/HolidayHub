{{-- Visual Discovery experiences: everything a guest can go and do, presented as
     a browse grid of reel cards with a full-screen reel feed behind the same
     result set. --}}
<div>
    @if ($isReels)
        {{-- ────────────────────────  Full-screen feed  ───────────────────── --}}
        <section class="dv-reels-screen" aria-label="Experience reels">
            <div class="dv-feed">
                @forelse ($cards as $card)
                    <x-experience.reel-panel :card="$card" />
                @empty
                    <div class="flex h-full flex-col items-center justify-center px-8 text-center">
                        <p class="font-dm-serif text-[1.8rem]">No experiences to watch yet</p>
                        <p class="mt-2 max-w-sm text-[12px] leading-relaxed text-white/70">
                            Nothing matches that filter in the feed. Clear the search to keep browsing.
                        </p>

                        <button
                            type="button"
                            class="dv-btn dv-btn-primary mt-6"
                            wire:click="$set('search', ''); $set('type', 'all')"
                        >Show every experience</button>
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
                    <a href="{{ route('experiences.index', array_filter(['q' => $search, 'type' => $type === 'all' ? null : $type, 'when' => $duration === 'all' ? null : $duration])) }}"
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
                        More experiences
                        <span wire:loading wire:target="nextPage">…</span>
                    </button>
                </div>
            @endif
        </section>
    @else
        @php
            $glow = 'bg-gradient-to-r from-[#FFD7B5] via-[#F6A27F] to-clay bg-clip-text text-transparent';
            $browsing = blank($search) && $type === 'all' && $duration === 'all' && blank($destination) && blank($minPrice) && blank($maxPrice) && $paginator->currentPage() === 1;
            $typeIcons = [
                'safari-wildlife' => 'map', 'ocean-water' => 'waves', 'adventure-trekking' => 'mountain',
                'culture-heritage' => 'leaf', 'food-markets' => 'utensils', 'wellness-retreats' => 'star', 'family-kids' => 'users',
            ];
        @endphp

        {{-- ──────────────────────────────  Hero  ────────────────────────── --}}
        <section
            class="dv-magic-hero"
            x-data="{
                moments: @js(collect($moments)->map(fn ($m) => ['caption' => $m['caption']])->values()),
                active: 0,
                still: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
                init() {
                    this.$nextTick(() => this.play());
                    if (this.still || this.moments.length < 2) return;
                    setInterval(() => { this.active = (this.active + 1) % this.moments.length; this.play() }, 7000);
                },
                play() {
                    this.$el.querySelectorAll('video.dv-magic-slide').forEach((video, i) => {
                        if (i === this.active && ! this.still) { video.play().catch(() => {}) } else { video.pause() }
                    });
                },
                go(i) { this.active = i; this.play() },
            }"
            x-on:mousemove="const r = $el.getBoundingClientRect(); $el.style.setProperty('--mx', ($event.clientX - r.left) + 'px'); $el.style.setProperty('--my', ($event.clientY - r.top) + 'px')"
        >
            @forelse ($moments as $i => $moment)
                <video
                    @class(['dv-magic-slide', 'is-active' => $i === 0])
                    :class="active === {{ $i }} && 'is-active'"
                    src="{{ $moment['video'] }}"
                    poster="{{ $moment['poster'] }}"
                    muted
                    loop
                    playsinline
                    preload="{{ $i === 0 ? 'auto' : 'none' }}"
                    aria-hidden="true"
                ></video>
            @empty
                @if (filled($hero))
                    <img src="{{ $hero['poster'] }}" alt="" class="dv-magic-slide is-active">
                @endif
            @endforelse

            <div class="dv-magic-shade"></div>
            <div class="absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_center,transparent_40%,rgba(5,14,10,0.7)_100%)]" aria-hidden="true"></div>
            <div class="dv-magic-spotlight" aria-hidden="true"></div>

            <div class="dv-magic-particles" aria-hidden="true">
                @php mt_srand(23); @endphp
                @for ($p = 0; $p < 22; $p++)
                    <span style="left: {{ mt_rand(0, 100) }}%; --size: {{ mt_rand(2, 5) }}px; --delay: -{{ mt_rand(0, 140) / 10 }}s; --duration: {{ mt_rand(100, 200) / 10 }}s; --drift: {{ mt_rand(-60, 60) }}px;"></span>
                @endfor
            </div>

            <div class="relative z-10 flex h-full w-full flex-col justify-end px-[5vw] pb-28 md:justify-center md:pb-12">
                <p class="dv-eyebrow-light dv-magic-in" style="--i: 0">Experiences · Once in a lifetime</p>
                <h1 class="dv-display dv-magic-in max-w-[52rem]" style="--i: 1">
                    Some moments<br>only happen <span class="{{ $glow }} pr-2 italic">once.</span>
                </h1>
                <p class="dv-hero-subtitle dv-magic-in" style="--i: 2">
                    Drift over the Mara at sunrise. Sleep under Saharan stars. Swim with the reef. These are the stories you’ll still be telling in twenty years.
                </p>

                <form class="dv-searchbar dv-magic-in" style="--i: 3" role="search" wire:submit="resetPage">
                    <span class="dv-search-icon" aria-hidden="true"><x-line-icon name="star" :size="20" /></span>
                    <span class="dv-search-copy">
                        <input
                            type="search"
                            wire:model.live.debounce.500ms="search"
                            placeholder="What have you always dreamed of doing?"
                            aria-label="Search experiences"
                            class="h-auto"
                        >
                        <small wire:key="experience-result-count">
                            @if (filled($search) && $resultCount > 0)
                                {{ $resultCount }} {{ Str::plural('experience', $resultCount) }} for “{{ $search }}”
                            @else
                                A safari, a summit, a sunset sail…
                            @endif
                        </small>
                    </span>
                    <button type="submit" class="dv-search-go" aria-label="Search experiences"><x-line-icon name="arrow" :size="19" /></button>
                </form>
            </div>

            @if (count($moments) > 1)
                <div class="absolute bottom-7 right-[5vw] z-10 hidden w-[18rem] md:block">
                    <p class="text-[10px] font-extrabold uppercase tracking-[0.25em] text-[#F6D6C9]">Moment <span x-text="String(active + 1).padStart(2, '0')">01</span> / {{ str_pad((string) count($moments), 2, '0', STR_PAD_LEFT) }}</p>
                    <p class="mt-1 font-dm-serif text-[1.3rem] text-white" x-text="moments[active].caption">{{ $moments[0]['caption'] }}</p>
                    <div class="mt-3 flex gap-1.5">
                        @foreach ($moments as $i => $moment)
                            <button type="button" x-on:click="go({{ $i }})" class="dv-magic-progress" :class="{ 'is-on': active === {{ $i }}, 'is-done': active > {{ $i }} }" aria-label="Show {{ $moment['caption'] }}"><span></span></button>
                        @endforeach
                    </div>
                </div>
            @endif

            <a href="#bucket-list" class="dv-magic-cue" aria-label="Scroll to the bucket list"><span></span></a>
        </section>

        {{-- ─────────────────────────  The bucket list  ───────────────────── --}}
        @if ($browsing && count($bucketList))
            <section id="bucket-list" class="dv-section scroll-mt-24">
                <div class="max-w-[44rem]">
                    <p class="dv-eyebrow">The bucket list</p>
                    <h2 class="dv-title">The ones travellers never stop talking about.</h2>
                </div>

                <div class="mt-14 space-y-20 md:space-y-28">
                    @foreach ($bucketList as $rank => $moment)
                        <article class="grid items-center gap-8 md:gap-14 lg:grid-cols-2" wire:key="bucket-{{ $moment['id'] }}">
                            <a href="{{ $moment['url'] }}" @class(['dv-reel-card group block aspect-[4/3] lg:aspect-[5/4]', 'lg:order-2' => $rank % 2 === 1])>
                                @if ($moment['video'])
                                    <video src="{{ $moment['video'] }}" poster="{{ $moment['image'] }}" muted loop playsinline preload="none" data-autoplay></video>
                                @else
                                    <img src="{{ $moment['image'] }}" alt="{{ $moment['alt'] }}" loading="lazy">
                                @endif
                                <span class="dv-reel-play"><x-line-icon name="play" :size="14" filled /></span>
                                <span class="absolute bottom-4 right-5 z-10 font-dm-serif text-[5rem] leading-none text-white/90 drop-shadow-[0_4px_20px_rgba(0,0,0,0.4)] md:text-[7rem]">{{ str_pad((string) ($rank + 1), 2, '0', STR_PAD_LEFT) }}</span>
                            </a>

                            <div @class(['lg:order-1' => $rank % 2 === 1])>
                                <p class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] font-extrabold uppercase tracking-[0.22em] text-clay">
                                    <span class="shrink-0 whitespace-nowrap font-dm-serif text-[1.6rem] normal-case tracking-normal [-webkit-text-stroke:1px_#D85F3C] text-transparent">Nº {{ $rank + 1 }}</span>
                                    <span>{{ $moment['category_label'] }} · {{ $moment['place'] }}</span>
                                </p>
                                <h3 class="mt-3 font-dm-serif text-[clamp(2rem,3.6vw,3.2rem)] leading-[1.02] text-forest">{{ $moment['name'] }}</h3>
                                @if ($moment['caption'])
                                    <p class="mt-4 max-w-[34rem] text-[15px] leading-[1.8] text-muted">{{ $moment['caption'] }}</p>
                                @endif

                                @if ($moment['highlights'])
                                    <ul class="mt-6 grid gap-2.5 sm:grid-cols-2">
                                        @foreach ($moment['highlights'] as $highlight)
                                            <li class="flex items-center gap-2.5 text-[13px] font-semibold text-ink"><span class="text-clay">✦</span> {{ $highlight }}</li>
                                        @endforeach
                                    </ul>
                                @endif

                                <div class="mt-8 flex flex-wrap items-center gap-4">
                                    <a href="{{ $moment['url'] }}" class="dv-btn dv-btn-primary min-h-[3.3rem] px-6 text-[13px]">Make it happen <x-line-icon name="arrow" :size="17" /></a>
                                    <p class="text-[12px] text-muted">
                                        @if ($moment['price'] > 0)
                                            From <b class="font-dm-serif text-[1.3rem] font-normal text-ink">KSh {{ number_format($moment['price']) }}</b> per person
                                        @endif
                                        @if ($moment['rating'] > 0)
                                            · <span class="text-clay">★</span> {{ number_format($moment['rating'], 1) }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ─────────────────────  Choose your adventure  ──────────────────── --}}
        <section id="moments" class="dv-section scroll-mt-24 bg-surface">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="dv-eyebrow">Choose your adventure</p>
                    <h2 class="dv-title">{{ $types[$type] === 'All experiences' ? 'Every moment worth the journey' : $types[$type] }}</h2>
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
                        <x-line-icon :name="$typeIcons[$value] ?? 'compass'" :size="17" /> {{ $label }}
                    </button>
                @endforeach
            </div>

            <div class="mt-3 flex flex-wrap gap-2" data-no-reveal>
                @foreach ($durations as $value => $label)
                    <button
                        type="button"
                        wire:click="setDuration('{{ $value }}')"
                        @class([
                            'rounded-full border px-3.5 py-1.5 text-[11px] font-bold transition-colors',
                            'border-clay bg-clay text-white' => $duration === $value,
                            'border-line bg-white text-muted hover:border-forest hover:text-forest' => $duration !== $value,
                        ])
                        aria-pressed="{{ $duration === $value ? 'true' : 'false' }}"
                    ><x-line-icon name="clock" :size="11" /> {{ $label }}</button>
                @endforeach
            </div>

            {{-- Filters: place, price per person, sort. --}}
            <div class="dv-filter-bar" wire:loading.class="opacity-50" wire:target="minPrice,maxPrice,sortBy,clearFilters">
                <span class="dv-filter-count" wire:key="experience-total">
                    {{ $resultCount }} {{ Illuminate\Support\Str::plural('experience', $resultCount) }}
                    @if ($paginator->hasPages())
                        · page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}
                    @endif
                </span>

                @if (count($places) > 0)
                    <div class="dv-select-wrap">
                        <label for="experiences-place">Where</label>

                        <select id="experiences-place" class="dv-select" wire:model.live="destination">
                            <option value="">Everywhere</option>
                            @foreach ($places as $place)
                                <option value="{{ $place }}">{{ $place }}</option>
                            @endforeach
                        </select>

                        <x-line-icon name="chevron-down" :size="15" />
                    </div>
                @endif

                <div class="dv-price">
                    <label for="experiences-min-price">Per person</label>

                    <input
                        id="experiences-min-price"
                        type="number"
                        inputmode="numeric"
                        min="0"
                        step="500"
                        placeholder="From"
                        aria-label="Minimum price per person"
                        wire:model.live.debounce.600ms="minPrice"
                    >

                    <span aria-hidden="true">–</span>

                    <input
                        id="experiences-max-price"
                        type="number"
                        inputmode="numeric"
                        min="0"
                        step="500"
                        placeholder="To"
                        aria-label="Maximum price per person"
                        wire:model.live.debounce.600ms="maxPrice"
                    >
                </div>

                <div class="dv-select-wrap">
                    <label for="experiences-sort">Sort</label>

                    <select id="experiences-sort" class="dv-select" wire:model.live="sort">
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
                    x-show="{{ filled($search) || filled($minPrice) || filled($maxPrice) || filled($destination) || $duration !== 'all' || $sort !== 'recommended' ? 'true' : 'false' }}"
                    x-cloak
                >Clear filters</button>
            </div>


            <div
                class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3"
                wire:loading.class="opacity-50"
                wire:target="search,setType,setDuration,setView,sortBy,clearFilters,gotoPage,nextPage,previousPage"
            >
                @forelse ($cards as $card)
                    <x-experience.moment-card :card="$card" wire:key="moment-{{ $card['id'] }}" />
                @empty
                    <div class="dv-magic-night col-span-full flex flex-col items-center rounded-[1.4rem] px-8 py-16 text-center text-white">
                        <x-line-icon name="compass" :size="32" />
                        <p class="mt-4 font-dm-serif text-[1.8rem]">That adventure hasn’t been written yet</p>
                        <p class="mx-auto mt-2 max-w-md text-[14px] leading-relaxed text-white/70">Nothing matches those filters. Loosen them a little — the next great story is probably one click away.</p>
                        <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                            <button type="button" class="dv-btn dv-btn-primary" wire:click="clearFilters" x-on:click="$wire.set('type', 'all')">Show every experience</button>
                            <a href="{{ route('destination.index') }}" class="dv-btn dv-magic-glass">Browse destinations</a>
                        </div>
                    </div>
                @endforelse
            </div>

            @if ($paginator->hasPages())
                <div class="mt-12">{{ $paginator->links() }}</div>
            @endif
        </section>

        {{-- ────────────────────────────  Finale  ─────────────────────────── --}}
        <section class="dv-magic-finale">
            <div class="dv-magic-aurora" aria-hidden="true"></div>
            <div class="relative z-10 mx-auto max-w-[46rem] px-6 text-center">
                <p class="dv-eyebrow-light">Once in a lifetime</p>
                <h2 class="mt-5 font-dm-serif text-[clamp(2.4rem,6vw,4.8rem)] leading-[0.98] text-white">
                    One day you’ll tell<br>this <span class="{{ $glow }} pr-2 italic">story.</span>
                </h2>
                <p class="mx-auto mt-5 max-w-[30rem] text-[14px] leading-relaxed text-white/65">Make sure it starts with “I actually did it.”</p>
                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    <a href="#moments" class="dv-btn dv-btn-primary min-h-[3.4rem] px-6 text-[13px]">Find your moment <x-line-icon name="arrow" :size="18" /></a>
                    <a href="{{ route('discover') }}" class="dv-btn dv-magic-glass min-h-[3.4rem] px-6 text-[13px]"><x-line-icon name="compass" :size="18" /> Let the globe choose</a>
                </div>
            </div>
        </section>
    @endif
</div>
