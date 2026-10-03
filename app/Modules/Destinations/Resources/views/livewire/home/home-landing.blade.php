<div
    x-data="{ story: null }"
>
    {{-- ─────────────────────────────  Hero  ────────────────────────────── --}}
    <section class="px-6 pt-12 md:px-[clamp(24px,4vw,72px)] md:pt-[clamp(54px,6vw,88px)]">
        <div class="mb-11 grid items-end gap-8 lg:grid-cols-[1.35fr_1fr]">
            <p class="eyebrow col-span-full mb-[18px]">Travel deeper</p>

            <h1 class="font-display text-[clamp(48px,6vw,92px)] leading-[0.91] tracking-[-3px]">
                Find the places<br>that stay with you.
            </h1>

            <p class="max-w-[440px] text-base leading-relaxed text-ink-soft lg:ml-auto">
                Remarkable stays, local stories, and unforgettable experiences—curated for the way you want to travel.
            </p>
        </div>

        <div class="flex snap-x snap-mandatory gap-3 overflow-x-auto pb-1 no-scrollbar -mr-6 pr-6
                    md:mr-0 md:grid md:h-[620px] md:grid-cols-[1.2fr_0.8fr] md:grid-rows-2 md:overflow-visible md:pr-0
                    lg:h-[clamp(440px,46vw,640px)] lg:grid-cols-[1.45fr_0.8fr_0.95fr] lg:grid-rows-1">
            @foreach ($heroDestinations as $index => $destination)
                <article
                    @class([
                        'group relative isolate overflow-hidden rounded-[20px] text-white',
                        'h-[510px] w-[84vw] flex-none snap-start',
                        'md:h-auto md:w-auto md:row-span-2',
                        'lg:row-span-1',
                    ])
                >
                    <img
                        src="{{ $destination['image'] }}"
                        alt="{{ $destination['name'] }}, {{ $destination['country'] }}"
                        class="media-zoom absolute inset-0"
                        loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                    >
                    <div class="absolute inset-0 bg-[linear-gradient(180deg,rgba(10,20,17,0)_42%,rgba(10,20,17,0.8)_100%)]"></div>

                    @if ($index === 0)
                        <button
                            type="button"
                            x-on:click='story = { name: @js($destination["name"]), country: @js($destination["country"]), meta: @js($destination["meta"]), image: @js($destination["image"]) }'
                            class="absolute left-1/2 top-1/2 grid h-14 w-14 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full bg-white/90 text-ink transition-transform hover:scale-105"
                            aria-label="Watch the {{ $destination['name'] }} story"
                        >
                            <x-line-icon name="play" :size="18" filled />
                        </button>
                    @endif

                    <div class="absolute bottom-7 left-7 right-7 lg:right-28">
                        <span class="inline-block rounded-full border border-white/45 bg-white/10 px-2.5 py-[7px] text-[10px] uppercase tracking-[1.2px] backdrop-blur-md">
                            {{ $destination['tag'] }}
                        </span>

                        <h2 class="mt-3 font-display text-[clamp(30px,4vw,58px)] leading-none">
                            {{ $destination['name'] }}
                        </h2>

                        <p class="mt-2.5 flex items-center gap-2 text-[11px] tracking-[0.4px] opacity-90">
                            <span>{{ $destination['country'] }}</span>
                            @if ($destination['meta'])
                                <span class="h-[3px] w-[3px] rounded-full bg-current"></span>
                                <span>{{ $destination['meta'] }}</span>
                            @endif
                        </p>
                    </div>

                    <a
                        href="{{ $destination['url'] }}"
                        @class([
                            'absolute bottom-6 right-6 grid h-[46px] w-[46px] place-items-center rounded-full',
                            'border border-white/35 bg-white/15 text-white backdrop-blur-md transition-colors hover:border-coral hover:bg-coral',
                            'md:hidden' => $index > 0,
                        ])
                        aria-label="Explore {{ $destination['name'] }}"
                    >
                        <x-line-icon name="arrow" />
                    </a>
                </article>
            @endforeach
        </div>

        {{-- Floating trip search --}}
        <form
            wire:submit="runTripSearch"
            class="relative z-10 mx-auto -mt-[26px] grid min-h-[100px] max-w-[980px] grid-cols-1 gap-0 rounded-[20px] bg-white p-4 shadow-[0_16px_50px_rgba(23,33,30,0.14)] max-[680px]:mt-5 md:mt-0 md:grid-cols-3 lg:grid-cols-[1.2fr_1fr_1fr_auto]"
        >
            <label class="flex items-center gap-3 border-b border-line px-5 py-3 text-left md:border-b-0 md:border-r md:py-2">
                <span class="text-coral"><x-line-icon name="map" /></span>
                <span class="flex min-w-0 flex-1 flex-col gap-1">
                    <span class="text-[10px] uppercase tracking-[1.1px] text-ink-soft">Where</span>
                    <input
                        type="text"
                        wire:model.live.debounce.400ms="tripWhere"
                        placeholder="Explore anywhere"
                        class="w-full border-0 bg-transparent p-0 text-[13px] font-medium text-ink placeholder:font-normal placeholder:text-ink focus:ring-0"
                    >
                </span>
            </label>

            <label class="flex items-center gap-3 border-b border-line px-5 py-3 text-left md:border-b-0 md:border-r md:py-2">
                <span class="text-coral"><x-line-icon name="calendar" /></span>
                <span class="flex min-w-0 flex-1 flex-col gap-1">
                    <span class="text-[10px] uppercase tracking-[1.1px] text-ink-soft">When</span>
                    <input
                        type="date"
                        wire:model="tripWhen"
                        class="w-full border-0 bg-transparent p-0 text-[13px] font-medium text-ink focus:ring-0"
                    >
                </span>
            </label>

            <label class="flex items-center gap-3 border-b border-line px-5 py-3 text-left md:border-b-0 md:border-r md:py-2">
                <span class="text-coral"><x-line-icon name="users" /></span>
                <span class="flex min-w-0 flex-1 flex-col gap-1">
                    <span class="text-[10px] uppercase tracking-[1.1px] text-ink-soft">Travelers</span>
                    <input
                        type="number"
                        min="1"
                        wire:model="tripGuests"
                        placeholder="Add guests"
                        class="w-full border-0 bg-transparent p-0 text-[13px] font-medium text-ink placeholder:font-normal placeholder:text-ink focus:ring-0"
                    >
                </span>
            </label>

            <button
                type="submit"
                class="flex items-center justify-center gap-2.5 rounded-[15px] bg-coral px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-coral-dark md:col-span-3 md:mt-2 md:min-h-[50px] lg:col-span-1 lg:mt-0 lg:min-h-0"
            >
                <x-line-icon name="search" />
                <span>Search</span>
            </button>
        </form>
    </section>

    {{-- ──────────────────────  Handpicked stays  ───────────────────────── --}}
    <section class="px-6 pb-[82px] pt-[82px] md:px-[clamp(24px,4vw,72px)] md:pb-[120px] md:pt-[110px]">
        <div class="flex items-end justify-between gap-6">
            <div>
                <p class="mb-2.5 text-[10px] font-semibold uppercase tracking-[0.18em] text-coral">
                    Handpicked for you
                </p>
                <h2 class="font-display text-[clamp(36px,4vw,56px)] leading-none tracking-[-1px]">
                    Stay somewhere remarkable.
                </h2>
            </div>

            <a
                href="{{ route('stays.index') }}"
                class="flex flex-none items-center gap-2.5 border-b border-ink pb-2 text-[13px] font-medium text-ink transition-colors hover:border-coral hover:text-coral"
            >
                <span class="max-[680px]:hidden">View all stays</span>
                <x-line-icon name="arrow" :size="18" />
            </a>
        </div>

        <div class="my-9 mb-[26px] flex gap-2 overflow-x-auto no-scrollbar" role="tablist" aria-label="Stay categories">
            @foreach ($categories as $key => $label)
                <button
                    type="button"
                    role="tab"
                    wire:click="selectCategory('{{ $key }}')"
                    @class(['pill', 'pill-active' => $category === $key])
                    @if ($category === $key) aria-selected="true" @else aria-selected="false" @endif
                >{{ $label }}</button>
            @endforeach
        </div>

        <div class="flex snap-x snap-mandatory gap-5 overflow-x-auto no-scrollbar -mr-6 pr-6 md:mr-0 sm:grid sm:grid-cols-2 sm:overflow-visible sm:pr-0 lg:grid-cols-3">
            @foreach ($stays as $stay)
                <article class="group min-w-0 flex-none snap-start sm:w-auto" @class(['w-[82vw]'])>
                    <div class="media-zoom relative aspect-[1.2] overflow-hidden rounded-[20px] bg-sage">
                        <img
                            src="{{ $stay['image'] }}"
                            alt="{{ $stay['name'] }}"
                            class="h-full w-full object-cover"
                            loading="lazy"
                        >

                        <span class="absolute left-3.5 top-3.5 rounded-full bg-white/90 px-2.5 py-[7px] text-[10px] font-semibold text-ink backdrop-blur-md">
                            {{ $stay['badge'] }}
                        </span>

                        <button
                            type="button"
                            wire:click.stop='toggleSave(@js($stay["slug"]))'
                            @class([
                                'absolute right-3.5 top-3.5 z-10 grid h-[38px] w-[38px] place-items-center rounded-full bg-white/90 text-ink transition-colors hover:bg-white',
                                'text-coral' => $this->isSaved($stay['slug']),
                            ])
                            aria-label="{{ $this->isSaved($stay['slug']) ? 'Remove' : 'Save' }} {{ $stay['name'] }}"
                            aria-pressed="{{ $this->isSaved($stay['slug']) ? 'true' : 'false' }}"
                        >
                            <x-line-icon name="heart" :filled="$this->isSaved($stay['slug'])" />
                        </button>

                        @if ($stay['media_count'] > 1)
                            <span class="absolute bottom-3.5 right-3.5 rounded-full bg-ink/55 px-2.5 py-1 text-[9px] text-white backdrop-blur-md">
                                1 / {{ $stay['media_count'] }}
                            </span>
                        @endif
                    </div>

                    <div class="px-[3px] pt-4">
                        <div class="flex justify-between gap-3">
                            <a href="{{ $stay['url'] }}" class="group/link min-w-0">
                                <h3 class="truncate text-[15px] font-semibold text-ink transition-colors group-hover/link:text-coral">
                                    {{ $stay['name'] }}
                                </h3>
                                <p class="mt-1 text-[11px] text-ink-soft">{{ $stay['location'] }}</p>
                            </a>

                            @if ($stay['rating'])
                                <span class="flex flex-none items-center gap-1.5 whitespace-nowrap text-[11px] text-ink">
                                    <span class="text-coral"><x-line-icon name="star" :size="14" filled /></span>
                                    {{ $stay['rating'] }}
                                </span>
                            @endif
                        </div>

                        <div class="mt-3.5 flex justify-between gap-3 border-t border-line pt-3 text-[11px] text-ink-soft">
                            <span>
                                @if ($stay['price'])
                                    <strong class="text-[13px] font-semibold text-ink">{{ $stay['price'] }}</strong> / night
                                @else
                                    <strong class="text-[13px] font-semibold text-ink">Ask</strong> for rates
                                @endif
                            </span>
                            <span>{{ $stay['reviews'] }}</span>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    {{-- ─────────────────────────  Field journal  ───────────────────────── --}}
    <section class="mx-4 mb-8 grid min-h-[520px] overflow-hidden rounded-[28px] bg-ink text-white md:mx-8 lg:grid-cols-[0.9fr_1.25fr]">
        <div class="flex min-h-[470px] flex-col items-start justify-center px-10 py-12 md:px-16 lg:px-[clamp(48px,7vw,100px)]">
            <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-coral-light">
                The field journal · Issue 08
            </p>

            <h2 class="my-[18px] font-display text-[clamp(44px,5vw,74px)] leading-[0.95] tracking-[-2px]">
                A slower way<br>through the tropics.
            </h2>

            <p class="max-w-[410px] text-[13px] leading-relaxed text-white/70">
                Follow the people who keep the old crafts alive—through mountain towns, tea houses, and the rituals that make a place feel like home.
            </p>

            <a
                href="{{ route('destination.index') }}"
                class="mt-[22px] flex items-center gap-2.5 border-b border-white/50 pb-2 text-xs text-white transition-colors hover:border-coral-light hover:text-coral-light"
            >
                Read the story
                <x-line-icon name="arrow" :size="18" />
            </a>
        </div>

        <img
            src="{{ $journalImage }}"
            alt="A quiet traditional street lined with wooden houses"
            class="h-[420px] w-full object-cover lg:h-auto lg:max-h-[600px]"
            loading="lazy"
        >
    </section>

    {{-- Destination story modal --}}
    <div
        x-show="story"
        x-cloak
        x-transition.opacity
        x-on:click.self="story = null"
        x-on:keydown.escape.window="story = null"
        class="fixed inset-0 z-[60] grid place-items-center bg-ink/70 p-6 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
    >
        <div x-show="story" class="w-full max-w-2xl overflow-hidden rounded-[20px] bg-paper shadow-2xl">
            <template x-if="story">
                <div>
                    <img :src="story.image" :alt="story.name" class="aspect-[16/9] w-full object-cover">
                    <div class="flex items-start justify-between gap-6 p-8">
                        <div>
                            <p class="mb-2 text-[10px] font-semibold uppercase tracking-[0.18em] text-coral">Field note</p>
                            <h3 class="font-display text-4xl leading-none" x-text="story.name"></h3>
                            <p class="mt-3 max-w-sm text-[13px] leading-relaxed text-ink-soft">
                                <span x-text="story.meta"></span>
                            </p>
                            <p class="mt-1 text-[11px] uppercase tracking-[1.1px] text-ink-soft" x-text="story.country"></p>
                        </div>
                        <button type="button" x-on:click="story = null" class="text-ink-soft transition-colors hover:text-ink" aria-label="Close story">
                            <x-line-icon name="close" />
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
