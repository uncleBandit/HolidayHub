{{-- Experience listing: gallery hero, title + host + meta, facts row, the day in
     sections, and a sticky booking card with the session picker. On small
     screens a sticky bar carries the price down to the booking form. --}}
@php
    $isUsable = fn ($image) => is_string($image)
        && filled($image)
        && ! \Illuminate\Support\Str::contains($image, ['placeholder', 'placehold', 'picsum', 'lorempixel', 'source.unsplash']);
    $toUrl = fn ($image) => \Illuminate\Support\Str::startsWith($image, ['http://', 'https://', '/'])
        ? $image
        : asset('storage/'.ltrim($image, '/'));

    $isFilm = fn ($path) => \App\Shared\Support\Media::isVideo($path);

    $photos = collect($activity->getImages())
        ->prepend($activity->thumbnail)
        ->filter($isUsable)
        ->reject($isFilm)
        ->map($toUrl)
        ->unique(fn ($url) => \Illuminate\Support\Str::before($url, '?'))
        ->values();

    $fallbacks = [
        'https://images.unsplash.com/photo-1585402969048-86f38f8bfc2e?auto=format&fit=crop&w=1600&q=84',
        'https://images.unsplash.com/photo-1707819487421-21228af983a9?auto=format&fit=crop&w=1000&q=84',
        'https://images.unsplash.com/photo-1664093671658-a2aac3a344a9?auto=format&fit=crop&w=1000&q=84',
    ];
    $heroPhotos = $photos->concat($fallbacks)->take(3)->values();

    // The host's own films: the cover film plays in the hero; gallery films
    // sit with the reels.
    $coverFilm = filled($activity->cover_video) ? $toUrl($activity->cover_video) : null;
    $galleryFilms = collect((array) ($activity->gallery ?? []))
        ->filter(fn ($path) => is_string($path) && $isFilm($path))
        ->map($toUrl)
        ->values();

    $moments = $activity->scenes;

    $reels = $activity->mediaPosts
        ->where('provider_id', $activity->provider_id)
        ->map(fn ($post) => [
            'post' => $post,
            'video' => $post->asset(\App\Modules\Media\Domain\Enums\MediaAssetType::Original),
            'poster' => $post->asset(\App\Modules\Media\Domain\Enums\MediaAssetType::Thumbnail),
        ])
        ->filter(fn ($reel) => $reel['video'] !== null)
        ->values();

    $minutes = (int) $activity->duration_minutes;
    $duration = $minutes <= 0
        ? 'Flexible'
        : ($minutes >= 60 ? rtrim(rtrim(number_format($minutes / 60, 1), '0'), '.').' '.\Illuminate\Support\Str::plural('hour', (int) ceil($minutes / 60)) : $minutes.' minutes');

    $ages = match (true) {
        $activity->min_age && $activity->max_age => 'Ages '.$activity->min_age.'–'.$activity->max_age,
        (bool) $activity->min_age => 'Ages '.$activity->min_age.'+',
        default => 'All ages',
    };

    $languageNames = ['en' => 'English', 'sw' => 'Swahili', 'fr' => 'French', 'de' => 'German', 'es' => 'Spanish', 'it' => 'Italian', 'ja' => 'Japanese', 'ar' => 'Arabic', 'zh' => 'Chinese', 'pt' => 'Portuguese'];
    $languages = $activity->languages
        ->pluck('language_code')
        ->filter()
        ->map(fn ($code) => $languageNames[strtolower($code)] ?? strtoupper($code))
        ->unique()
        ->values();

    $host = $activity->provider?->company_name;
    $currency = \App\Shared\Support\Currencies::symbol($activity->getCurrency());
    $attributes = (array) ($activity->getAttribute('attributes') ?? []);
    $cancellation = $attributes['cancellation_policy'] ?? null;
    $accessibility = collect((array) ($activity->accessibility ?? []))->filter()->values();
    $fitness = $activity->requirements->firstWhere('type', 'fitness');
    $price = (float) $activity->base_price;
    // Shared experiences are priced per person; a private one is one price for the group.
    $private = app(\App\Modules\Activities\Application\Services\ExperiencePricing::class)->isPrivate($activity);
    $unit = $private ? 'group' : 'person';
    $rating = (float) $activity->rating;
    $activeOptions = $activity->options->where('is_active', true);
    $highlights = collect((array) ($activity->highlights ?? []))->filter()->values();
    $inclusions = collect((array) ($activity->inclusions ?? []))->filter()->values();
    $exclusions = collect((array) ($activity->exclusions ?? []))->filter()->values();
@endphp

<div class="pb-24 lg:pb-0">
    {{-- ─────────────────────────────  Gallery hero  ─────────────────────── --}}
    <section class="relative grid h-[52vh] min-h-[24rem] max-h-[44rem] gap-1.5 bg-ink md:h-[64vh] md:min-h-[32rem] md:grid-cols-[2fr_1fr]" aria-label="Photos of {{ $activity->name }}">
        <div class="relative overflow-hidden">
            @if ($coverFilm)
                <video src="{{ $coverFilm }}" poster="{{ $heroPhotos[0] }}" class="h-full w-full object-cover" autoplay muted loop playsinline preload="metadata" aria-label="{{ $activity->name }}, the film"></video>
                @if ($moments->isNotEmpty())
                    <a href="#moments" class="absolute bottom-4 left-4 z-10 inline-flex items-center gap-2 rounded-full bg-black/45 px-3.5 py-2.5 text-[11px] font-extrabold text-white backdrop-blur-md hover:bg-black/60">
                        <x-line-icon name="play" :size="13" :filled="true" /> Live it moment by moment
                    </a>
                @endif
            @else
                <img src="{{ $heroPhotos[0] }}" alt="{{ $activity->name }}" class="h-full w-full object-cover">
            @endif
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent md:hidden" aria-hidden="true"></div>
        </div>

        <div class="hidden grid-rows-2 gap-1.5 overflow-hidden md:grid">
            @if ($reels->isNotEmpty())
                <div class="relative overflow-hidden">
                    <video
                        class="h-full w-full object-cover"
                        src="{{ $reels[0]['video']->url }}"
                        @if ($reels[0]['poster']) poster="{{ $reels[0]['poster']->url }}" @else poster="{{ $heroPhotos[1] }}" @endif
                        data-autoplay muted loop playsinline preload="none"
                        aria-hidden="true"
                    ></video>
                    <a href="#reels" class="absolute bottom-4 left-4 inline-flex items-center gap-2 rounded-full bg-black/45 px-3 py-2 text-[11px] font-extrabold text-white backdrop-blur-md hover:bg-black/60">
                        <x-line-icon name="play" :size="13" :filled="true" /> Watch the reel
                    </a>
                </div>
            @else
                <img src="{{ $heroPhotos[1] }}" alt="{{ $activity->name }} — photo 2" class="h-full w-full object-cover">
            @endif
            <img src="{{ $heroPhotos[2] }}" alt="{{ $activity->name }} — photo 3" class="h-full w-full object-cover">
        </div>

        @if ($photos->count() > 1)
            <a href="#photos" class="absolute bottom-4 right-4 inline-flex items-center gap-2 rounded-full border border-line bg-white/95 px-4 py-2.5 text-[11px] font-extrabold text-ink shadow-sm transition-colors hover:bg-white">
                <x-line-icon name="grid" :size="14" /> {{ $photos->count() }} photos
            </a>
        @endif
    </section>

    <div class="mx-auto grid max-w-[1250px] gap-12 px-4 pt-10 sm:px-8 md:pt-14 lg:grid-cols-[minmax(0,1.5fr)_minmax(20rem,0.8fr)] lg:gap-16 lg:pb-24">
        {{-- ─────────────────────────────  Listing copy  ───────────────────── --}}
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-3">
                @if ($host)
                    <p class="flex items-center gap-2 text-[12px] font-bold text-ink">
                        <span class="grid h-8 w-8 place-items-center rounded-full bg-forest text-[11px] font-extrabold text-white">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($host, 0, 2)) }}</span>
                        Hosted by {{ $host }}
                        <span class="grid h-4 w-4 place-items-center rounded-full bg-forest-light text-white"><x-line-icon name="check" :size="10" :stroke="3" /></span>
                    </p>
                @endif
                @if ($activity->category)
                    <span class="rounded-full bg-sand px-3 py-1 text-[10px] font-extrabold uppercase tracking-[0.1em] text-forest">{{ $activity->category->name }}</span>
                @endif
            </div>

            <h1 class="mt-5 max-w-[50rem] font-dm-serif text-[clamp(2.2rem,4.4vw,3.9rem)] leading-[1.02] tracking-[-0.02em] text-forest">{{ $activity->name }}</h1>

            <div class="mt-5 flex flex-wrap items-center gap-x-5 gap-y-2 text-[13px] text-muted">
                <span class="flex items-center gap-1.5">
                    <span class="text-clay"><x-line-icon name="star" :size="15" :filled="true" /></span>
                    @if ($rating > 0)
                        <b class="text-ink">{{ number_format($rating, 1) }}</b> ({{ (int) $activity->reviews_count }} {{ \Illuminate\Support\Str::plural('review', (int) $activity->reviews_count) }})
                    @else
                        New experience
                    @endif
                </span>
                @if ($activity->destination)
                    <a href="{{ route('destination.show', $activity->destination) }}" class="flex items-center gap-1.5 hover:text-ink">
                        <x-line-icon name="map" :size="15" /> {{ $activity->destination->name }}
                    </a>
                @endif
                <span class="flex items-center gap-1.5"><x-line-icon name="clock" :size="15" /> {{ $duration }}</span>
            </div>

            {{-- Facts row --}}
            <div class="mt-8 grid grid-cols-2 gap-x-4 gap-y-6 border-y border-line py-6 sm:grid-cols-4">
                @foreach ([
                    ['icon' => 'clock', 'label' => $duration, 'sub' => 'Duration'],
                    ['icon' => 'users', 'label' => $activity->capacity ? 'Up to '.$activity->capacity : 'Flexible', 'sub' => 'Group size'],
                    ['icon' => 'user', 'label' => $ages, 'sub' => 'Age'],
                    ['icon' => 'compass', 'label' => $languages->isNotEmpty() ? $languages->take(2)->implode(', ') : 'English', 'sub' => $languages->count() > 2 ? '+'.($languages->count() - 2).' more languages' : 'Language'],
                ] as $fact)
                    <div class="flex items-center gap-3">
                        <span class="text-clay"><x-line-icon :name="$fact['icon']" :size="22" /></span>
                        <p class="flex min-w-0 flex-col">
                            <b class="text-[13px] text-ink">{{ $fact['label'] }}</b>
                            <span class="mt-0.5 text-[11px] text-muted">{{ $fact['sub'] }}</span>
                        </p>
                    </div>
                @endforeach
            </div>

            {{-- Moment by moment: the host's reel walk, in the shared player with an experience heading. --}}
            @if ($moments->isNotEmpty())
                <section id="moments" class="mt-10 scroll-mt-28 rounded-[1.4rem] border border-line bg-surface p-5 sm:p-7">
                    <x-stay-detail.reel-walk
                        :scenes="$moments"
                        :name="$activity->name"
                        eyebrow="Moment by moment"
                        heading="Live it before you book"
                        noun="moment"
                        cta="Play the experience"
                        class="!py-0"
                    />
                </section>
            @endif

            @if ($activity->is_featured || $rating >= 4.7)
                <div class="mt-6 flex items-center gap-4 rounded-[0.9rem] bg-sand/60 px-5 py-4">
                    <span class="text-forest"><x-line-icon name="star" :size="24" /></span>
                    <div>
                        <b class="text-[13px] text-ink">A guest favourite</b>
                        <p class="mt-0.5 text-[12px] text-muted">One of the most loved experiences{{ $activity->destination ? ' in '.$activity->destination->name : '' }}, based on ratings and verified reviews.</p>
                    </div>
                </div>
            @endif

            {{-- About --}}
            <section class="mt-12">
                <p class="dv-eyebrow">About this experience</p>
                @if ($activity->short_description)
                    <h2 class="mt-2 font-dm-serif text-[clamp(1.6rem,2.6vw,2.1rem)] leading-tight text-forest">{{ $activity->short_description }}</h2>
                @endif
                @if (filled($activity->description))
                    <p class="dv-body mt-4 whitespace-pre-line">{{ $activity->description }}</p>
                @elseif (! $activity->short_description)
                    <p class="dv-body mt-4">The host is still writing up this experience. Message them after booking for the full plan.</p>
                @endif

                @if ($highlights->isNotEmpty())
                    <ul class="mt-8 grid gap-3 sm:grid-cols-2">
                        @foreach ($highlights as $highlight)
                            <li class="flex items-start gap-3 text-[14px] font-semibold text-ink">
                                <span class="mt-0.5 grid h-6 w-6 flex-none place-items-center rounded-full bg-forest text-white"><x-line-icon name="check" :size="13" /></span>
                                {{ $highlight }}
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            {{-- Reels: published media posts, then the host's gallery films --}}
            @if ($reels->isNotEmpty() || $galleryFilms->isNotEmpty())
                <section id="reels" class="mt-14 scroll-mt-28">
                    <p class="dv-eyebrow">On film</p>
                    <h2 class="dv-title">Watch this experience</h2>
                    <div class="mt-6 grid grid-cols-2 gap-3 sm:gap-4">
                        @foreach ($reels as $reel)
                            <article class="overflow-hidden rounded-[1.2rem] bg-forest text-white">
                                <video class="aspect-[9/14] max-h-[560px] w-full bg-black object-cover" controls playsinline preload="metadata" @if ($reel['poster']) poster="{{ $reel['poster']->url }}" @endif>
                                    <source src="{{ $reel['video']->url }}" type="{{ $reel['video']->mime_type }}">
                                </video>
                                @if ($reel['post']->title || $reel['post']->caption)
                                    <div class="space-y-1 p-4">
                                        @if ($reel['post']->title)<h3 class="font-dm-serif text-[1.1rem] leading-tight">{{ $reel['post']->title }}</h3>@endif
                                        @if ($reel['post']->caption)<p class="line-clamp-3 text-[12px] text-white/75">{{ $reel['post']->caption }}</p>@endif
                                    </div>
                                @endif
                            </article>
                        @endforeach
                        @foreach ($galleryFilms as $film)
                            <article class="overflow-hidden rounded-[1.2rem] bg-forest text-white">
                                <video class="aspect-[9/14] max-h-[560px] w-full bg-black object-cover" src="{{ $film }}" controls playsinline preload="metadata" @if ($heroPhotos[0] ?? null) poster="{{ $heroPhotos[0] }}" @endif></video>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Included / not included --}}
            <section class="mt-14">
                <p class="dv-eyebrow">What's covered</p>
                <h2 class="dv-title">Included, and not</h2>
                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div class="dv-panel p-6">
                        <h3 class="text-[12px] font-extrabold uppercase tracking-[0.1em] text-forest">Included</h3>
                        <ul class="mt-4 space-y-3">
                            @forelse ($inclusions as $item)
                                <li class="flex items-start gap-3 text-[14px] text-ink">
                                    <span class="mt-0.5 text-forest-light"><x-line-icon name="check" :size="16" :stroke="2.4" /></span>{{ $item }}
                                </li>
                            @empty
                                <li class="text-[13px] text-muted">Not specified — ask the host.</li>
                            @endforelse
                        </ul>
                    </div>
                    <div class="dv-panel p-6">
                        <h3 class="text-[12px] font-extrabold uppercase tracking-[0.1em] text-clay">Not included</h3>
                        <ul class="mt-4 space-y-3">
                            @forelse ($exclusions as $item)
                                <li class="flex items-start gap-3 text-[14px] text-ink">
                                    <span class="mt-0.5 text-clay"><x-line-icon name="close" :size="16" :stroke="2.4" /></span>{{ $item }}
                                </li>
                            @empty
                                <li class="text-[13px] text-muted">Not specified.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </section>

            {{-- Itinerary --}}
            <section class="mt-14">
                <p class="dv-eyebrow">How the day runs</p>
                <h2 class="dv-title">Itinerary</h2>
                @if ($activity->itinerary->isNotEmpty())
                    <ol class="relative mt-8 space-y-8 before:absolute before:bottom-3 before:left-[1.05rem] before:top-3 before:w-px before:bg-line">
                        @foreach ($activity->itinerary as $item)
                            <li class="relative flex gap-5">
                                <span class="relative z-10 grid h-[2.1rem] w-[2.1rem] flex-none place-items-center rounded-full border border-line bg-surface text-[12px] font-extrabold text-forest">{{ $item->sequence }}</span>
                                <div class="pt-1">
                                    <h3 class="font-dm-serif text-[1.25rem] leading-tight text-ink">{{ $item->title }}</h3>
                                    @if ($item->description)<p class="mt-1.5 text-[14px] leading-relaxed text-muted">{{ $item->description }}</p>@endif
                                    @if ($item->duration_minutes)
                                        <p class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-sand/70 px-2.5 py-1 text-[11px] font-bold text-forest"><x-line-icon name="clock" :size="12" /> {{ $item->duration_minutes }} minutes</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <p class="mt-4 rounded-[1rem] border border-dashed border-line bg-surface px-5 py-6 text-[13px] text-muted">The host shares a step-by-step plan once you've booked.</p>
                @endif
            </section>

            {{-- Meeting point & before you go --}}
            <section class="mt-14 grid gap-4 sm:grid-cols-2">
                <div class="dv-panel p-6">
                    <h2 class="flex items-center gap-2 text-[12px] font-extrabold uppercase tracking-[0.1em] text-forest"><x-line-icon name="map" :size="15" /> Meeting and pickup</h2>
                    <ul class="mt-4 space-y-4">
                        @forelse ($activity->locations as $location)
                            <li>
                                <p class="text-[14px] font-bold text-ink">{{ $location->name }}</p>
                                <p class="text-[13px] text-muted">{{ $location->address }}</p>
                                @if ($location->instructions)<p class="mt-1 text-[12px] text-muted">{{ $location->instructions }}</p>@endif
                            </li>
                        @empty
                            <li class="text-[13px] text-muted">Meeting details provided after booking.</li>
                        @endforelse
                    </ul>
                </div>
                <div class="dv-panel p-6">
                    <h2 class="flex items-center gap-2 text-[12px] font-extrabold uppercase tracking-[0.1em] text-forest"><x-line-icon name="leaf" :size="15" /> Before you go</h2>
                    <ul class="mt-4 space-y-3">
                        @forelse ($activity->requirements->where('type', '!=', 'fitness') as $requirement)
                            <li class="text-[13px] text-muted">
                                <span class="font-bold text-ink">{{ $requirement->title }}</span>
                                @if ($requirement->required)<span class="ml-1 rounded-full bg-clay/10 px-2 py-0.5 text-[10px] font-extrabold text-clay">Required</span>@endif
                                @if ($requirement->description)<p class="mt-1">{{ $requirement->description }}</p>@endif
                            </li>
                        @empty
                            @if ($activity->safety_instructions)
                                <li class="text-[13px] text-muted">{{ $activity->safety_instructions }}</li>
                            @else
                                <li class="text-[13px] text-muted">No additional requirements listed.</li>
                            @endif
                        @endforelse
                    </ul>
                </div>
            </section>

            {{-- Good to know --}}
            @if ($cancellation || $activity->weather_dependent || $accessibility->isNotEmpty() || $fitness || ($activity->safety_instructions && $activity->requirements->isNotEmpty()))
                <section class="mt-4 dv-panel p-6">
                    <h2 class="flex items-center gap-2 text-[12px] font-extrabold uppercase tracking-[0.1em] text-forest"><x-line-icon name="check" :size="15" /> Good to know</h2>
                    <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                        @if ($fitness)
                            <div><dt class="text-[13px] font-bold text-ink">{{ $fitness->title }}</dt><dd class="mt-0.5 text-[12.5px] text-muted">{{ $fitness->description }}</dd></div>
                        @endif
                        @if ($cancellation)
                            <div><dt class="text-[13px] font-bold text-ink">Cancellation</dt><dd class="mt-0.5 text-[12.5px] text-muted">{{ $cancellation }}</dd></div>
                        @endif
                        @if ($activity->weather_dependent)
                            <div><dt class="text-[13px] font-bold text-ink">Depends on the weather</dt><dd class="mt-0.5 text-[12.5px] text-muted">{{ $activity->weather_cancellation_policy ?: 'If the weather makes it unsafe, your host will offer another date or a refund.' }}</dd></div>
                        @endif
                        @if ($accessibility->isNotEmpty())
                            <div><dt class="text-[13px] font-bold text-ink">Accessibility</dt><dd class="mt-0.5 text-[12.5px] text-muted">{{ $accessibility->implode(' · ') }}</dd></div>
                        @endif
                        @if ($activity->safety_instructions && $activity->requirements->isNotEmpty())
                            <div class="sm:col-span-2"><dt class="text-[13px] font-bold text-ink">Safety</dt><dd class="mt-0.5 text-[12.5px] text-muted">{{ $activity->safety_instructions }}</dd></div>
                        @endif
                    </dl>
                </section>
            @endif

            {{-- Photos --}}
            @if ($photos->count() > 1)
                <section id="photos" class="mt-14 scroll-mt-28">
                    <p class="dv-eyebrow">In pictures</p>
                    <h2 class="dv-title">Photos</h2>
                    <div @class(['mt-6 grid auto-rows-[9rem] grid-cols-2 gap-2.5 sm:auto-rows-[11rem]', 'sm:grid-cols-3' => $photos->count() > 2])>
                        @foreach ($photos->take(6) as $photo)
                            <img src="{{ $photo }}" alt="{{ $activity->name }} — photo {{ $loop->iteration }}" loading="lazy" @class(['h-full w-full rounded-[1rem] object-cover', 'row-span-2' => $loop->count === 2, 'col-span-2 row-span-2' => $loop->first && $loop->count > 2])>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Agent journeys that include this experience (renders nothing when there are none). --}}
            @include('packages::partials.journeys-rail', ['journeysFor' => $activity, 'journeysTitle' => 'Journeys that include it', 'journeysClass' => 'mt-14'])

            {{-- Reviews --}}
            <section class="mt-14">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p class="dv-eyebrow">Guest voices</p>
                        <h2 class="dv-title">Guest reviews</h2>
                    </div>
                    @if ($rating > 0)
                        <p class="flex items-center gap-1.5 text-[13px] font-bold text-ink"><span class="text-clay"><x-line-icon name="star" :size="16" :filled="true" /></span> {{ number_format($rating, 1) }} · {{ (int) $activity->reviews_count }} {{ \Illuminate\Support\Str::plural('review', (int) $activity->reviews_count) }}</p>
                    @endif
                </div>
                <div class="mt-6 divide-y divide-line">
                    @forelse ($reviews as $review)
                        <article class="py-5 first:pt-0">
                            <div class="flex items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <span class="grid h-9 w-9 place-items-center rounded-full bg-forest text-[13px] font-extrabold text-white">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($review['user_name'], 0, 1)) }}</span>
                                    <div>
                                        <p class="text-[14px] font-bold text-ink">{{ $review['user_name'] }}</p>
                                        <p class="text-[11px] text-muted">{{ $review['created_at'] }}</p>
                                    </div>
                                </div>
                                <span class="flex items-center gap-1 text-[12px] font-bold text-clay"><x-line-icon name="star" :size="13" :filled="true" /> {{ $review['rating'] }}/5</span>
                            </div>
                            @if ($review['comment'])<p class="mt-3 text-[14px] leading-relaxed text-ink">{{ $review['comment'] }}</p>@endif
                        </article>
                    @empty
                        <div class="flex items-center gap-4 rounded-[1rem] border border-dashed border-line bg-surface px-5 py-6">
                            <span class="grid h-11 w-11 flex-none place-items-center rounded-full bg-cream text-clay"><x-line-icon name="star" :size="20" /></span>
                            <p class="text-[13px] text-muted">No published reviews yet — be among the first to go and tell others how it was.</p>
                        </div>
                    @endforelse
                </div>
                @if ($hasMoreReviews)
                    <button type="button" wire:click="loadMoreReviews" wire:loading.attr="disabled" class="dv-btn dv-btn-ghost mt-4">
                        Load more reviews
                        <span wire:loading wire:target="loadMoreReviews">…</span>
                    </button>
                @endif
            </section>
        </div>

        {{-- ─────────────────────────────  Booking card  ────────────────────── --}}
        <aside id="book" class="scroll-mt-28 self-start lg:sticky lg:top-[112px]">
            <div class="dv-panel dv-panel-shadow p-6" x-data="{ people: {{ (int) $participants }} }">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <p class="font-dm-serif text-[1.8rem] leading-none text-ink">
                        <span class="text-[12px] font-sans font-bold text-muted">From</span>
                        {{ $currency }} {{ number_format($price, 2) }}
                        <small class="font-sans text-[12px] text-muted">/ {{ $unit }}</small>
                    </p>
                    @if ($rating > 0)
                        <span class="flex items-center gap-1 text-[12px] font-bold text-ink"><span class="text-clay"><x-line-icon name="star" :size="13" :filled="true" /></span> {{ number_format($rating, 1) }} · {{ (int) $activity->reviews_count }}</span>
                    @endif
                </div>

                @if ($activeOptions->isNotEmpty())
                    <div class="mt-5 rounded-[0.8rem] bg-cream p-4">
                        <h2 class="text-[10px] font-extrabold uppercase tracking-[0.1em] text-muted">Available options</h2>
                        <ul class="mt-2 space-y-2">
                            @foreach ($activeOptions as $option)
                                <li class="flex justify-between gap-3 text-[13px] text-ink">
                                    <span>{{ $option->name }}</span>
                                    <b class="whitespace-nowrap">{{ \App\Shared\Support\Currencies::symbol($option->currency) }} {{ number_format((float) $option->base_price, 2) }}</b>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form wire:submit="bookNow" class="mt-5">
                    <div class="overflow-hidden rounded-[0.8rem] border border-line">
                        <label class="block border-b border-line px-4 py-3 focus-within:bg-cream/60">
                            <span class="flex items-center gap-1.5 text-[10px] font-extrabold uppercase tracking-[0.1em] text-ink"><x-line-icon name="calendar" :size="13" /> Choose a session</span>
                            <select wire:model="selectedSessionId" required class="mt-1 w-full cursor-pointer appearance-none border-0 bg-transparent p-0 pr-6 text-[14px] font-bold text-ink focus:ring-0">
                                <option value="">Select a date and time</option>
                                @foreach ($availability as $session)
                                    <option value="{{ $session['id'] }}">
                                        {{ \Carbon\Carbon::parse($session['starts_at'])->setTimezone($session['timezone'])->format('D, M j · g:i A') }}
                                        — {{ $session['slots'] }} places left
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block px-4 py-3 focus-within:bg-cream/60">
                            <span class="flex items-center gap-1.5 text-[10px] font-extrabold uppercase tracking-[0.1em] text-ink"><x-line-icon name="users" :size="13" /> Participants</span>
                            <input wire:model="participants" x-on:input="people = parseInt($event.target.value) || 0" type="number" min="1" max="1000" required class="mt-1 w-full border-0 bg-transparent p-0 text-[14px] font-bold text-ink focus:ring-0">
                        </label>
                    </div>

                    @error('selectedSessionId')<p class="auth-error mt-2">{{ $message }}</p>@enderror
                    @error('session')<p class="auth-error mt-2">{{ $message }}</p>@enderror
                    @error('participants')<p class="auth-error mt-2">{{ $message }}</p>@enderror
                    @error('guest')<p role="alert" class="auth-error mt-2">{{ $message }}</p>@enderror

                    <button type="submit" @disabled($availability === []) wire:loading.attr="disabled" wire:target="bookNow" class="dv-btn dv-btn-primary mt-4 w-full !min-h-[3.2rem] !text-[13px] disabled:cursor-not-allowed disabled:!bg-muted/50">
                        <span wire:loading.remove wire:target="bookNow">Continue to booking</span>
                        <span wire:loading wire:target="bookNow">Holding your places…</span>
                    </button>

                    @if ($availability === [])
                        <p class="mt-3 text-center text-[12px] text-muted">No bookable sessions are currently available.</p>
                    @else
                        <p class="mt-3 text-center text-[11px] text-muted">{{ count($availability) }} upcoming {{ \Illuminate\Support\Str::plural('session', count($availability)) }} · you won't be charged yet</p>
                    @endif
                </form>

                @if ($price > 0)
                    <div class="mt-5 space-y-2.5 text-[12px] text-muted">
                        <p class="flex justify-between"><span>{{ $currency }} {{ number_format($price, 2) }} × <span x-text="people">{{ (int) $participants }}</span> <span x-text="people === 1 ? 'person' : 'people'">{{ \Illuminate\Support\Str::plural('person', (int) $participants) }}</span></span>
                            <b class="text-ink" x-text="'{{ $currency }} ' + ({{ $price }} * ({{ $private ? 'Math.min(people, 1)' : 'Math.max(people, 0)' }})).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })">{{ $currency }} {{ number_format($private ? $price : $price * max((int) $participants, 0), 2) }}</b>
                        </p>
                        <p class="flex justify-between border-t border-line pt-3 text-[13px] font-extrabold text-ink"><span>Estimated total</span><span x-text="'{{ $currency }} ' + ({{ $price }} * ({{ $private ? 'Math.min(people, 1)' : 'Math.max(people, 0)' }})).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })">{{ $currency }} {{ number_format($private ? $price : $price * max((int) $participants, 0), 2) }}</span></p>
                        <p class="text-[11px]">Final price depends on the option and session; confirmed at checkout.</p>
                    </div>
                @endif

                <p class="mt-5 flex items-center justify-center gap-2 rounded-[0.6rem] bg-cream px-3 py-2.5 text-center text-[11px] text-forest">
                    <x-line-icon name="clock" :size="14" />
                    Times shown in {{ $activity->timezone }} · places held atomically when you book
                </p>
            </div>
        </aside>
    </div>

    {{-- ─────────────────────────────  Mobile book bar  ─────────────────── --}}
    <div class="fixed inset-x-0 bottom-[4.8rem] z-40 flex items-center justify-between gap-3 border-t border-line bg-surface/95 px-4 py-3 backdrop-blur-xl md:bottom-0 lg:hidden" data-no-reveal>
        <div class="min-w-0">
            <span class="text-[11px] text-muted">From</span>
            <p class="font-dm-serif text-[1.15rem] leading-tight text-ink">{{ $currency }} {{ number_format($price) }} <small class="font-sans text-[11px] text-muted">/ {{ $unit }}</small></p>
        </div>
        <a href="#book" class="dv-btn dv-btn-primary flex-none">{{ $availability === [] ? 'See availability' : 'Choose a session' }}</a>
    </div>
</div>
