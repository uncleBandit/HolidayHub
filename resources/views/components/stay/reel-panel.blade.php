@props([
    'card',
])

{{-- One full-screen stay in the reel feed.

     Playback is driven by an IntersectionObserver so only the panel the visitor
     is actually looking at is moving; the tap target pauses and resumes. --}}
<article
    class="dv-feed-panel"
    x-data="{ liked: false, saved: false, muted: true, paused: false }"
    x-init="
        const reel = $refs.reel
        const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches

        if (reel) {
            const observer = new IntersectionObserver(([entry]) => {
                if (entry.isIntersecting && ! reduced) {
                    paused = false
                    reel.play().catch(() => {})
                } else {
                    reel.pause()
                }
            }, { threshold: 0.55 })

            observer.observe($el)
        }
    "
>
    @if ($card['video'])
        <video
            x-ref="reel"
            src="{{ $card['video'] }}"
            poster="{{ $card['poster'] }}"
            x-bind:muted="muted"
            loop
            playsinline
            preload="metadata"
            @if ($card['clip_title']) aria-label="{{ $card['clip_title'] }}" @endif
        ></video>
    @else
        <img src="{{ $card['image'] }}" alt="{{ $card['alt'] }}" class="absolute inset-0 -z-20 h-full w-full object-cover">
    @endif

    <div class="dv-feed-shade" aria-hidden="true"></div>

    {{-- Tap anywhere on the footage to pause or resume. --}}
    <button
        type="button"
        class="dv-video-tap"
        x-on:click="paused = ! paused; if (paused) { $refs.reel && $refs.reel.pause() } else { $refs.reel && $refs.reel.play().catch(() => {}) }"
        x-bind:aria-label="paused ? 'Play reel' : 'Pause reel'"
    >
        <span class="dv-pause" x-show="paused" x-cloak>
            <x-line-icon name="play" :size="26" :filled="true" />
        </span>
    </button>

    <div class="dv-reel-side">
        <button
            type="button"
            class="dv-side-action"
            x-on:click="liked = ! liked"
            x-bind:data-on="liked"
            x-bind:aria-pressed="liked"
        >
            <x-line-icon name="heart" :size="24" x-bind:fill="liked ? 'currentColor' : 'none'" />
            <small x-text="liked ? 'Liked' : 'Like'">Like</small>
        </button>

        <button
            type="button"
            class="dv-side-action"
            x-on:click="
                if (navigator.share) {
                    navigator.share({ title: $el.dataset.name, url: $el.dataset.url }).catch(() => {})
                } else {
                    navigator.clipboard?.writeText($el.dataset.url)
                }
            "
            data-name="{{ $card['name'] }}"
            data-url="{{ $card['url'] }}"
        >
            <x-line-icon name="share" :size="23" />
            <small>Share</small>
        </button>

        <button
            type="button"
            class="dv-side-action"
            x-on:click="saved = ! saved"
            x-bind:data-saved="saved"
            x-bind:aria-pressed="saved"
        >
            <x-line-icon name="bookmark" :size="23" x-bind:fill="saved ? 'currentColor' : 'none'" />
            <small x-text="saved ? 'Saved' : 'Save'">Save</small>
        </button>

        <button
            type="button"
            class="dv-side-action"
            x-on:click="muted = ! muted; if (! muted) { $refs.reel && $refs.reel.play().catch(() => {}) }"
            x-bind:aria-pressed="! muted"
        >
            <span x-show="muted">
                <x-line-icon name="volume-off" :size="23" />
            </span>
            <span x-show="! muted" x-cloak>
                <x-line-icon name="volume" :size="23" />
            </span>
            <small x-text="muted ? 'Sound' : 'Muted'">Sound</small>
        </button>
    </div>

    <div class="dv-reel-bottom">
        <div class="dv-provider-line">
            <span class="dv-provider-avatar" aria-hidden="true">
                {{ Illuminate\Support\Str::upper(Illuminate\Support\Str::substr(preg_replace('/[^A-Za-z]/', '', $card['name']), 0, 2)) }}
            </span>

            <div class="min-w-0 flex-1">
                <p class="truncate">
                    {{ $card['name'] }}
                    <span class="inline-grid h-4 w-4 place-items-center rounded-full bg-[#4A9FDA] align-middle text-white">
                        <x-line-icon name="check" :size="11" :stroke="3" />
                    </span>
                </p>
                <span>
                    <x-line-icon name="map" :size="12" />
                    {{ $card['place'] }}
                </span>
            </div>

            @if ($card['rating'] > 0)
                <span class="dv-reel-badge !static">
                    <x-line-icon name="star" :size="12" :filled="true" />
                    {{ number_format($card['rating'], 1) }} ({{ number_format($card['reviews']) }})
                </span>
            @endif
        </div>

        <p class="dv-reel-title">{{ $card['caption'] ?: $card['name'] }}</p>

        @if ($card['guests'] > 0 || $card['price'] > 0)
            <p class="dv-reel-caption">
                {{ $card['type_label'] }}
                @if ($card['guests'] > 0)
                    · sleeps {{ $card['guests'] }}
                @endif
                @if ($card['destination'])
                    · {{ $card['destination'] }}
                @endif
            </p>
        @endif

        <div class="dv-reel-product">
            <div class="min-w-0">
                <span>{{ $card['featured'] ? 'Featured stay' : $card['type_label'] }}</span>
                <p class="truncate">
                    {{ $card['name'] }}
                    @if ($card['guests'] > 0)
                        · {{ $card['guests'] }} guests
                    @endif
                </p>
                @if ($card['price'] > 0)
                    <b>From {{ $card['currency'] }} {{ number_format($card['price']) }} / night</b>
                @endif
            </div>

            <a href="{{ $card['url'] }}" class="dv-reel-cta">
                View stay
                <x-line-icon name="arrow" :size="17" />
            </a>
        </div>
    </div>
</article>