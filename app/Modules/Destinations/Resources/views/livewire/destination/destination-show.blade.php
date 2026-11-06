{{-- Destination page, told as an expedition: a cinematic full-viewport hero with
     Ken Burns slides, embers and a cursor spotlight; field notes; the legend;
     numbered secrets; where to sleep under the stars; experiences; packages;
     a gallery mosaic; tales from travellers and a night-sky finale. --}}
@php
    $isUsable = fn ($image) => is_string($image)
        && filled($image)
        && ! \Illuminate\Support\Str::contains($image, ['placeholder', 'placehold', 'picsum', 'lorempixel', 'source.unsplash']);
    $toUrl = fn ($image) => \Illuminate\Support\Str::startsWith($image, ['http://', 'https://', '/'])
        ? $image
        : asset('storage/'.ltrim($image, '/'));

    $photos = collect((array) ($destination->gallery ?? []))
        ->prepend($destination->image_url)
        ->push($destination->thumbnail)
        ->filter($isUsable)
        ->map($toUrl)
        ->unique(fn ($url) => \Illuminate\Support\Str::before($url, '?'))
        ->values();

    if ($photos->isEmpty()) {
        $photos = collect([
            'https://images.unsplash.com/photo-1651860282131-e3257674ccd1?auto=format&fit=crop&w=1800&q=84',
            'https://images.unsplash.com/photo-1667935837291-1dc178866251?auto=format&fit=crop&w=1800&q=84',
        ]);
    }

    // Real coordinates as an expedition bearing, e.g. 04°19′S 39°34′E.
    $bearing = function ($value, string $positive, string $negative): string {
        $value = (float) $value;
        $abs = abs($value);
        $degrees = (int) floor($abs);
        $minutes = (int) round(($abs - $degrees) * 60);
        if ($minutes === 60) {
            $degrees++;
            $minutes = 0;
        }

        return sprintf('%02d°%02d′%s', $degrees, $minutes, $value < 0 ? $negative : $positive);
    };
    $coordinates = (filled($destination->latitude) && filled($destination->longitude))
        ? $bearing($destination->latitude, 'N', 'S').' '.$bearing($destination->longitude, 'E', 'W')
        : null;
    $place = collect([$destination->city, $destination->country])->filter()->implode(', ');
    $kicker = collect(['Expedition', $coordinates ?: $place])->filter()->implode(' · ');

    $currency = data_get($destination->meta_data, 'currency')
        ?: $destination->getAttribute('currency')
        ?: match (\Illuminate\Support\Str::lower((string) $destination->country)) {
            'kenya' => 'Kenyan shilling',
            'tanzania' => 'Tanzanian shilling',
            'japan' => 'Japanese yen',
            'italy', 'france', 'spain', 'portugal', 'greece' => 'Euro',
            'morocco' => 'Moroccan dirham',
            default => null,
        };

    $highlights = collect((array) ($destination->highlights ?? []))->filter()->values();
    $rating = (float) ($destination->reviews_avg_rating ?? 0);
    $reviewCount = (int) ($destination->reviews_count ?? $destination->reviews->count());
    $searchTerm = $destination->city ?: $destination->name;

    $notes = array_values(array_filter([
        ['icon' => 'calendar', 'label' => 'Best season', 'value' => $destination->best_season ?: 'Year-round'],
        $currency ? ['icon' => 'ticket', 'label' => 'Currency', 'value' => $currency] : null,
        ['icon' => 'compass', 'label' => 'Average cost', 'value' => $destination->average_cost ? 'KSh '.number_format((float) $destination->average_cost) : 'Varies by season'],
        $coordinates
            ? ['icon' => 'map', 'label' => 'Coordinates', 'value' => $coordinates]
            : ['icon' => 'map', 'label' => 'Region', 'value' => $place ?: $destination->name],
    ]));

    $sections = [
        'legend' => 'The legend',
        'secrets' => 'Secrets',
        'sleep' => 'Where to sleep',
        'experiences' => 'Experiences',
        'packages' => 'Packages',
        'gallery' => 'Gallery',
        'tales' => 'Tales',
    ];
@endphp

<div x-data="{ section: 'legend' }">
    {{-- ─────────────────────────────  Hero  ───────────────────────────── --}}
    <section
        class="dv-magic-hero"
        x-data="{
            active: 0,
            count: {{ $photos->count() }},
            timer: null,
            still: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
            init() {
                if (this.still || this.count < 2) return;
                this.timer = setInterval(() => this.next(), 6500);
            },
            next() { this.active = (this.active + 1) % this.count },
            go(i) {
                this.active = i;
                clearInterval(this.timer);
                if (! this.still && this.count > 1) this.timer = setInterval(() => this.next(), 6500);
            },
            spot(e) {
                const r = this.$el.getBoundingClientRect();
                this.$el.style.setProperty('--mx', (e.clientX - r.left) + 'px');
                this.$el.style.setProperty('--my', (e.clientY - r.top) + 'px');
            },
        }"
        x-on:mousemove="spot($event)"
    >
        @foreach ($photos as $i => $photo)
            <img
                src="{{ $photo }}"
                alt="{{ $destination->name }} — photo {{ $i + 1 }}"
                @class(['dv-magic-slide', 'is-active' => $i === 0])
                :class="active === {{ $i }} && 'is-active'"
                @if ($i === 0) loading="eager" @else loading="lazy" @endif
            >
        @endforeach

        <div class="dv-magic-shade"></div>
        <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_30%_110%,rgba(8,14,11,0.92),transparent_62%)]" aria-hidden="true"></div>
        <div class="dv-magic-spotlight" aria-hidden="true"></div>

        <div class="dv-magic-particles" aria-hidden="true">
            @php mt_srand(crc32((string) $destination->id)); @endphp
            @for ($p = 0; $p < 24; $p++)
                <span style="left: {{ mt_rand(0, 100) }}%; --size: {{ mt_rand(2, 6) }}px; --delay: -{{ mt_rand(0, 140) / 10 }}s; --duration: {{ mt_rand(110, 220) / 10 }}s; --drift: {{ mt_rand(-60, 60) }}px;"></span>
            @endfor
        </div>

        <div class="relative z-10 flex h-full w-full flex-col justify-end px-5 pb-24 sm:px-[5vw] md:pb-28">
            <p class="dv-magic-in text-[11px] font-extrabold uppercase tracking-[0.32em] text-[#F6D6C9]" style="--i: 0">{{ $kicker }}</p>

            <h1 class="dv-magic-in mt-5 max-w-[70rem] break-words font-dm-serif text-[clamp(3.2rem,10.5vw,9.5rem)] leading-[0.88] tracking-[-0.035em] text-white [text-shadow:0_10px_60px_rgba(0,0,0,0.35)]" style="--i: 1">
                {{ $destination->name }}
            </h1>

            <p class="dv-magic-in mt-6 max-w-[38rem] font-dm-serif text-[clamp(1.25rem,2.2vw,1.75rem)] italic leading-snug text-white/85" style="--i: 2">
                {{ $destination->tagline ?: 'Discover a journey of a lifetime.' }}
            </p>

            <div class="dv-magic-in mt-8 flex flex-wrap items-center gap-3" style="--i: 3">
                <a href="#legend" class="dv-btn dv-btn-primary min-h-[3.4rem] px-6 text-[13px]">
                    Begin the expedition <x-line-icon name="arrow" :size="18" />
                </a>
                <a href="#sleep" class="dv-btn dv-magic-glass min-h-[3.4rem] px-6 text-[13px]">
                    <x-line-icon name="bed" :size="18" /> Where to stay
                </a>
            </div>
        </div>

        @if ($photos->count() > 1)
            <div class="absolute bottom-16 right-[5vw] z-10 hidden w-[15rem] md:block">
                <p class="flex items-center gap-2 text-[11px] font-bold text-white/70">
                    <x-line-icon name="map" :size="14" /> {{ $place ?: $destination->name }}
                    <span class="ml-auto"><span x-text="active + 1">1</span> / {{ $photos->count() }}</span>
                </p>
                <div class="mt-3 flex gap-1.5">
                    @foreach ($photos as $i => $photo)
                        <button
                            type="button"
                            x-on:click="go({{ $i }})"
                            class="dv-magic-progress"
                            :class="{ 'is-on': active === {{ $i }}, 'is-done': active > {{ $i }} }"
                            aria-label="Show photo {{ $i + 1 }}"
                        ><span></span></button>
                    @endforeach
                </div>
            </div>
        @endif

        <a href="#legend" class="dv-magic-cue !bottom-16" aria-label="Scroll to the story"><span></span></a>
    </section>

    {{-- ─────────────────────────  Field notes  ──────────────────────────── --}}
    <div class="relative z-10 -mt-10 px-4 sm:px-[max(5vw,calc((100vw_-_1350px)/2))]">
        <div class="dv-panel dv-panel-shadow overflow-hidden" data-no-reveal>
            <p class="flex items-center gap-2 border-b border-dashed border-line bg-cream/70 px-5 py-2.5 text-[10px] font-extrabold uppercase tracking-[0.22em] text-clay">
                <x-line-icon name="bookmark" :size="13" /> Field notes · {{ $destination->name }}
            </p>
            <div @class([
                'grid grid-cols-2 gap-px bg-line [&>*]:bg-white',
                'lg:grid-cols-4' => count($notes) === 4,
                'lg:grid-cols-3' => count($notes) === 3,
            ])>
                @foreach ($notes as $note)
                    <x-destination.quick-fact-card :icon="$note['icon']" :title="$note['label']" :value="$note['value']" />
                @endforeach
            </div>
        </div>
    </div>

    {{-- ────────────────────────  Section nav  ──────────────────────────── --}}
    <nav class="sticky top-[76px] z-20 mt-8 border-b border-line bg-cream/95 px-4 backdrop-blur-md sm:px-[max(5vw,calc((100vw_-_1350px)/2))] md:top-[88px]" aria-label="On this page" data-no-reveal>
        <div class="dv-tabs !border-b-0">
            @foreach ($sections as $id => $label)
                <a
                    href="#{{ $id }}"
                    class="dv-tab"
                    x-bind:class="section === '{{ $id }}' && 'dv-tab-active'"
                    x-on:click="section = '{{ $id }}'"
                >{{ $label }}</a>
            @endforeach
        </div>
    </nav>

    {{-- ──────────────────────────  The legend  ──────────────────────────── --}}
    <section id="legend" class="dv-section scroll-mt-36" x-intersect.margin.-40%="section = 'legend'">
        <div class="grid gap-10 lg:grid-cols-[0.8fr_1.2fr] lg:gap-20">
            <div>
                <p class="dv-eyebrow">The legend</p>
                <h2 class="dv-title !text-[clamp(2rem,4vw,3.4rem)]">What they whisper about {{ $destination->name }}</h2>
                @if ($place)
                    <p class="mt-5 flex items-center gap-2 text-[12px] font-bold text-muted"><x-line-icon name="map" :size="15" /> {{ $place }}</p>
                @endif
            </div>

            <div>
                <blockquote class="relative border-l-2 border-clay pl-6 md:pl-8">
                    <span class="pointer-events-none absolute -top-12 left-3 font-dm-serif text-[7rem] leading-none text-clay/15" aria-hidden="true">“</span>
                    <p class="relative font-dm-serif text-[clamp(1.5rem,2.6vw,2.2rem)] italic leading-[1.25] text-forest">
                        {{ $destination->short_description ?: ($destination->tagline ?: 'Some places you visit. Others stay with you.') }}
                    </p>
                </blockquote>

                @if (filled($destination->description))
                    <p class="dv-body mt-8 whitespace-pre-line">{{ $destination->description }}</p>
                @else
                    <p class="dv-body mt-8">Our field guide to {{ $destination->name }} is still being written. Its stays and experiences below are the best way in.</p>
                @endif
            </div>
        </div>
    </section>

    {{-- ──────────────────  Secrets worth the journey  ────────────────────── --}}
    <section id="secrets" class="dv-section scroll-mt-36 !pt-0" x-intersect.margin.-40%="section = 'secrets'">
        <p class="dv-eyebrow">Secrets worth the journey</p>
        <h2 class="dv-title">
            @if ($highlights->isNotEmpty())
                {{ $highlights->count() }} {{ \Illuminate\Support\Str::plural('discovery', $highlights->count()) }} waiting for you
            @else
                Discoveries waiting for you
            @endif
        </h2>

        @if ($highlights->isNotEmpty())
            <ol @class([
                'mt-8 grid gap-px overflow-hidden rounded-[1.2rem] border border-line bg-line sm:grid-cols-2',
                'lg:grid-cols-4' => $highlights->count() >= 4,
                'lg:grid-cols-3' => $highlights->count() === 3,
            ])>
                @foreach ($highlights as $highlight)
                    <li class="group relative flex items-baseline gap-4 bg-surface p-5 transition-colors duration-300 hover:bg-white sm:block sm:p-6 md:p-7">
                        <span class="font-dm-serif text-[2.6rem] leading-none text-clay/70 transition-colors group-hover:text-clay">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        <p class="font-dm-serif text-[1.3rem] leading-tight text-forest sm:mt-5">{{ $highlight }}</p>
                        <span class="mt-4 hidden h-px w-10 bg-clay/40 transition-all duration-500 group-hover:w-20 group-hover:bg-clay sm:block" aria-hidden="true"></span>
                    </li>
                @endforeach
            </ol>
        @else
            <div class="mt-8 flex flex-col items-center rounded-[1.2rem] border border-dashed border-line bg-surface px-6 py-12 text-center">
                <span class="grid h-14 w-14 place-items-center rounded-full bg-cream text-clay"><x-line-icon name="compass" :size="24" /></span>
                <p class="mt-4 font-dm-serif text-[1.5rem] text-forest">Still uncharted</p>
                <p class="mt-1.5 max-w-[26rem] text-[13px] leading-relaxed text-muted">Our guides haven't mapped {{ $destination->name }}'s secrets yet — the experiences below are a good place to start digging.</p>
            </div>
        @endif
    </section>

    {{-- ────────────────  Where to sleep under the stars  ─────────────────── --}}
    <section id="sleep" class="scroll-mt-36 px-4 sm:px-[max(5vw,calc((100vw_-_1350px)/2))]" x-intersect.margin.-40%="section = 'sleep'">
        <div class="dv-magic-night overflow-hidden rounded-[1.6rem] px-5 py-12 sm:px-10 md:px-14 md:py-16">
            <div class="relative z-10 flex flex-wrap items-end justify-between gap-4">
                <div class="text-white">
                    <p class="dv-eyebrow-light">Where to sleep under the stars</p>
                    <h2 class="mt-2 font-dm-serif text-[clamp(1.9rem,3.6vw,3rem)] leading-[1.02]">Nights in {{ $destination->name }}</h2>
                </div>
                <a href="{{ route('stays.index', ['q' => $searchTerm]) }}" class="dv-btn dv-magic-glass">
                    All stays <x-line-icon name="arrow" :size="16" />
                </a>
            </div>

            @if ($accommodations->isEmpty())
                <div class="relative z-10 mt-8 flex flex-col items-center rounded-[1.2rem] border border-dashed border-white/20 px-6 py-12 text-center text-white">
                    <span class="grid h-14 w-14 place-items-center rounded-full bg-white/10 text-[#F6D6C9]"><x-line-icon name="bed" :size="24" /></span>
                    <p class="mt-4 font-dm-serif text-[1.5rem]">No beds here yet</p>
                    <p class="mt-1.5 max-w-[26rem] text-[13px] leading-relaxed text-white/70">We're still signing up hosts in {{ $destination->name }}. Check back soon, or find a stay elsewhere.</p>
                    <a href="{{ route('stays.index') }}" class="dv-btn dv-btn-primary mt-6">Browse all stays</a>
                </div>
            @else
                <div class="dv-reel-grid relative z-10 mt-8 !grid-cols-1 sm:!grid-cols-2 lg:!grid-cols-3">
                    @foreach ($accommodations as $accommodation)
                        <x-accommodation-card :accommodation="$accommodation" />
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- ─────────────────  Things that will change you  ───────────────────── --}}
    <section id="experiences" class="dv-section scroll-mt-36" x-intersect.margin.-40%="section = 'experiences'">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="dv-eyebrow">Things that will change you</p>
                <h2 class="dv-title">Experiences around {{ $destination->name }}</h2>
            </div>
            <a href="{{ route('experiences.index', ['q' => $searchTerm]) }}" class="dv-btn dv-btn-ghost">
                All experiences <x-line-icon name="arrow" :size="16" />
            </a>
        </div>

        @if ($activities->isEmpty())
            <div class="mt-8 flex flex-col items-center rounded-[1.2rem] border border-dashed border-line bg-surface px-6 py-14 text-center">
                <span class="grid h-14 w-14 place-items-center rounded-full bg-cream text-clay"><x-line-icon name="compass" :size="24" /></span>
                <p class="mt-4 font-dm-serif text-[1.5rem] text-forest">No experiences listed yet</p>
                <p class="mt-1.5 max-w-[26rem] text-[13px] leading-relaxed text-muted">Local guides haven't published anything here yet. Explore what's on elsewhere.</p>
                <a href="{{ route('experiences.index') }}" class="dv-btn dv-btn-primary mt-6">Browse experiences</a>
            </div>
        @else
            <div class="dv-grid mt-8 !grid-cols-1 sm:!grid-cols-2 lg:!grid-cols-3">
                @foreach ($activities as $activity)
                    <x-place.experience-card :activity="$activity" />
                @endforeach
            </div>
        @endif
    </section>

    {{-- ──────────────────────────  Packages  ────────────────────────────── --}}
    <section id="packages" class="dv-section scroll-mt-36 !pt-0" x-intersect.margin.-40%="section = 'packages'">
        <p class="dv-eyebrow">The whole expedition, arranged</p>
        <h2 class="dv-title">Packages to {{ $destination->name }}</h2>

        @if ($packages->isEmpty())
            <div class="mt-8 grid items-center gap-6 rounded-[1.2rem] border border-line bg-surface p-7 md:grid-cols-[auto_1fr_auto] md:p-8">
                <span class="grid h-14 w-14 place-items-center rounded-full bg-cream text-clay"><x-line-icon name="ticket" :size="24" /></span>
                <div>
                    <p class="font-dm-serif text-[1.4rem] leading-tight text-forest">No packages charted yet</p>
                    <p class="mt-1 max-w-[34rem] text-[13px] leading-relaxed text-muted">Our agents haven't bundled a trip to {{ $destination->name }} yet — pair a stay with an experience and make your own.</p>
                </div>
                <a href="#sleep" class="dv-btn dv-btn-ghost">Start with a stay</a>
            </div>
        @else
            <div class="dv-grid mt-8 !grid-cols-1 sm:!grid-cols-2 lg:!grid-cols-3">
                @foreach ($packages as $package)
                    <x-place.package-card :package="$package" />
                @endforeach
            </div>
        @endif
    </section>

    {{-- ────────────────────────  Gallery mosaic  ────────────────────────── --}}
    <section id="gallery" class="dv-section scroll-mt-36 !pt-0" x-intersect.margin.-40%="section = 'gallery'">
        <p class="dv-eyebrow">Postcards from the edge of the map</p>
        <h2 class="dv-title">{{ $destination->name }}, up close</h2>

        @if ($photos->count() < 2)
            <div class="mt-8 grid gap-4 md:grid-cols-[1.4fr_1fr]">
                <img src="{{ $photos->first() }}" alt="{{ $destination->name }}" loading="lazy" class="h-[22rem] w-full rounded-[1.2rem] object-cover">
                <div class="flex flex-col justify-center rounded-[1.2rem] border border-dashed border-line bg-surface p-8">
                    <p class="font-dm-serif text-[1.4rem] text-forest">More postcards on the way</p>
                    <p class="mt-2 text-[13px] leading-relaxed text-muted">Hosts and travellers in {{ $destination->name }} add new photography all the time.</p>
                </div>
            </div>
        @else
            <div class="mt-8 grid auto-rows-[11rem] grid-cols-2 gap-3 md:auto-rows-[13rem] md:grid-cols-4">
                @foreach ($photos->take(5) as $photo)
                    <figure @class([
                        'group relative overflow-hidden rounded-[1.1rem] bg-sand',
                        'col-span-2 row-span-2' => $loop->first || $loop->count === 2,
                        'col-span-2' => ! $loop->first && ($loop->count === 3 || ($loop->count === 4 && $loop->index === 1)),
                    ])>
                        <img src="{{ $photo }}" alt="{{ $destination->name }} — photo {{ $loop->iteration }}" loading="lazy" class="h-full w-full object-cover transition-transform duration-1000 ease-out group-hover:scale-[1.06]">
                        <figcaption class="absolute bottom-3 left-3 rounded-full bg-black/40 px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-[0.14em] text-white/90 backdrop-blur-md">No. {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</figcaption>
                    </figure>
                @endforeach
            </div>
        @endif
    </section>

    {{-- Agent journeys that go here (renders nothing when there are none). --}}
    @include('packages::partials.journeys-rail', ['journeysIn' => $destination, 'journeysTitle' => 'Journeys through '.$destination->name, 'journeysClass' => 'dv-section !pt-0'])

    {{-- ──────────────────────  Tales from travellers  ───────────────────── --}}
    <section id="tales" class="dv-section scroll-mt-36 !pt-0" x-intersect.margin.-40%="section = 'tales'">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="dv-eyebrow">Tales from travellers</p>
                <h2 class="dv-title">Those who went before you</h2>
            </div>
            @if ($reviewCount > 0)
                <p class="flex items-center gap-2 text-[13px] font-bold text-ink">
                    <span class="text-clay"><x-line-icon name="star" :size="16" :filled="true" /></span>
                    {{ number_format($rating, 1) }} · {{ $reviewCount }} {{ \Illuminate\Support\Str::plural('tale', $reviewCount) }}
                </p>
            @endif
        </div>

        @if ($destination->reviews->isEmpty())
            <div class="mt-8 flex flex-col items-center rounded-[1.2rem] border border-dashed border-line bg-surface px-6 py-14 text-center">
                <span class="grid h-14 w-14 place-items-center rounded-full bg-cream text-clay"><x-line-icon name="star" :size="24" /></span>
                <p class="mt-4 font-dm-serif text-[1.5rem] text-forest">No tales told yet</p>
                <p class="mt-1.5 max-w-[26rem] text-[13px] leading-relaxed text-muted">Be the first to come back from {{ $destination->name }} with a story worth sharing.</p>
            </div>
        @else
            <div class="mt-8 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($destination->reviews as $review)
                    <x-destination.review-card :review="$review" />
                @endforeach
            </div>
        @endif
    </section>

    {{-- ────────────────────────────  Finale  ───────────────────────────── --}}
    <section class="dv-magic-finale">
        <div class="dv-magic-aurora" aria-hidden="true"></div>
        <div class="relative z-10 mx-auto max-w-[46rem] px-6 text-center">
            @if ($coordinates)
                <p class="text-[11px] font-extrabold uppercase tracking-[0.32em] text-[#F6D6C9]">{{ $coordinates }}</p>
            @else
                <span class="dv-brand-mark mx-auto block scale-150 text-[#F6D6C9]" aria-hidden="true"><span></span><span></span></span>
            @endif
            <h2 class="mt-8 font-dm-serif text-[clamp(2.4rem,6vw,4.6rem)] leading-[0.98] text-white">
                The journey begins here.
            </h2>
            <p class="mx-auto mt-5 max-w-[30rem] font-dm-serif text-[1.15rem] italic leading-relaxed text-white/70">
                {{ $destination->name }} is waiting. All that's left is to choose where you'll wake up.
            </p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ route('stays.index', ['q' => $searchTerm]) }}" class="dv-btn dv-btn-primary min-h-[3.4rem] px-6 text-[13px]">
                    Find a stay <x-line-icon name="arrow" :size="18" />
                </a>
                <a href="{{ route('experiences.index', ['q' => $searchTerm]) }}" class="dv-btn dv-magic-glass min-h-[3.4rem] px-6 text-[13px]">
                    Explore experiences
                </a>
                <a href="{{ route('destination.index') }}" class="dv-btn dv-magic-glass min-h-[3.4rem] px-6 text-[13px]">
                    <x-line-icon name="compass" :size="18" /> Other horizons
                </a>
            </div>
        </div>
    </section>
</div>
