<div>
    {{-- ─────────────────────────────  Hero  ────────────────────────────── --}}
    <section
        class="dv-magic-hero"
        x-data="{
            slides: @js($hero),
            words: @js($wonders),
            active: 0,
            word: 0,
            timer: null,
            still: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
            init() {
                if (this.still) return;
                this.timer = setInterval(() => this.next(), 6500);
                setInterval(() => this.word = (this.word + 1) % this.words.length, 2600);
            },
            next() { this.active = (this.active + 1) % this.slides.length },
            go(i) {
                this.active = i;
                clearInterval(this.timer);
                if (! this.still) this.timer = setInterval(() => this.next(), 6500);
            },
            spot(e) {
                const r = this.$el.getBoundingClientRect();
                this.$el.style.setProperty('--mx', (e.clientX - r.left) + 'px');
                this.$el.style.setProperty('--my', (e.clientY - r.top) + 'px');
            },
        }"
        x-on:mousemove="spot($event)"
    >
        @foreach ($hero as $i => $slide)
            <img
                src="{{ $slide['image'] }}"
                alt="{{ $slide['name'] }}, {{ $slide['country'] }}"
                class="dv-magic-slide"
                :class="active === {{ $i }} && 'is-active'"
                @if ($i === 0) loading="eager" @else loading="lazy" @endif
            >
        @endforeach

        <div class="dv-magic-shade"></div>
        <div class="dv-magic-spotlight" aria-hidden="true"></div>

        <div class="dv-magic-particles" aria-hidden="true">
            @php mt_srand(7); @endphp
            @for ($p = 0; $p < 24; $p++)
                <span style="left: {{ mt_rand(0, 100) }}%; --size: {{ mt_rand(2, 6) }}px; --delay: -{{ mt_rand(0, 140) / 10 }}s; --duration: {{ mt_rand(110, 220) / 10 }}s; --drift: {{ mt_rand(-60, 60) }}px;"></span>
            @endfor
        </div>

        <div class="relative z-10 flex h-full w-full flex-col justify-end px-[5vw] pb-28 md:justify-center md:pb-16">
            <p class="dv-eyebrow-light dv-magic-in" style="--i: 0">Discover · Travel by feeling</p>

            <h1 class="dv-display max-w-[56rem] dv-magic-in" style="--i: 1">
                Go where it<br>feels
                <span class="dv-wonder">
                    @foreach ($wonders as $w => $wonder)
                        <span :class="word === {{ $w }} && 'is-on'" @class(['is-on' => $w === 0])>{{ $wonder }}</span>
                    @endforeach
                </span>
            </h1>

            <p class="dv-hero-subtitle dv-magic-in" style="--i: 2">
                Skip the search box. Tell us how you want to feel, or let the globe choose for you.
            </p>

            <div class="mt-8 flex flex-wrap items-center gap-3 dv-magic-in" style="--i: 3">
                <a href="#moods" class="dv-btn dv-btn-primary min-h-[3.4rem] px-6 text-[13px]">
                    Find my mood <x-line-icon name="arrow" :size="18" />
                </a>
                <button
                    type="button"
                    x-on:click="$dispatch('surprise')"
                    class="dv-btn dv-magic-glass min-h-[3.4rem] px-6 text-[13px]"
                >
                    <x-line-icon name="compass" :size="18" /> Surprise me
                </button>
            </div>
        </div>

        {{-- Slide caption and story-style progress --}}
        <div class="absolute bottom-7 right-[5vw] z-10 hidden w-[19rem] md:block">
            <a :href="slides[active].url" class="flex items-center gap-2.5 text-white">
                <x-line-icon name="map" :size="17" />
                <span class="flex flex-col">
                    <b class="text-[13px]" x-text="slides[active].name">{{ $hero[0]['name'] }}</b>
                    <span class="text-[11px] text-white/70" x-text="slides[active].line">{{ $hero[0]['line'] }}</span>
                </span>
            </a>
            <div class="mt-4 flex gap-1.5">
                @foreach ($hero as $i => $slide)
                    <button
                        type="button"
                        x-on:click="go({{ $i }})"
                        class="dv-magic-progress"
                        :class="{ 'is-on': active === {{ $i }}, 'is-done': active > {{ $i }} }"
                        aria-label="Show {{ $slide['name'] }}"
                    ><span></span></button>
                @endforeach
            </div>
        </div>

        <a href="#moods" class="dv-magic-cue" aria-label="Scroll to moods"><span></span></a>
    </section>

    {{-- ────────────────────────────  Moods  ────────────────────────────── --}}
    <section id="moods" class="dv-section dv-magic-moods scroll-mt-24" style="--tint: {{ $current['tint'] }}">
        <div class="relative">
            <p class="dv-eyebrow">Travel by feeling</p>
            <h2 class="dv-title">How do you want to feel?</h2>

            <div class="dv-category-row mt-7" role="tablist" aria-label="Moods">
                @foreach ($moods as $key => $item)
                    <button
                        type="button"
                        role="tab"
                        wire:click="setMood('{{ $key }}')"
                        @class(['dv-category', 'dv-category-active' => $mood === $key])
                        aria-selected="{{ $mood === $key ? 'true' : 'false' }}"
                    >
                        <x-line-icon :name="$item['icon']" :size="19" /> {{ $item['label'] }}
                    </button>
                @endforeach
            </div>

            <div wire:key="mood-{{ $mood }}" class="mt-9">
                <p class="dv-magic-rise max-w-[40rem] font-dm-serif text-[clamp(1.6rem,3vw,2.4rem)] italic leading-[1.15] text-forest" style="--i: 0">
                    “{{ $current['line'] }}”
                </p>

                <div class="mt-8 grid gap-4 lg:grid-cols-[1.35fr_1fr] lg:grid-rows-2" style="grid-auto-rows: minmax(15rem, auto)">
                    @foreach ($current['places'] as $i => $place)
                        <a
                            href="{{ $place['url'] }}"
                            @class([
                                'dv-card group dv-magic-rise',
                                'min-h-[26rem] lg:row-span-2 lg:min-h-[34rem]' => $i === 0,
                                'min-h-[15rem]' => $i > 0,
                                'lg:row-span-2' => $i === 1 && count($current['places']) === 2,
                            ])
                            style="--i: {{ $i + 1 }}"
                        >
                            <img src="{{ $i === 0 ? $place['image'] : $place['thumb'] }}" alt="{{ $place['name'] }}, {{ $place['country'] }}" loading="lazy">
                            <div class="dv-card-gradient"></div>
                            <div class="dv-card-body pr-14">
                                <p class="dv-card-kicker">{{ $place['country'] }}</p>
                                <p @class(['dv-card-name', 'lg:text-[2.6rem]' => $i === 0])>{{ $place['name'] }}</p>
                                <p class="dv-card-sub">{{ $place['line'] }}</p>
                            </div>
                            <span class="dv-card-arrow"><x-line-icon name="arrow" :size="19" /></span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ──────────────────────────  Surprise me  ─────────────────────────── --}}
    <section class="px-5 pb-6 md:px-[max(5vw,calc((100vw-1350px)/2))]">
        <div
            id="surprise"
            class="dv-magic-night grid items-center gap-10 overflow-hidden rounded-[1.6rem] px-7 py-12 md:px-14 md:py-16 lg:grid-cols-[1fr_auto]"
            x-data="{
                deck: @js($deck),
                card: null,
                state: 'idle',
                deal() {
                    if (this.state === 'shuffling') return;
                    this.state = 'shuffling';
                    setTimeout(() => {
                        let next;
                        do { next = this.deck[Math.floor(Math.random() * this.deck.length)] }
                        while (this.deck.length > 1 && next === this.card);
                        this.card = next;
                        const img = new Image();
                        img.onload = img.onerror = () => this.state = 'revealed';
                        img.src = next.thumb;
                    }, 1100);
                },
            }"
            x-on:surprise.window="deal(); $el.scrollIntoView({ behavior: 'smooth', block: 'center' })"
        >
            <div class="relative z-10 max-w-[32rem] text-white">
                <p class="dv-eyebrow-light">Can’t decide?</p>
                <h2 class="mt-2 font-dm-serif text-[clamp(2.2rem,4.5vw,3.8rem)] leading-[1]">Let the globe choose.</h2>
                <p class="mt-4 text-[14px] leading-relaxed text-white/70">
                    One tap deals you an escape from every corner of the map. Keep shuffling until something makes your heart skip.
                </p>
                <button type="button" x-on:click="deal()" class="dv-btn dv-btn-primary dv-magic-shimmer mt-7 min-h-[3.4rem] px-6 text-[13px]">
                    <span :class="state === 'shuffling' && 'animate-spin'" class="inline-grid"><x-line-icon name="compass" :size="18" /></span>
                    <span x-text="state === 'idle' ? 'Surprise me' : (state === 'shuffling' ? 'Shuffling the globe…' : 'Deal again')">Surprise me</span>
                </button>
            </div>

            <div class="dv-flip relative z-10 mx-auto" :class="{ 'is-revealed': state === 'revealed', 'is-shuffling': state === 'shuffling' }">
                <div class="dv-flip-inner">
                    {{-- Card back --}}
                    <div class="dv-flip-face dv-flip-back">
                        <span class="dv-brand-mark scale-[2.2] text-[#F6D6C9]" aria-hidden="true"><span></span><span></span></span>
                        <p class="mt-12 font-dm-serif text-[1.5rem] text-white">Your next escape</p>
                        <p class="mt-1 text-[11px] font-bold uppercase tracking-[0.15em] text-white/55">Tap “Surprise me”</p>
                    </div>

                    {{-- Card front --}}
                    <a :href="card?.url ?? '#'" class="dv-flip-face dv-flip-front dv-card">
                        <template x-if="card">
                            <img :src="card.thumb" :alt="card.name">
                        </template>
                        <div class="dv-card-gradient"></div>
                        <span class="absolute left-4 top-4 rounded-full border border-white/30 bg-white/15 px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-[0.1em] backdrop-blur-md" x-text="card?.mood"></span>
                        <div class="dv-card-body">
                            <p class="dv-card-kicker" x-text="card?.country"></p>
                            <p class="dv-card-name text-[2rem]" x-text="card?.name"></p>
                            <p class="dv-card-sub" x-text="card?.line"></p>
                            <span class="mt-4 inline-flex items-center gap-1.5 rounded-[0.7rem] bg-clay px-3 py-2 text-[11px] font-extrabold">
                                Take me there <x-line-icon name="arrow" :size="15" />
                            </span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- ───────────────────────  Stories in motion  ─────────────────────── --}}
    @if ($reels->isNotEmpty())
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

            <div class="-mx-5 mt-7 flex snap-x snap-mandatory gap-4 overflow-x-auto px-5 pb-2 no-scrollbar md:mx-0 md:px-0">
                @foreach ($reels as $reel)
                    <a
                        href="{{ $reel['url'] }}"
                        class="dv-reel-card group h-[26rem] w-[15rem] flex-none snap-start md:h-[30rem] md:w-[17rem]"
                    >
                        <video src="{{ $reel['video'] }}" poster="{{ $reel['poster'] }}" muted loop playsinline preload="none" data-autoplay></video>
                        <span class="dv-reel-play"><x-line-icon name="play" :size="14" filled /></span>
                        <div class="dv-reel-card-body">
                            <p class="dv-card-kicker">{{ $reel['place'] }}</p>
                            <p class="dv-card-name text-[1.25rem]">{{ $reel['title'] }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ──────────────────────  Catalogue destinations  ─────────────────── --}}
    @if ($destinations->isNotEmpty())
        <section class="dv-section bg-surface">
            <p class="dv-eyebrow">Worth the journey</p>
            <h2 class="dv-title">Places calling your name</h2>

            <div class="mt-7 grid gap-4 sm:grid-cols-2 lg:grid-cols-3" style="perspective: 1200px">
                @foreach ($destinations as $destination)
                    <a
                        href="{{ $destination['url'] }}"
                        class="dv-card dv-tilt group h-[22rem]"
                        x-data
                        x-on:mousemove="
                            const r = $el.getBoundingClientRect();
                            $el.style.setProperty('--rx', ((($event.clientY - r.top) / r.height) - 0.5) * -10 + 'deg');
                            $el.style.setProperty('--ry', ((($event.clientX - r.left) / r.width) - 0.5) * 12 + 'deg');
                        "
                        x-on:mouseleave="$el.style.setProperty('--rx', '0deg'); $el.style.setProperty('--ry', '0deg')"
                    >
                        <img src="{{ $destination['image'] }}" alt="{{ $destination['name'] }}" loading="lazy">
                        <div class="dv-card-gradient"></div>
                        <div class="dv-card-body pr-14">
                            <p class="dv-card-name">{{ $destination['name'] }}</p>
                            <p class="dv-card-sub">{{ $destination['sub'] }}</p>
                        </div>
                        <span class="dv-card-arrow"><x-line-icon name="arrow" :size="19" /></span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ────────────────────────────  Finale  ───────────────────────────── --}}
    <section class="dv-magic-finale">
        <div class="dv-magic-aurora" aria-hidden="true"></div>
        <div class="relative z-10 mx-auto max-w-[44rem] px-6 text-center">
            <span class="dv-brand-mark mx-auto block scale-150 text-[#F6D6C9]" aria-hidden="true"><span></span><span></span></span>
            <h2 class="mt-8 font-dm-serif text-[clamp(2.4rem,6vw,4.6rem)] leading-[0.98] text-white">
                Go somewhere that<br>stays with you.
            </h2>
            <p class="mx-auto mt-5 max-w-[28rem] text-[14px] leading-relaxed text-white/65">
                Remarkable stays and experiences, chosen by people who’ve been there.
            </p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ route('stays.index') }}" class="dv-btn dv-btn-primary min-h-[3.4rem] px-6 text-[13px]">
                    Find a stay <x-line-icon name="arrow" :size="18" />
                </a>
                <a href="{{ route('experiences.index') }}" class="dv-btn dv-magic-glass min-h-[3.4rem] px-6 text-[13px]">
                    Explore experiences
                </a>
            </div>
        </div>
    </section>
</div>
