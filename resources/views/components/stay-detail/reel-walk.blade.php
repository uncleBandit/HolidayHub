{{-- The reel walk: a property's scenes played full screen like stories.
     Films play through and advance when they end; photos hold for five
     seconds. Tap the left or right of the screen (or swipe, or use the arrow
     keys) to move, hold to pause, and jump by space with the chips.

     `scenes` takes PropertyScene models or the arrays SceneBuilder keeps.
     With `inline` the page also gets a "Step inside" strip of the scenes;
     without it only the player is rendered, opened by an `open-reel-walk`
     window event (the builder's preview uses that). The event may carry a
     start index: $dispatch('open-reel-walk', { index: 2 }).

     `eyebrow` and `heading` replace the strip's "The walk-through" /
     "Step inside {name}" wording (experiences and journeys use their own);
     `noun` and `cta` do the same for "scene" and the play button. --}}
@props([
    'scenes' => [],
    'name' => '',
    'inline' => true,
    'eyebrow' => 'The walk-through',
    'heading' => null,
    'noun' => 'scene',
    'cta' => 'Start the walk-through',
])

@php
    $items = collect($scenes)
        ->map(fn ($scene) => $scene instanceof \App\Modules\Accommodation\Domain\Models\PropertyScene
            ? ['url' => $scene->url(), 'video' => $scene->isVideo(), 'title' => (string) $scene->title, 'caption' => (string) $scene->caption, 'space' => $scene->spaceLabel()]
            : [
                'url' => $scene['url'] ?? null,
                'video' => (bool) ($scene['is_video'] ?? $scene['video'] ?? false),
                'title' => (string) ($scene['title'] ?? ''),
                'caption' => (string) ($scene['caption'] ?? ''),
                'space' => \App\Modules\Accommodation\Domain\Models\PropertyScene::labelFor($scene['space'] ?? null)
                    ?? ($scene['space_label'] ?? null),
            ])
        ->filter(fn ($scene) => filled($scene['url']))
        ->values();
    $films = $items->where('video', true)->count();
@endphp

@if ($items->isNotEmpty())
<div
    x-data="{
        scenes: @js($items->all()),
        open: false,
        index: 0,
        progress: 0,
        held: false,
        muted: true,
        timer: null,
        touchX: null,
        get current() { return this.scenes[this.index] },
        play(i = 0) {
            this.index = i;
            this.open = true;
            document.body.classList.add('overflow-hidden');
            this.start();
        },
        close() {
            this.open = false;
            clearInterval(this.timer);
            document.body.classList.remove('overflow-hidden');
            this.$refs.video?.pause();
        },
        start() {
            clearInterval(this.timer);
            this.progress = 0;
            this.$nextTick(() => {
                if (this.current.video) {
                    const video = this.$refs.video;
                    if (! video) return;
                    video.currentTime = 0;
                    video.muted = this.muted;
                    video.play().catch(() => {});
                    return;
                }
                // Photos hold for five seconds.
                this.timer = setInterval(() => {
                    if (this.held) return;
                    this.progress = Math.min(1, this.progress + 0.01);
                    if (this.progress >= 1) this.next();
                }, 50);
            });
        },
        next() { this.index < this.scenes.length - 1 ? (this.index++, this.start()) : this.close() },
        prev() { this.index > 0 ? (this.index--, this.start()) : this.start() },
        hold(on) {
            this.held = on;
            const video = this.$refs.video;
            if (video && this.current.video) on ? video.pause() : video.play().catch(() => {});
        },
        toggleSound() {
            this.muted = ! this.muted;
            if (this.$refs.video) this.$refs.video.muted = this.muted;
        },
        swipe(event) {
            if (this.touchX === null) return;
            const dx = event.changedTouches[0].clientX - this.touchX;
            this.touchX = null;
            if (Math.abs(dx) > 50) dx < 0 ? this.next() : this.prev();
        },
    }"
    x-on:open-reel-walk.window="play(Math.min(Math.max(0, parseInt($event.detail?.index ?? 0) || 0), scenes.length - 1))"
    x-on:keydown.escape.window="open && close()"
    x-on:keydown.arrow-right.window="open && next()"
    x-on:keydown.arrow-left.window="open && prev()"
    data-no-reveal
>
    @if ($inline)
        <section {{ $attributes->merge(['class' => 'py-2']) }}>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="dv-eyebrow">{{ $eyebrow }}</p>
                    <h2 class="mt-1 font-dm-serif text-[clamp(1.8rem,3vw,2.4rem)] leading-tight text-forest">{{ $heading ?? 'Step inside '.$name }}</h2>
                    <p class="mt-1 text-[12.5px] text-muted">{{ $items->count() }} {{ Str::plural($noun, $items->count()) }}@if ($films) · {{ $films }} {{ Str::plural('film', $films) }}@endif · from {{ Str::lower($items->first()['space'] ?? 'the arrival') }} to {{ Str::lower($items->last()['space'] ?? 'the view') }}</p>
                </div>
                <button type="button" x-on:click="play(0)" class="dv-btn dv-btn-primary min-h-[3.2rem] px-6 text-[13px]"><x-line-icon name="play" :size="14" filled /> {{ $cta }}</button>
            </div>

            <div class="-mx-4 mt-6 flex snap-x gap-3 overflow-x-auto px-4 pb-2 no-scrollbar sm:mx-0 sm:px-0">
                @foreach ($items as $i => $scene)
                    <button type="button" x-on:click="play({{ $i }})" class="group relative aspect-[9/14] w-[10.5rem] flex-none snap-start overflow-hidden rounded-[1rem] bg-[#0C1511] text-left text-white sm:w-[12rem]">
                        @if ($scene['video'])
                            <video src="{{ $scene['url'] }}" class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 group-hover:scale-105" muted loop playsinline preload="metadata" x-on:mouseenter="$el.play()" x-on:mouseleave="$el.pause()" aria-hidden="true"></video>
                        @else
                            <img src="{{ $scene['url'] }}" alt="" class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 group-hover:scale-105" loading="lazy">
                        @endif
                        <span class="absolute inset-0 bg-[linear-gradient(0deg,rgba(8,22,17,0.85),transparent_55%)]"></span>
                        <span class="absolute left-2.5 top-2.5 grid h-7 w-7 place-items-center rounded-full bg-black/40 font-dm-serif text-[0.85rem] backdrop-blur-md">{{ $i + 1 }}</span>
                        @if ($scene['video'])
                            <span class="absolute right-2.5 top-2.5 grid h-7 w-7 place-items-center rounded-full bg-black/40 backdrop-blur-md"><x-line-icon name="play" :size="11" filled /></span>
                        @endif
                        <span class="absolute inset-x-3 bottom-3">
                            @if ($scene['space'])<span class="block text-[9.5px] font-extrabold uppercase tracking-[0.16em] text-[#F6D6C9]">{{ $scene['space'] }}</span>@endif
                            <span class="mt-0.5 line-clamp-2 block font-dm-serif text-[1.05rem] leading-tight">{{ $scene['title'] ?: ($scene['space'] ?? 'Scene '.($i + 1)) }}</span>
                        </span>
                    </button>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Player --}}
    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            x-transition.opacity.duration.300ms
            class="fixed inset-0 z-[130] flex items-center justify-center bg-black text-white"
            role="dialog"
            aria-modal="true"
            aria-label="{{ $name }} walk-through"
            x-on:touchstart="touchX = $event.touches[0].clientX"
            x-on:touchend="swipe($event)"
        >
            <div class="relative h-full w-full max-w-[34rem] overflow-hidden md:my-6 md:h-[calc(100%-3rem)] md:rounded-[1.4rem]">
                <template x-if="current.video">
                    <video x-ref="video" :src="current.url" class="absolute inset-0 h-full w-full object-cover" playsinline autoplay
                           x-on:timeupdate="progress = $el.duration ? $el.currentTime / $el.duration : 0"
                           x-on:ended="next()"></video>
                </template>
                <template x-if="! current.video">
                    <img :src="current.url" alt="" class="absolute inset-0 h-full w-full object-cover" style="animation: dv-kenburns 6s ease-out forwards">
                </template>
                <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(180deg,rgba(0,0,0,0.55),transparent_22%,transparent_55%,rgba(0,0,0,0.8))]"></div>

                {{-- Progress --}}
                <div class="absolute inset-x-3 top-3 z-20 flex gap-1">
                    <template x-for="(scene, i) in scenes" :key="i">
                        <span class="h-[3px] flex-1 overflow-hidden rounded-full bg-white/30">
                            <span class="block h-full bg-white" :style="`width: ${i < index ? 100 : (i === index ? progress * 100 : 0)}%`"></span>
                        </span>
                    </template>
                </div>

                {{-- Top bar --}}
                <div class="absolute inset-x-3 top-6 z-20 flex items-center justify-between gap-3">
                    <p class="truncate text-[12px] font-bold"><span x-text="index + 1"></span>/<span x-text="scenes.length"></span> · {{ $name }}</p>
                    <div class="flex items-center gap-2">
                        <button type="button" x-show="current.video" x-on:click.stop="toggleSound()" class="grid h-10 w-10 place-items-center rounded-full bg-black/35 backdrop-blur-md" :aria-label="muted ? 'Turn sound on' : 'Mute'">
                            <span x-show="muted"><x-line-icon name="volume-off" :size="17" /></span>
                            <span x-show="! muted" x-cloak><x-line-icon name="volume" :size="17" /></span>
                        </button>
                        <button type="button" x-on:click.stop="close()" class="grid h-10 w-10 place-items-center rounded-full bg-black/35 backdrop-blur-md" aria-label="Close the walk-through"><x-line-icon name="close" :size="17" /></button>
                    </div>
                </div>

                {{-- Tap zones: left goes back, right goes on; holding pauses --}}
                <button type="button" class="absolute bottom-40 left-0 top-20 z-10 w-1/3" x-on:click="prev()" x-on:pointerdown="hold(true)" x-on:pointerup="hold(false)" x-on:pointerleave="held && hold(false)" aria-label="Previous scene"></button>
                <button type="button" class="absolute bottom-40 right-0 top-20 z-10 w-2/3" x-on:click="next()" x-on:pointerdown="hold(true)" x-on:pointerup="hold(false)" x-on:pointerleave="held && hold(false)" aria-label="Next scene"></button>

                {{-- Story --}}
                <div class="absolute inset-x-5 bottom-6 z-20">
                    <p class="text-[10.5px] font-extrabold uppercase tracking-[0.2em] text-[#F6D6C9]" x-text="current.space || ''"></p>
                    <p class="mt-1 font-dm-serif text-[clamp(1.6rem,6vw,2.2rem)] leading-[1.05]" x-text="current.title || current.space || ''"></p>
                    <p class="mt-2 max-w-[28rem] text-[13px] leading-relaxed text-white/80" x-show="current.caption" x-text="current.caption"></p>

                    <div class="-mx-5 mt-4 flex gap-1.5 overflow-x-auto px-5 no-scrollbar">
                        <template x-for="(scene, i) in scenes" :key="'chip' + i">
                            <button type="button" x-on:click.stop="index = i; start()"
                                    class="flex-none rounded-full border px-3 py-1.5 text-[11px] font-bold backdrop-blur-md transition-colors"
                                    :class="i === index ? 'border-white bg-white text-black' : 'border-white/30 bg-black/30 text-white hover:bg-black/50'"
                                    x-text="scene.space || ('Scene ' + (i + 1))"></button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
@endif
