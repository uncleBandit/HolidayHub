<div x-data="{ story: null }">
    {{-- ─────────────────────────────  Hero  ────────────────────────────── --}}
    <section
        class="dv-magic-hero"
        x-data="{
            slides: @js(collect($heroDestinations)->map(fn ($d) => ['name' => $d['name'], 'country' => $d['country']])->values()),
            active: 0,
            init() {
                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || this.slides.length < 2) return;
                setInterval(() => this.active = (this.active + 1) % this.slides.length, 7000);
            },
        }"
        x-on:mousemove="const r = $el.getBoundingClientRect(); $el.style.setProperty('--mx', ($event.clientX - r.left) + 'px'); $el.style.setProperty('--my', ($event.clientY - r.top) + 'px')"
    >
        @foreach ($heroDestinations as $index => $destination)
            <img
                src="{{ $destination['image'] }}"
                alt="{{ $destination['name'] }}, {{ $destination['country'] }}"
                @class(['dv-magic-slide', 'is-active' => $index === 0])
                :class="active === {{ $index }} && 'is-active'"
                loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
            >
        @endforeach

        <div class="dv-magic-shade"></div>
        <div class="dv-magic-spotlight" aria-hidden="true"></div>

        <div class="relative z-10 flex h-full w-full flex-col justify-end px-[5vw] pb-24 md:justify-center md:pb-10">
            <p class="dv-eyebrow-light dv-magic-in" style="--i: 0">Curated for you</p>
            <h1 class="dv-display dv-magic-in max-w-[50rem]" style="--i: 1">Where are you<br>escaping to?</h1>
            <p class="dv-hero-subtitle dv-magic-in" style="--i: 2">
                Discover extraordinary places, unforgettable stays, and stories worth living.
            </p>

            {{-- Trip search --}}
            <form
                wire:submit="runTripSearch"
                class="dv-magic-in mt-8 grid w-full max-w-[56rem] grid-cols-1 gap-1 rounded-[1.2rem] bg-white/95 p-2.5 text-ink shadow-[0_20px_60px_rgba(20,36,28,0.25)] backdrop-blur md:grid-cols-[1.3fr_1fr_0.9fr_auto]"
                style="--i: 3"
            >
                <label class="flex items-center gap-3 rounded-[0.9rem] px-4 py-3 transition-colors hover:bg-cream">
                    <span class="grid h-10 w-10 flex-none place-items-center rounded-[0.8rem] bg-cream text-clay"><x-line-icon name="map" :size="19" /></span>
                    <span class="flex min-w-0 flex-1 flex-col gap-0.5">
                        <span class="text-[10px] font-extrabold uppercase tracking-[0.12em] text-muted">Where</span>
                        <input type="text" wire:model.live.debounce.400ms="tripWhere" placeholder="Search your next escape" class="w-full border-0 bg-transparent p-0 text-[14px] font-bold text-ink placeholder:font-semibold placeholder:text-ink/70 focus:ring-0">
                    </span>
                </label>

                <label class="flex items-center gap-3 rounded-[0.9rem] px-4 py-3 transition-colors hover:bg-cream">
                    <span class="grid h-10 w-10 flex-none place-items-center rounded-[0.8rem] bg-cream text-clay"><x-line-icon name="calendar" :size="19" /></span>
                    <span class="flex min-w-0 flex-1 flex-col gap-0.5">
                        <span class="text-[10px] font-extrabold uppercase tracking-[0.12em] text-muted">When</span>
                        <input type="date" wire:model="tripWhen" class="w-full border-0 bg-transparent p-0 text-[14px] font-bold text-ink focus:ring-0">
                    </span>
                </label>

                <label class="flex items-center gap-3 rounded-[0.9rem] px-4 py-3 transition-colors hover:bg-cream">
                    <span class="grid h-10 w-10 flex-none place-items-center rounded-[0.8rem] bg-cream text-clay"><x-line-icon name="users" :size="19" /></span>
                    <span class="flex min-w-0 flex-1 flex-col gap-0.5">
                        <span class="text-[10px] font-extrabold uppercase tracking-[0.12em] text-muted">Travelers</span>
                        <input type="number" min="1" wire:model="tripGuests" placeholder="Add guests" class="w-full border-0 bg-transparent p-0 text-[14px] font-bold text-ink placeholder:font-semibold placeholder:text-ink/70 focus:ring-0">
                    </span>
                </label>

                <button type="submit" class="dv-btn dv-btn-primary min-h-[3.6rem] rounded-[0.9rem] px-7 text-[13px]">
                    <x-line-icon name="search" :size="18" /> Search
                </button>
            </form>
        </div>

        {{-- Current place --}}
        <div class="absolute bottom-7 right-[5vw] z-10 hidden items-center gap-2.5 text-white lg:flex">
            <x-line-icon name="map" :size="17" />
            <div class="flex flex-col">
                <b class="text-[13px]" x-text="slides[active].name">{{ $heroDestinations[0]['name'] ?? '' }}</b>
                <span class="text-[11px] text-white/70" x-text="slides[active].country">{{ $heroDestinations[0]['country'] ?? '' }}</span>
            </div>
        </div>
    </section>

    {{-- ──────────────────────  Stories in motion  ──────────────────────── --}}
    @if (count($reels))
        <section class="dv-section">
            <div class="flex items-end justify-between gap-6">
                <div>
                    <p class="dv-eyebrow">Discover in motion</p>
                    <h2 class="dv-title">Stories that take you places</h2>
                </div>
                <a href="{{ route('stays.index') }}?view=reels" class="flex flex-none items-center gap-2 text-[12px] font-extrabold text-clay transition-colors hover:text-clay-dark">
                    Watch all <x-line-icon name="arrow" :size="18" />
                </a>
            </div>

            <div class="dv-reel-grid mt-7">
                @foreach ($reels as $index => $reel)
                    <a href="{{ route('stays.index') }}?view=reels" @class(['dv-reel-card group', 'max-md:hidden' => $index === 2])>
                        <video src="{{ $reel['video'] }}" poster="{{ $reel['poster'] }}" muted loop playsinline preload="none" data-autoplay></video>
                        <span class="dv-reel-play"><x-line-icon name="play" :size="14" filled /></span>
                        <div class="dv-reel-card-body">
                            <p class="dv-card-kicker">{{ $reel['kicker'] }}</p>
                            <p class="dv-card-name text-[clamp(1.1rem,2vw,1.5rem)]">{{ $reel['title'] }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ────────────────────────  Destinations  ─────────────────────────── --}}
    <section class="dv-section bg-cream">
        <div class="flex items-end justify-between gap-6">
            <div>
                <p class="dv-eyebrow">Worth the journey</p>
                <h2 class="dv-title">Places calling your name</h2>
            </div>
            <a href="{{ route('destination.index') }}" class="flex flex-none items-center gap-2 text-[12px] font-extrabold text-clay transition-colors hover:text-clay-dark">
                Explore all <x-line-icon name="arrow" :size="18" />
            </a>
        </div>

        <div class="mt-7 grid gap-4 md:grid-cols-[1.25fr_1fr_1fr]">
            @foreach ($heroDestinations as $index => $destination)
                <article @class(['dv-card group', 'h-[24rem] md:h-[29rem]' => $index === 0, 'h-[15rem] md:h-[24rem] md:self-end' => $index > 0])>
                    <img src="{{ $destination['image'] }}" alt="{{ $destination['name'] }}, {{ $destination['country'] }}" loading="lazy">
                    <div class="dv-card-gradient"></div>
                    <span class="dv-foryou-reason">{{ $destination['tag'] }}</span>

                    @if ($index === 0)
                        <button
                            type="button"
                            x-on:click='story = { name: @js($destination["name"]), country: @js($destination["country"]), meta: @js($destination["meta"]), image: @js($destination["image"]) }'
                            class="absolute left-1/2 top-[44%] z-10 grid h-14 w-14 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full border border-white/50 bg-white/20 text-white backdrop-blur-md transition-transform hover:scale-110"
                            aria-label="Watch the {{ $destination['name'] }} story"
                        >
                            <x-line-icon name="play" :size="20" filled />
                        </button>
                    @endif

                    <a href="{{ $destination['url'] }}" class="dv-card-body pr-14">
                        <p class="font-dm-serif text-[clamp(1.6rem,3vw,2.2rem)] leading-none">{{ $destination['name'] }}</p>
                        <p class="mt-1.5 text-[11px] text-white/75">{{ $destination['country'] }}@if ($destination['meta']) · {{ $destination['meta'] }}@endif</p>
                    </a>
                    <a href="{{ $destination['url'] }}" class="dv-card-arrow" aria-label="Explore {{ $destination['name'] }}"><x-line-icon name="arrow" :size="19" /></a>
                </article>
            @endforeach
        </div>
    </section>

    {{-- ──────────────────────  Handpicked stays  ───────────────────────── --}}
    <section class="dv-section bg-surface">
        <div class="flex items-end justify-between gap-6">
            <div>
                <p class="dv-eyebrow">Handpicked for you</p>
                <h2 class="dv-title">Stay somewhere remarkable</h2>
            </div>
            <a href="{{ route('stays.index') }}" class="flex flex-none items-center gap-2 text-[12px] font-extrabold text-clay transition-colors hover:text-clay-dark">
                <span class="max-sm:hidden">View all stays</span> <x-line-icon name="arrow" :size="18" />
            </a>
        </div>

        <div class="dv-category-row mt-7" role="tablist" aria-label="Stay categories" data-no-reveal>
            @foreach ($categories as $key => $label)
                <button
                    type="button"
                    role="tab"
                    wire:click="selectCategory('{{ $key }}')"
                    @class(['dv-category py-3', 'dv-category-active' => $category === $key])
                    aria-selected="{{ $category === $key ? 'true' : 'false' }}"
                >{{ $label }}</button>
            @endforeach
        </div>

        <div class="-mx-5 mt-6 flex snap-x snap-mandatory gap-4 overflow-x-auto px-5 pb-2 no-scrollbar sm:mx-0 sm:grid sm:grid-cols-2 sm:overflow-visible sm:px-0 lg:grid-cols-3" wire:loading.class="opacity-60" wire:target="selectCategory">
            @foreach ($stays as $stay)
                <article class="dv-panel group w-[80vw] flex-none snap-start overflow-hidden transition-shadow duration-300 hover:shadow-[0_28px_60px_-22px_rgba(20,36,28,0.45)] sm:w-auto" wire:key="stay-{{ $stay['slug'] }}">
                    <div class="relative aspect-[1.25] overflow-hidden">
                        <img src="{{ $stay['image'] }}" alt="{{ $stay['name'] }}" class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-[1.05]" loading="lazy">
                        <span class="absolute left-3.5 top-3.5 rounded-full bg-white/90 px-2.5 py-1.5 text-[10px] font-extrabold text-forest backdrop-blur-md">{{ $stay['badge'] }}</span>
                        <button
                            type="button"
                            wire:click.stop='toggleSave(@js($stay["slug"]))'
                            @class([
                                'absolute right-3.5 top-3.5 z-10 grid h-10 w-10 place-items-center rounded-full backdrop-blur-md transition-all duration-200 hover:scale-110',
                                'bg-clay text-white' => $this->isSaved($stay['slug']),
                                'bg-black/35 text-white' => ! $this->isSaved($stay['slug']),
                            ])
                            aria-label="{{ $this->isSaved($stay['slug']) ? 'Remove' : 'Save' }} {{ $stay['name'] }}"
                            aria-pressed="{{ $this->isSaved($stay['slug']) ? 'true' : 'false' }}"
                        >
                            <x-line-icon name="heart" :size="18" :filled="$this->isSaved($stay['slug'])" />
                        </button>
                        @if ($stay['media_count'] > 1)
                            <span class="absolute bottom-3.5 right-3.5 rounded-full bg-black/45 px-2.5 py-1 text-[10px] font-bold text-white backdrop-blur-md">1 / {{ $stay['media_count'] }}</span>
                        @endif
                    </div>

                    <a href="{{ $stay['url'] }}" class="flex flex-col gap-1.5 p-4 pb-5">
                        @if ($stay['rating'])
                            <span class="flex items-center gap-1 text-[11px] font-extrabold text-clay">
                                <x-line-icon name="star" :size="13" filled /> {{ $stay['rating'] }}
                                <span class="font-medium text-muted">({{ $stay['reviews'] }})</span>
                            </span>
                        @endif
                        <span class="truncate font-dm-serif text-[1.3rem] leading-tight text-ink transition-colors group-hover:text-clay">{{ $stay['name'] }}</span>
                        <span class="text-[11px] text-muted">{{ $stay['location'] }}</span>
                        <span class="mt-1 text-[12px] font-extrabold text-ink">
                            @if ($stay['price'])
                                From {{ $stay['price'] }} <span class="font-medium text-muted">/ night</span>
                            @else
                                Ask for rates
                            @endif
                        </span>
                    </a>
                </article>
            @endforeach
        </div>
    </section>

    {{-- ─────────────────────────  Field journal  ───────────────────────── --}}
    <section class="px-5 pb-10 md:px-[max(5vw,calc((100vw-1350px)/2))]">
        <div class="dv-magic-night grid min-h-[30rem] overflow-hidden rounded-[1.6rem] text-white lg:grid-cols-[0.9fr_1.2fr]">
            <div class="relative z-10 flex flex-col items-start justify-center px-8 py-12 md:px-14">
                <p class="dv-eyebrow-light">The field journal · Issue 08</p>
                <h2 class="mt-4 font-dm-serif text-[clamp(2.4rem,5vw,4.2rem)] leading-[0.98]">A slower way<br>through the tropics.</h2>
                <p class="mt-5 max-w-[26rem] text-[14px] leading-relaxed text-white/70">
                    Follow the people who keep the old crafts alive, through mountain towns, tea houses, and the rituals that make a place feel like home.
                </p>
                <a href="{{ route('destination.index') }}" class="dv-btn dv-magic-glass mt-7">
                    Read the story <x-line-icon name="arrow" :size="17" />
                </a>
            </div>
            <div class="relative min-h-[18rem] overflow-hidden">
                <img src="{{ $journalImage }}" alt="A quiet traditional street lined with wooden houses" class="absolute inset-0 h-full w-full object-cover transition-transform duration-[1.2s] hover:scale-[1.04]" loading="lazy">
            </div>
        </div>
    </section>

    {{-- Destination story modal --}}
    <div
        x-show="story"
        x-cloak
        x-transition.opacity
        x-on:click.self="story = null"
        x-on:keydown.escape.window="story = null"
        class="fixed inset-0 z-[80] grid place-items-center bg-[#0C1511]/75 p-5 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
    >
        <div x-show="story" x-transition.scale.95 class="w-full max-w-2xl overflow-hidden rounded-[1.4rem] bg-surface shadow-2xl">
            <template x-if="story">
                <div>
                    <img :src="story.image" :alt="story.name" class="aspect-[16/9] w-full object-cover">
                    <div class="flex items-start justify-between gap-6 p-7">
                        <div>
                            <p class="dv-eyebrow">Field note</p>
                            <h3 class="mt-2 font-dm-serif text-4xl leading-none text-forest" x-text="story.name"></h3>
                            <p class="mt-3 max-w-sm text-[13px] leading-relaxed text-muted" x-text="story.meta"></p>
                            <p class="mt-1 text-[11px] font-extrabold uppercase tracking-[0.12em] text-clay" x-text="story.country"></p>
                        </div>
                        <button type="button" x-on:click="story = null" class="grid h-10 w-10 place-items-center rounded-full border border-line text-muted transition-colors hover:border-forest hover:text-forest" aria-label="Close story">
                            <x-line-icon name="close" />
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
