{{-- A rail of live journeys (agent packages). Self-contained: pass either
     `journeysFor` (a hotel, villa, B&B or activity: "journeys that include
     it"), `journeysIn` (a destination) or `journeysFeatured` => true; it
     renders nothing when there are none. Optional: `journeysEyebrow`,
     `journeysTitle`, `journeysClass`. (Prefixed names, so the including
     view's own variables never leak in.) --}}
@php
    $discovery = app(\App\Modules\Packages\Application\Services\PackageDiscovery::class);
    $journeys = match (true) {
        ! empty($journeysFor) => $discovery->including($journeysFor),
        ! empty($journeysIn) => $discovery->forDestination($journeysIn),
        ! empty($journeysFeatured) => $discovery->featured(),
        default => collect(),
    };
@endphp

@if ($journeys->isNotEmpty())
    <section @class([$journeysClass ?? 'py-12']) aria-label="{{ $journeysTitle ?? 'Journeys' }}">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="dv-eyebrow">{{ $journeysEyebrow ?? 'Journeys by our agents' }}</p>
                <h2 class="dv-title">{{ $journeysTitle ?? 'Journeys that include it' }}</h2>
            </div>
            <p class="text-[12px] text-muted">Multi-day trips, booked in one go</p>
        </div>

        <div class="-mx-4 mt-6 flex snap-x gap-4 overflow-x-auto px-4 pb-2 no-scrollbar sm:mx-0 sm:px-0">
            @foreach ($journeys as $journey)
                <a href="{{ $journey['url'] }}" class="group relative block aspect-[4/5] w-[16rem] flex-none snap-start overflow-hidden rounded-[1.2rem] bg-[#0C1511] text-white sm:w-[17.5rem]" wire:key="journey-{{ $journey['id'] }}">
                    @if ($journey['film'])
                        <video src="{{ $journey['film'] }}" poster="{{ $journey['image'] }}" class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 group-hover:scale-105" muted loop playsinline preload="none" x-data x-on:mouseenter="$el.play()" x-on:mouseleave="$el.pause()" aria-hidden="true"></video>
                    @else
                        <img src="{{ $journey['image'] }}" alt="" class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 group-hover:scale-105" loading="lazy">
                    @endif
                    <span class="absolute inset-0 bg-[linear-gradient(0deg,rgba(8,22,17,0.92),rgba(8,22,17,0.15)_55%,transparent)]"></span>
                    <span class="absolute left-3 top-3 inline-flex items-center gap-1 rounded-full bg-black/40 px-2.5 py-1 text-[10px] font-extrabold backdrop-blur-md">
                        @if ($journey['film'])<x-line-icon name="play" :size="10" filled />@endif {{ $journey['days'] }}-day journey
                    </span>
                    <span class="absolute inset-x-4 bottom-4">
                        @if ($journey['place'])<span class="block text-[9.5px] font-extrabold uppercase tracking-[0.16em] text-[#F6D6C9]">{{ $journey['place'] }} · {{ $journey['listings'] }} {{ Str::plural('stop', $journey['listings']) }}</span>@endif
                        <span class="mt-1 line-clamp-2 block font-dm-serif text-[1.35rem] leading-tight">{{ $journey['name'] }}</span>
                        @if ($journey['hook'])<span class="mt-1 line-clamp-2 block text-[11.5px] leading-snug text-white/75">{{ $journey['hook'] }}</span>@endif
                        <span class="mt-2 flex items-center justify-between gap-2 text-[11.5px]">
                            <span class="truncate text-white/70">{{ $journey['agency'] ? 'By '.$journey['agency'] : '' }}</span>
                            @if ($journey['from'])<b class="whitespace-nowrap">from {{ $journey['from'] }} pp</b>@endif
                        </span>
                    </span>
                </a>
            @endforeach
        </div>
    </section>
@endif
