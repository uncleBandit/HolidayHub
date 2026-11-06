@php
    use App\Modules\Media\Domain\Enums\MediaAssetType;

    $fallbackPhoto = 'https://images.unsplash.com/photo-1708119063168-4785d1359824?auto=format&fit=crop&w=1800&q=84';
    $heroPhoto = $posts->getCollection()
        ->map(fn ($post) => $post->asset(MediaAssetType::Thumbnail)?->url)
        ->filter()
        ->first() ?? $fallbackPhoto;

    $logo = $provider->logo;
    if (! is_string($logo) || blank($logo) || Str::contains($logo, ['placeholder', 'placehold', 'picsum', 'lorempixel'])) {
        $logo = null;
    } elseif (! Str::startsWith($logo, ['http://', 'https://', '/'])) {
        $logo = asset('storage/'.ltrim($logo, '/'));
    }
    $initials = collect(preg_split('/[\s&]+/', trim((string) $provider->company_name)))
        ->filter(fn ($word) => preg_match('/^[A-Za-z]/', $word))
        ->take(2)
        ->map(fn ($word) => Str::upper(Str::substr($word, 0, 1)))
        ->implode('') ?: 'HH';

    $place = collect([$provider->city, $provider->country])->filter()->implode(', ');
    $filters = ['all' => 'All media', 'reel' => 'Reels', 'video' => 'Videos'];
@endphp

<div>
    {{-- ─────────────────────────────  Hero  ───────────────────────────── --}}
    <section class="dv-hero !min-h-[26rem] items-end md:!min-h-[34rem]">
        <img src="{{ $heroPhoto }}" alt="">
        <div class="absolute inset-0 -z-10 bg-[linear-gradient(0deg,rgba(8,22,17,0.82),transparent_65%),linear-gradient(180deg,rgba(8,22,17,0.35),transparent_30%)]" aria-hidden="true"></div>

        <div class="w-full px-[max(1.25rem,5vw)] pb-14 md:pb-20">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-end">
                <span class="dv-magic-in grid h-20 w-20 shrink-0 place-items-center overflow-hidden rounded-full border-4 border-white/65 bg-clay font-dm-serif text-[1.5rem] text-white md:h-24 md:w-24" style="--i: 0">
                    @if ($logo)
                        <img src="{{ $logo }}" alt="{{ $provider->company_name }} logo" class="h-full w-full object-cover">
                    @else
                        {{ $initials }}
                    @endif
                </span>
                <div class="min-w-0">
                    <p class="dv-eyebrow-light dv-magic-in" style="--i: 1">Provider media{{ $provider->provider_type ? ' · '.Str::headline($provider->provider_type) : '' }}</p>
                    <h1 class="dv-magic-in mt-2 font-dm-serif text-[clamp(2.3rem,5.5vw,4.6rem)] leading-[0.98] tracking-[-0.03em]" style="--i: 2">{{ $provider->company_name }}</h1>
                    @if ($provider->city || $provider->country)
                        <p class="dv-magic-in mt-3 flex items-center gap-1.5 text-[13px] text-white/80" style="--i: 3">
                            <x-line-icon name="map" :size="15" />
                            {{ $provider->city }}@if($provider->city && $provider->country), @endif{{ $provider->country }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="dv-section !pt-0">
        {{-- Facts strip, floated over the hero edge --}}
        <div class="relative z-10 -mt-8 grid grid-cols-3 rounded-[1.1rem] border border-line bg-white px-2 py-5 shadow-[0_20px_60px_rgba(20,36,28,0.12)] md:ml-auto md:w-[min(36rem,60%)] md:px-4" data-no-reveal>
            <div class="flex flex-col items-center gap-1 border-r border-line px-2 text-center">
                <b class="font-dm-serif text-[1.45rem] leading-none text-forest">{{ number_format($posts->total()) }}</b>
                <span class="text-[10px] text-muted">{{ $filter === 'all' ? 'Published posts' : ($filter === 'reel' ? 'Reels' : 'Videos') }}</span>
            </div>
            <div class="flex flex-col items-center gap-1 border-r border-line px-2 text-center">
                <b class="flex items-center gap-1 font-dm-serif text-[1.45rem] leading-none text-forest">
                    @if ($provider->is_verified)
                        <span class="grid h-5 w-5 place-items-center rounded-full bg-[#4A9FDA] text-white"><x-line-icon name="check" :size="12" :stroke="3" /></span> Yes
                    @else
                        —
                    @endif
                </b>
                <span class="text-[10px] text-muted">Verified provider</span>
            </div>
            <div class="flex flex-col items-center gap-1 px-2 text-center">
                <b class="font-dm-serif text-[1.45rem] leading-none text-forest">{{ $provider->created_at?->format('Y') ?? '—' }}</b>
                <span class="text-[10px] text-muted">On HolidayHub since</span>
            </div>
        </div>

        <div class="mt-12 grid gap-8 lg:grid-cols-[0.8fr_1.2fr] lg:gap-14">
            <div>
                <p class="dv-eyebrow">About</p>
                <h2 class="dv-title">Meet {{ $provider->company_name }}</h2>
            </div>
            <div class="lg:pt-6">
                @if ($provider->bio)
                    <p class="dv-body max-w-3xl">{{ $provider->bio }}</p>
                @else
                    <p class="dv-body max-w-3xl">
                        {{ $provider->company_name }} shares its stays and experiences on HolidayHub{{ $place ? ' from '.$place : '' }}. Watch their reels below to get a feel for the place.
                    </p>
                @endif
            </div>
        </div>

        {{-- Filter tabs --}}
        <div class="mt-12 border-b border-line">
            <nav class="dv-tabs max-w-full" aria-label="Filter provider media" role="tablist" data-no-reveal>
                @foreach ($filters as $value => $label)
                    <button
                        type="button"
                        role="tab"
                        wire:click="setFilter('{{ $value }}')"
                        @class(['dv-tab', 'dv-tab-active' => $filter === $value])
                        aria-selected="{{ $filter === $value ? 'true' : 'false' }}"
                    >{{ $label }}</button>
                @endforeach
            </nav>
        </div>

        {{-- Media grid --}}
        <div class="mt-8" wire:loading.class="opacity-60" wire:target="setFilter, gotoPage, nextPage, previousPage">
            @if ($posts->isEmpty())
                <div class="dv-panel mx-auto max-w-[36rem] px-6 py-12 text-center">
                    <span class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-cream text-clay">
                        <x-line-icon name="play" :size="24" :filled="true" />
                    </span>
                    <p class="mt-5 font-dm-serif text-[1.6rem] leading-tight text-forest">Nothing here yet</p>
                    <p class="mx-auto mt-2 max-w-sm text-[13px] text-muted">No published media matches this filter yet.</p>
                    @if ($filter !== 'all')
                        <button type="button" wire:click="setFilter('all')" class="dv-btn dv-btn-ghost mt-6">Show all media</button>
                    @endif
                </div>
            @else
                <div class="dv-reel-grid">
                    @foreach ($posts as $post)
                        @php
                            $original = $post->asset(MediaAssetType::Original);
                            $thumbnail = $post->asset(MediaAssetType::Thumbnail);
                        @endphp
                        <article
                            wire:key="media-{{ $post->id }}"
                            class="dv-reel-card"
                            x-data="{ muted: true, paused: false }"
                        >
                            @if ($original)
                                <video
                                    x-ref="clip"
                                    data-autoplay
                                    muted
                                    loop
                                    playsinline
                                    preload="none"
                                    x-on:play="paused = false"
                                    @if ($thumbnail) poster="{{ $thumbnail->url }}" @endif
                                    aria-label="{{ $post->title ?: 'Media from '.$provider->company_name }}"
                                >
                                    <source src="{{ $original->url }}" type="{{ $original->mime_type }}">
                                </video>

                                <button
                                    type="button"
                                    class="absolute inset-0 z-[1]"
                                    x-on:click="paused = ! paused; paused ? $refs.clip.pause() : $refs.clip.play().catch(() => {})"
                                    x-bind:aria-label="paused ? 'Play' : 'Pause'"
                                >
                                    <span class="dv-pause" x-show="paused" x-cloak><x-line-icon name="play" :size="24" :filled="true" /></span>
                                </button>

                                <button
                                    type="button"
                                    class="dv-reel-play !z-20"
                                    x-on:click="muted = ! muted; $refs.clip.muted = muted; if (! muted) { paused = false; $refs.clip.play().catch(() => {}) }"
                                    x-bind:aria-label="muted ? 'Turn sound on' : 'Mute'"
                                    x-bind:aria-pressed="! muted"
                                >
                                    <span x-show="muted"><x-line-icon name="volume-off" :size="16" /></span>
                                    <span x-show="! muted" x-cloak><x-line-icon name="volume" :size="16" /></span>
                                </button>
                            @elseif ($thumbnail)
                                <img src="{{ $thumbnail->url }}" alt="" loading="lazy">
                            @else
                                <img src="{{ $fallbackPhoto }}" alt="" loading="lazy">
                            @endif

                            <span class="dv-reel-badge uppercase tracking-[0.08em]">
                                <x-line-icon name="play" :size="11" :filled="true" /> {{ $post->type->value }}
                            </span>

                            <div class="dv-reel-card-body pointer-events-none">
                                @if ($post->title)<h3 class="dv-card-name">{{ $post->title }}</h3>@endif
                                @if ($post->caption)<p class="dv-reel-card-highlights line-clamp-2">{{ $post->caption }}</p>@endif
                                @if ($post->published_at)
                                    <p class="dv-reel-meta">{{ $post->published_at->format('M j, Y') }}</p>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>

                @if ($posts->hasPages())
                    <div class="mt-10 rounded-[1rem] border border-line bg-white px-4 py-3">{{ $posts->links() }}</div>
                @endif
            @endif
        </div>
    </section>
</div>
