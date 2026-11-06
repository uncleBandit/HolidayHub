{{-- Stay detail gallery hero: main photo plus two side photos on desktop, a
     single photo on mobile, glass back / share / save buttons and a client-side
     lightbox over every photo. Shared by the hotel, villa and B&B pages.

     `images` may hold absolute URLs or storage paths, photos and films mixed;
     placeholders are dropped and a verified photo stands in when nothing real
     is left. `video` is an optional cover film: it plays in the main slot
     (the first photo is its poster) and opens first in the lightbox. --}}
@props([
    'images' => [],
    'video' => null,
    'name' => '',
    'kind' => 'hotel',
    'wishlisted' => false,
    'backUrl' => null,
])

@php
    $fallbacks = [
        'hotel' => ['1574236170880-fbbca132d83d', '1708119063168-4785d1359824', '1651860282131-e3257674ccd1'],
        'villa' => ['1561501900-3701fa6a0864', '1708119063168-4785d1359824', '1667935837291-1dc178866251'],
        'bnb' => ['1574236170880-fbbca132d83d', '1651860282131-e3257674ccd1', '1613061445510-e296bfedb73e'],
    ];

    $photos = collect($images)
        ->filter(fn ($image) => is_string($image) && trim($image) !== '')
        ->map(function (string $image) {
            if (\Illuminate\Support\Str::contains($image, ['via.placeholder', 'placehold', 'picsum', 'source.unsplash', 'default-hero'])) {
                return null;
            }

            if (\Illuminate\Support\Str::startsWith($image, ['http://', 'https://'])) {
                // Absolute URLs that were wrongly prefixed with /storage/.
                return preg_replace('#^.*?/storage/(https?://)#', '$1', $image);
            }

            $path = ltrim(preg_replace('#^/?storage/#', '', $image), '/');

            return \Illuminate\Support\Facades\Storage::disk('public')->exists($path)
                ? \Illuminate\Support\Facades\Storage::url($path)
                : null;
        })
        ->filter()
        ->unique()
        ->values();

    if ($photos->isEmpty()) {
        $photos = collect([
            'https://images.unsplash.com/photo-'.($fallbacks[$kind] ?? $fallbacks['hotel'])[0].'?auto=format&fit=crop&w=1400&q=84',
        ]);
    }

    $isFilm = fn (?string $url) => \App\Shared\Support\Media::isVideo($url);
    $film = \App\Shared\Support\Media::url($video);
    $poster = $photos->first(fn ($url) => ! $isFilm($url));

    // The lightbox walks the cover film first, then every photo and film.
    $items = collect([$film])->filter()->concat($photos)->unique()->values();

    $count = $items->count();
    $films = $items->filter($isFilm)->count();
    $side = $items->slice(1, 2)->values();
    $backUrl = $backUrl ?: route('stays.index');
@endphp

<section
    {{ $attributes->merge(['class' => 'relative isolate bg-ink']) }}
    x-data="{
        open: false,
        index: 0,
        photos: @js($items->all()),
        isFilm(url) { return /\.(mp4|webm|mov|m4v)(\?|$)/i.test(url || '') },
        copied: false,
        show(i) { this.index = i; this.open = true },
        next() { this.index = (this.index + 1) % this.photos.length },
        prev() { this.index = (this.index - 1 + this.photos.length) % this.photos.length },
        back() { if (window.history.length > 1 && document.referrer.startsWith(window.location.origin)) { window.history.back() } else { window.location.href = @js($backUrl) } },
        async share() {
            const data = { title: @js($name), url: window.location.href };
            try {
                if (navigator.share) { await navigator.share(data); return }
                await navigator.clipboard.writeText(data.url);
                this.copied = true;
                setTimeout(() => this.copied = false, 2200);
            } catch (e) {}
        },
    }"
    x-on:keydown.escape.window="open = false"
    x-on:keydown.arrow-right.window="open && next()"
    x-on:keydown.arrow-left.window="open && prev()"
    aria-label="{{ $name }} photos"
    data-no-reveal
>
    <div @class([
        'grid h-[52vh] min-h-[21rem] max-h-[44rem] gap-[0.45rem] md:h-[64vh] md:min-h-[30rem]',
        'md:grid-cols-[2fr_1fr]' => $count > 1,
    ])>
        <button type="button" class="group relative block h-full w-full overflow-hidden" x-on:click="show(0)" aria-label="Open {{ $isFilm($items[0]) ? 'film' : 'photo' }} 1 of {{ $count }}">
            @if ($isFilm($items[0]))
                <video src="{{ $items[0] }}" @if ($poster) poster="{{ $poster }}" @endif class="h-full w-full object-cover" autoplay muted loop playsinline preload="auto" aria-hidden="true"></video>
            @else
                <img src="{{ $items[0] }}" alt="{{ $name }}" class="h-full w-full object-cover transition-transform duration-[1.2s] ease-out group-hover:scale-[1.03]" fetchpriority="high">
            @endif
            <span class="pointer-events-none absolute inset-0 bg-gradient-to-t from-[#081611]/55 via-transparent to-[#081611]/35"></span>
        </button>

        @if ($count > 1)
            <div @class(['hidden h-full gap-[0.45rem] overflow-hidden md:grid', 'grid-rows-2' => $side->count() > 1])>
                @foreach ($side as $i => $photo)
                    <button type="button" class="group relative block h-full w-full overflow-hidden" x-on:click="show({{ $i + 1 }})" aria-label="Open photo {{ $i + 2 }} of {{ $count }}">
                        @if ($isFilm($photo))
                            <video src="{{ $photo }}" class="h-full w-full object-cover" muted loop playsinline preload="none" data-autoplay aria-hidden="true"></video>
                            <span class="pointer-events-none absolute left-3 top-3 inline-flex items-center gap-1 rounded-full bg-black/45 px-2.5 py-1 text-[10px] font-extrabold text-white backdrop-blur-md"><x-line-icon name="play" :size="10" filled /> Film</span>
                        @else
                            <img src="{{ $photo }}" alt="{{ $name }} photo {{ $i + 2 }}" class="h-full w-full object-cover transition-transform duration-[1.2s] ease-out group-hover:scale-[1.04]" loading="lazy">
                        @endif
                        @if ($loop->last && $count > 3)
                            <span class="absolute inset-0 grid place-items-center bg-[#081611]/45 font-dm-serif text-[1.6rem] text-white">+{{ $count - 3 }} more</span>
                        @endif
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Glass navigation --}}
    <div class="absolute inset-x-4 top-4 z-10 flex items-center justify-between sm:inset-x-6 sm:top-6 lg:inset-x-[max(2rem,calc((100vw-1250px)/2))]">
        <button type="button" x-on:click="back()" class="grid h-11 w-11 place-items-center rounded-full border border-white/30 bg-black/25 text-white backdrop-blur-md transition hover:bg-black/40" aria-label="Go back">
            <x-line-icon name="chevron" :size="20" class="rotate-180" />
        </button>

        <div class="flex items-center gap-2">
            <span x-show="copied" x-transition.opacity x-cloak class="rounded-full bg-black/45 px-3 py-1.5 text-[11px] font-bold text-white backdrop-blur-md">Link copied</span>
            <button type="button" x-on:click="share()" class="grid h-11 w-11 place-items-center rounded-full border border-white/30 bg-black/25 text-white backdrop-blur-md transition hover:bg-black/40" aria-label="Share {{ $name }}">
                <x-line-icon name="share" :size="18" />
            </button>
            <button
                type="button"
                wire:click="toggleWishlist"
                wire:loading.attr="disabled"
                wire:target="toggleWishlist"
                @class([
                    'grid h-11 w-11 place-items-center rounded-full border backdrop-blur-md transition',
                    'border-clay bg-clay text-white hover:bg-clay-dark' => $wishlisted,
                    'border-white/30 bg-black/25 text-white hover:bg-black/40' => ! $wishlisted,
                ])
                aria-pressed="{{ $wishlisted ? 'true' : 'false' }}"
                aria-label="{{ $wishlisted ? 'Remove from saved' : 'Save to wishlist' }}"
            >
                <x-line-icon name="bookmark" :size="18" :filled="$wishlisted" />
            </button>
        </div>
    </div>

    <button type="button" x-on:click="show(0)" class="absolute bottom-14 left-4 z-10 inline-flex items-center gap-2 rounded-full border border-white/30 bg-black/30 px-4 py-2.5 text-[11px] font-extrabold text-white backdrop-blur-md transition hover:bg-black/45 sm:left-6 lg:bottom-6 lg:left-[max(2rem,calc((100vw-1250px)/2))]">
        <x-line-icon name="grid" :size="15" />
        @if ($films)
            {{ $count }} photos &amp; films
        @else
            {{ $count === 1 ? 'View photo' : 'All '.$count.' photos' }}
        @endif
    </button>

    {{-- Lightbox --}}
    <template x-teleport="body">
        <div
            x-show="open"
            x-transition.opacity.duration.250ms
            x-cloak
            class="fixed inset-0 z-[120] flex flex-col bg-[#0C1511]/95 text-white"
            role="dialog"
            aria-modal="true"
            aria-label="{{ $name }} gallery"
        >
            <div class="flex items-center justify-between px-4 py-4 sm:px-8">
                <p class="text-[12px] font-bold text-white/70"><span x-text="index + 1"></span> / <span x-text="photos.length"></span></p>
                <p class="hidden font-dm-serif text-[1.25rem] sm:block">{{ $name }}</p>
                <button type="button" x-on:click="open = false" class="grid h-11 w-11 place-items-center rounded-full border border-white/25 bg-white/10 transition hover:bg-white/20" aria-label="Close gallery">
                    <x-line-icon name="close" :size="18" />
                </button>
            </div>

            <div class="relative flex min-h-0 flex-1 items-center justify-center px-4 pb-8 sm:px-20">
                <template x-if="isFilm(photos[index])">
                    <video :src="photos[index]" x-init="$el.play().catch(() => {})" controls playsinline loop class="max-h-full max-w-full rounded-[1rem] object-contain shadow-[0_30px_80px_rgba(0,0,0,0.45)]"></video>
                </template>
                <template x-if="! isFilm(photos[index])">
                    <img :src="photos[index]" alt="{{ $name }}" class="max-h-full max-w-full rounded-[1rem] object-contain shadow-[0_30px_80px_rgba(0,0,0,0.45)]">
                </template>

                <template x-if="photos.length > 1">
                    <div>
                        <button type="button" x-on:click="prev()" class="absolute left-3 top-1/2 grid h-12 w-12 -translate-y-1/2 place-items-center rounded-full border border-white/25 bg-black/35 backdrop-blur-md transition hover:bg-black/55 sm:left-6" aria-label="Previous photo">
                            <x-line-icon name="chevron" :size="22" class="rotate-180" />
                        </button>
                        <button type="button" x-on:click="next()" class="absolute right-3 top-1/2 grid h-12 w-12 -translate-y-1/2 place-items-center rounded-full border border-white/25 bg-black/35 backdrop-blur-md transition hover:bg-black/55 sm:right-6" aria-label="Next photo">
                            <x-line-icon name="chevron" :size="22" />
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </template>
</section>
