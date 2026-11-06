@php
    use App\Modules\Accommodation\Domain\Models\Accommodation;
    use App\Modules\Accommodation\Domain\Models\BedAndBreakfast;
    use App\Modules\Accommodation\Domain\Models\Hotel;
    use App\Modules\Accommodation\Domain\Models\Villa;
    use App\Modules\Activities\Domain\Models\Activity;
    use App\Modules\Media\Domain\Enums\MediaAssetType;

    // What a reel is about (a stay or an experience), resolved for the product chip.
    $product = function ($post): ?array {
        $target = $post->targetable;
        $item = $target instanceof Accommodation ? $target->bookable : $target;

        $url = match (true) {
            $item instanceof Hotel => route('hotel-show', $item),
            $item instanceof Villa => route('villa.show', $item),
            $item instanceof BedAndBreakfast => route('bedandbreakfast.show', $item),
            $item instanceof Activity => route('activity.show', $item),
            default => null,
        };

        if ($url === null) {
            return null;
        }

        $isExperience = $item instanceof Activity;
        $price = $isExperience
            ? (float) $item->base_price
            : (float) ($item->price_per_night ?? $item->avg_price_per_night ?? 0);

        return [
            'kicker' => match (true) {
                $item instanceof Hotel => 'Hotel',
                $item instanceof Villa => 'Villa',
                $item instanceof BedAndBreakfast => 'B&B',
                default => 'Experience',
            },
            'name' => $item->name ?? ($target->name ?? 'View listing'),
            'price' => $price > 0
                ? 'From '.($isExperience ? ($item->currency ?: 'KSh') : 'KSh').' '.number_format($price).($isExperience ? '' : ' / night')
                : null,
            'url' => $url,
            'cta' => $isExperience ? 'View experience' : 'View stay',
        ];
    };
@endphp

<section class="dv-reels-screen !h-[calc(100dvh-76px)] !min-h-[34rem] md:!h-[calc(100dvh-88px)]" aria-label="Reel stream">
    <div class="dv-feed">
        @forelse ($posts as $post)
            @php
                $original = $post->asset(MediaAssetType::Original);
                $thumbnail = $post->asset(MediaAssetType::Thumbnail);
                $provider = $post->provider;
                $item = $product($post);
                $initials = Str::upper(Str::substr(preg_replace('/[^A-Za-z]/', '', (string) $provider->company_name), 0, 2)) ?: 'HH';
            @endphp

            <article
                wire:key="reel-{{ $post->id }}"
                class="dv-feed-panel"
                data-no-reveal
                x-data="{ liked: false, saved: false, muted: true, paused: false }"
            >
                @if ($original)
                    <video
                        x-ref="reel"
                        data-autoplay
                        muted
                        loop
                        playsinline
                        preload="none"
                        x-bind:muted="muted"
                        x-on:play="paused = false"
                        @if ($thumbnail) poster="{{ $thumbnail->url }}" @endif
                        aria-label="{{ $post->title ?: 'Reel from '.$provider->company_name }}"
                    >
                        <source src="{{ $original->url }}" type="{{ $original->mime_type }}">
                    </video>
                @else
                    <img src="https://images.unsplash.com/photo-1651860282131-e3257674ccd1?auto=format&fit=crop&w=1400&q=84" alt="" class="absolute inset-0 -z-20 h-full w-full object-cover">
                @endif

                <div class="dv-feed-shade" aria-hidden="true"></div>

                {{-- Tap the footage to pause or resume. --}}
                @if ($original)
                    <button
                        type="button"
                        class="dv-video-tap"
                        x-on:click="paused = ! paused; paused ? $refs.reel.pause() : $refs.reel.play().catch(() => {})"
                        x-bind:aria-label="paused ? 'Play reel' : 'Pause reel'"
                    >
                        <span class="dv-pause" x-show="paused" x-cloak>
                            <x-line-icon name="play" :size="26" :filled="true" />
                        </span>
                    </button>
                @endif

                <div class="dv-reel-side">
                    <button type="button" class="dv-side-action" x-on:click="liked = ! liked" x-bind:data-on="liked" x-bind:aria-pressed="liked">
                        <x-line-icon name="heart" :size="24" x-bind:fill="liked ? 'currentColor' : 'none'" />
                        <small x-text="liked ? 'Liked' : 'Like'">Like</small>
                    </button>

                    <button
                        type="button"
                        class="dv-side-action"
                        data-name="{{ $post->title ?: $provider->company_name }}"
                        data-url="{{ $item['url'] ?? route('media.providers.media', $provider) }}"
                        x-on:click="navigator.share ? navigator.share({ title: $el.dataset.name, url: $el.dataset.url }).catch(() => {}) : navigator.clipboard?.writeText($el.dataset.url)"
                    >
                        <x-line-icon name="share" :size="23" />
                        <small>Share</small>
                    </button>

                    <a href="{{ route('media.providers.media', $provider) }}" class="dv-side-action">
                        <x-line-icon name="grid" :size="23" />
                        <small>Profile</small>
                    </a>

                    @if ($original)
                        <button
                            type="button"
                            class="dv-side-action"
                            x-on:click="muted = ! muted; $refs.reel.muted = muted; if (! muted) $refs.reel.play().catch(() => {})"
                            x-bind:aria-pressed="! muted"
                        >
                            <span x-show="muted"><x-line-icon name="volume-off" :size="23" /></span>
                            <span x-show="! muted" x-cloak><x-line-icon name="volume" :size="23" /></span>
                            <small x-text="muted ? 'Sound' : 'Mute'">Sound</small>
                        </button>
                    @endif
                </div>

                <div class="dv-reel-bottom">
                    <a href="{{ route('media.providers.media', $provider) }}" class="dv-provider-line w-fit max-w-full rounded-full pr-3 transition-opacity hover:opacity-90">
                        <span class="dv-provider-avatar" aria-hidden="true">{{ $initials }}</span>
                        <div class="min-w-0">
                            <p class="truncate">
                                {{ $provider->company_name }}
                                @if ($provider->is_verified)
                                    <span class="inline-grid h-4 w-4 place-items-center rounded-full bg-[#4A9FDA] align-middle text-white" aria-label="Verified provider">
                                        <x-line-icon name="check" :size="11" :stroke="3" />
                                    </span>
                                @endif
                            </p>
                            @if ($provider->city)
                                <span><x-line-icon name="map" :size="12" /> {{ $provider->city }}@if ($provider->country), {{ $provider->country }}@endif</span>
                            @endif
                        </div>
                    </a>

                    @if ($post->title)<h2 class="dv-reel-title">{{ $post->title }}</h2>@endif
                    @if ($post->caption)<p class="dv-reel-caption">{{ $post->caption }}</p>@endif

                    <div class="dv-reel-product">
                        <div class="min-w-0">
                            @if ($item)
                                <span>{{ $item['kicker'] }}</span>
                                <p class="truncate">{{ $item['name'] }}</p>
                                @if ($item['price'])<b>{{ $item['price'] }}</b>@endif
                            @else
                                <span>Provider</span>
                                <p class="truncate">{{ $provider->company_name }}</p>
                                <b>See every reel they have posted</b>
                            @endif
                        </div>

                        @if ($item)
                            <a href="{{ $item['url'] }}" class="dv-reel-cta">{{ $item['cta'] }} <x-line-icon name="arrow" :size="17" /></a>
                        @else
                            <a href="{{ route('media.providers.media', $provider) }}" class="dv-reel-cta">View provider <x-line-icon name="arrow" :size="17" /></a>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <div class="flex h-full flex-col items-center justify-center px-8 text-center">
                <span class="grid h-16 w-16 place-items-center rounded-full border border-white/20 bg-white/10 text-cream">
                    <x-line-icon name="play" :size="26" :filled="true" />
                </span>
                <h2 class="mt-6 font-dm-serif text-[1.9rem]">No reels yet</h2>
                <p class="mt-2 max-w-sm text-[13px] leading-relaxed text-white/70">Approved provider reels will show up here.</p>
                <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('stays.index') }}" class="dv-btn dv-btn-primary">Browse stays</a>
                    <a href="{{ route('experiences.index') }}" class="dv-btn border border-white/30 text-white hover:border-white">Browse experiences</a>
                </div>
            </div>
        @endforelse

        @if ($posts->isNotEmpty())
            {{-- The last stop in the feed: load the next page or sign off. --}}
            <div class="dv-feed-panel flex flex-col items-center justify-center px-8 text-center" data-no-reveal>
                @if ($hasMore)
                    <p class="dv-eyebrow-light">Keep watching</p>
                    <p class="mt-3 font-dm-serif text-[clamp(1.8rem,4vw,2.6rem)] leading-tight">There’s more where that came from.</p>
                    <button type="button" wire:click="loadMore" wire:loading.attr="disabled" class="dv-btn dv-btn-primary mt-7 px-7 disabled:opacity-50">
                        <span wire:loading.remove wire:target="loadMore">Load more reels</span>
                        <span wire:loading wire:target="loadMore">Loading…</span>
                    </button>
                @else
                    <span class="grid h-14 w-14 place-items-center rounded-full bg-clay text-white shadow-[0_0_0_9px_rgba(216,95,60,0.18)]">
                        <x-line-icon name="check" :size="26" :stroke="2.6" />
                    </span>
                    <p class="mt-6 font-dm-serif text-[clamp(1.8rem,4vw,2.6rem)] leading-tight">You’re all caught up.</p>
                    <p class="mt-2 max-w-sm text-[13px] text-white/70">New reels from providers land here as soon as they are approved.</p>
                    <a href="{{ route('stays.index') }}" class="dv-btn dv-btn-primary mt-7 px-7">Find a stay <x-line-icon name="arrow" :size="16" /></a>
                @endif
            </div>
        @endif
    </div>

    {{-- Floating header over the footage. --}}
    <div class="pointer-events-none absolute inset-x-0 top-0 z-30 flex items-start justify-between gap-4 px-4 pt-5 sm:px-8">
        <div>
            <p class="dv-eyebrow-light">Explore HolidayHub</p>
            <h1 class="mt-1 font-dm-serif text-[1.8rem] leading-none text-white drop-shadow">Reels</h1>
        </div>
        @guest
            <a href="{{ route('login') }}" class="pointer-events-auto rounded-full border border-white/25 bg-black/35 px-3.5 py-2 text-[11px] font-bold text-white backdrop-blur-md hover:bg-black/50">Provider? Sign in to post</a>
        @else
            @if (auth()->user()->provider)
                <a href="{{ route('media.provider.media') }}" class="pointer-events-auto rounded-full border border-white/25 bg-black/35 px-3.5 py-2 text-[11px] font-bold text-white backdrop-blur-md hover:bg-black/50">Manage your media</a>
            @endif
        @endguest
    </div>
</section>
