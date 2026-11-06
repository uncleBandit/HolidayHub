{{-- Global search. Rendered two ways: as the full /search page (headline, big
     search field, type chips, photo result cards) and, when embedded in a
     header, as a compact field with a keyboard-navigable dropdown. Both call
     selectResult() so routing stays in the component. --}}
<div>
@if ($asPage)
    @php
        $isUsable = fn ($image) => is_string($image)
            && filled($image)
            && ! \Illuminate\Support\Str::contains($image, ['placeholder', 'placehold', 'picsum', 'lorempixel', 'source.unsplash']);
        $imageFor = function (array $row, array $keys, string $fallback) use ($isUsable) {
            $candidates = [];
            foreach ($keys as $key) {
                $value = $row[$key] ?? null;
                if (is_string($value) && str_starts_with(trim($value), '[')) {
                    $value = json_decode($value, true);
                }
                $candidates = array_merge($candidates, (array) $value);
            }
            $image = collect($candidates)->first($isUsable);
            if (! $image) {
                return 'https://images.unsplash.com/photo-'.$fallback.'?auto=format&fit=crop&w=1000&q=84';
            }

            return \Illuminate\Support\Str::startsWith($image, ['http://', 'https://', '/']) ? $image : asset('storage/'.ltrim($image, '/'));
        };

        $groups = [
            'destinations' => ['label' => 'Destinations', 'type' => 'destination', 'icon' => 'map', 'images' => ['image_url', 'thumbnail', 'gallery'], 'fallback' => '1651860282131-e3257674ccd1', 'name' => 'name', 'unnamed' => 'Unnamed Destination', 'sub' => fn ($r) => collect([$r['city'] ?? null, $r['country'] ?? null])->filter()->implode(', ') ?: ($r['tagline'] ?? null)],
            'hotels' => ['label' => 'Hotels', 'type' => 'hotel', 'icon' => 'bed', 'images' => ['cover_image', 'gallery'], 'fallback' => '1574236170880-fbbca132d83d', 'name' => 'name', 'unnamed' => 'Unnamed Hotel', 'sub' => fn ($r) => collect([$r['city'] ?? null, $r['country'] ?? null])->filter()->implode(', ')],
            'villas' => ['label' => 'Villas', 'type' => 'villa', 'icon' => 'waves', 'images' => ['cover_image', 'gallery'], 'fallback' => '1561501900-3701fa6a0864', 'name' => 'name', 'unnamed' => 'Unnamed Villa', 'sub' => fn ($r) => collect([$r['city'] ?? null, $r['country'] ?? null])->filter()->implode(', ')],
            'bnbs' => ['label' => 'B&Bs', 'type' => 'bnb', 'icon' => 'leaf', 'images' => ['cover_image', 'gallery'], 'fallback' => '1708119063168-4785d1359824', 'name' => 'name', 'unnamed' => 'Unnamed B&B', 'sub' => fn ($r) => collect([$r['city'] ?? null, $r['country'] ?? null])->filter()->implode(', ')],
            'packages' => ['label' => 'Packages', 'type' => 'package', 'icon' => 'ticket', 'images' => ['cover_image', 'gallery'], 'fallback' => '1664093671658-a2aac3a344a9', 'name' => 'title', 'unnamed' => 'Unnamed Package', 'sub' => fn ($r) => $r['short_description'] ?? null],
            'activities' => ['label' => 'Experiences', 'type' => 'activity', 'icon' => 'compass', 'images' => ['thumbnail', 'gallery'], 'fallback' => '1585402969048-86f38f8bfc2e', 'name' => 'name', 'unnamed' => 'Unnamed Activity', 'sub' => fn ($r) => $r['short_description'] ?? null],
        ];

        $counts = collect($groups)->map(fn ($g, $key) => count($results[$key] ?? []));
        $total = $counts->sum();
        $searching = strlen($query) > 2;
    @endphp

    <div x-data="{ show: 'all' }">
        {{-- ─────────────────────────────  Header  ───────────────────────── --}}
        <section class="dv-section !pb-6">
            <p class="dv-eyebrow dv-magic-in" style="--i: 0">Search HolidayHub</p>
            <h1 class="dv-magic-in mt-3 font-dm-serif text-[clamp(2.4rem,5.5vw,4.6rem)] leading-[0.95] tracking-[-0.03em] text-forest" style="--i: 1">
                Where to <span class="italic text-clay">next?</span>
            </h1>

            <form class="dv-searchbar dv-magic-in max-w-[44rem]" role="search" x-on:submit.prevent style="--i: 2">
                <span class="dv-search-icon" aria-hidden="true">
                    <span wire:loading.remove wire:target="query"><x-line-icon name="search" :size="20" /></span>
                    <span wire:loading wire:target="query" class="block h-5 w-5 animate-spin rounded-full border-2 border-clay/30 border-t-clay"></span>
                </span>
                <span class="dv-search-copy">
                    <input
                        wire:model.live="query"
                        type="search"
                        autofocus
                        placeholder="Search hotels, packages, destinations..."
                        aria-label="Search hotels, packages, destinations, villas, B&Bs and experiences"
                        class="h-auto focus:outline-none"
                    >
                    <small>
                        @if ($searching)
                            {{ $total }} {{ \Illuminate\Support\Str::plural('result', $total) }} for “{{ $query }}”
                        @else
                            Type at least three letters
                        @endif
                    </small>
                </span>
                <span class="dv-search-go" aria-hidden="true"><x-line-icon name="arrow" :size="19" /></span>
            </form>
        </section>

        <section class="dv-section !pt-0" wire:loading.class="opacity-60" wire:target="query">
            @if (! $searching)
                {{-- Before typing: point somewhere useful. --}}
                <div class="dv-magic-night overflow-hidden rounded-[1.6rem] px-6 py-12 text-white md:px-12">
                    <div class="relative z-10 max-w-[34rem]">
                        <p class="dv-eyebrow-light">Not sure yet?</p>
                        <p class="mt-2 font-dm-serif text-[clamp(1.8rem,3.5vw,2.8rem)] leading-tight">Start with a feeling, not a name.</p>
                        <p class="mt-3 text-[13px] leading-relaxed text-white/70">Search by town, coastline, stay or experience — or wander one of these.</p>
                        <div class="mt-6 flex flex-wrap gap-3">
                            <a href="{{ route('destination.index') }}" class="dv-btn dv-btn-primary"><x-line-icon name="map" :size="16" /> Destinations</a>
                            <a href="{{ route('stays.index') }}" class="dv-btn dv-magic-glass"><x-line-icon name="bed" :size="16" /> Stays</a>
                            <a href="{{ route('experiences.index') }}" class="dv-btn dv-magic-glass"><x-line-icon name="compass" :size="16" /> Experiences</a>
                        </div>
                    </div>
                </div>
            @elseif ($total === 0)
                <div class="flex flex-col items-center rounded-[1.2rem] border border-dashed border-line bg-surface px-6 py-16 text-center">
                    <span class="grid h-14 w-14 place-items-center rounded-full bg-cream text-clay"><x-line-icon name="search" :size="24" /></span>
                    <p class="mt-4 font-dm-serif text-[1.6rem] text-forest">No results found</p>
                    <p class="mt-1.5 max-w-[26rem] text-[13px] leading-relaxed text-muted">Nothing matches “{{ $query }}”. Try another spelling, a town, or a country.</p>
                    <div class="mt-6 flex flex-wrap justify-center gap-3">
                        <a href="{{ route('stays.index') }}" class="dv-btn dv-btn-primary">Browse stays</a>
                        <a href="{{ route('experiences.index') }}" class="dv-btn dv-btn-ghost">Browse experiences</a>
                    </div>
                </div>
            @else
                {{-- Type chips --}}
                <div class="dv-category-row" role="tablist" aria-label="Filter results by type">
                    <button type="button" role="tab" class="dv-category !py-3" x-bind:class="show === 'all' && 'dv-category-active'" x-on:click="show = 'all'">
                        <x-line-icon name="grid" :size="16" /> All <span class="opacity-70">{{ $total }}</span>
                    </button>
                    @foreach ($groups as $key => $group)
                        @if ($counts[$key] > 0)
                            <button type="button" role="tab" class="dv-category !py-3" x-bind:class="show === '{{ $key }}' && 'dv-category-active'" x-on:click="show = '{{ $key }}'">
                                <x-line-icon :name="$group['icon']" :size="16" /> {{ $group['label'] }} <span class="opacity-70">{{ $counts[$key] }}</span>
                            </button>
                        @endif
                    @endforeach
                </div>

                <div class="mt-8 space-y-12">
                    @foreach ($groups as $key => $group)
                        @if ($counts[$key] > 0)
                            <div x-show="show === 'all' || show === '{{ $key }}'" wire:key="search-group-{{ $key }}">
                                <div class="flex items-baseline gap-3">
                                    <h2 class="dv-title !text-[clamp(1.5rem,2.4vw,2.1rem)]">{{ $group['label'] }}</h2>
                                    <span class="text-[13px] font-bold text-clay">{{ $counts[$key] }}</span>
                                </div>
                                <div class="mt-5 grid auto-rows-[15rem] grid-cols-1 gap-4 sm:grid-cols-2 sm:auto-rows-[17rem] lg:grid-cols-3 xl:grid-cols-4">
                                    @foreach ($results[$key] as $row)
                                        @php($sub = ($group['sub'])($row))
                                        <button
                                            type="button"
                                            wire:click="selectResult('{{ $group['type'] }}', {{ (int) ($row['id'] ?? 0) }})"
                                            wire:key="search-{{ $key }}-{{ $row['id'] ?? $loop->index }}"
                                            class="dv-card group text-left"
                                        >
                                            <img src="{{ $imageFor($row, $group['images'], $group['fallback']) }}" alt="{{ $row[$group['name']] ?? $row['name'] ?? $group['unnamed'] }}" loading="lazy">
                                            <span class="dv-card-gradient" aria-hidden="true"></span>
                                            <span class="dv-card-mark" aria-hidden="true"><x-line-icon :name="$group['icon']" :size="16" /></span>
                                            <span class="dv-card-body block pr-12">
                                                <span class="dv-card-kicker block">{{ \Illuminate\Support\Str::singular($group['label']) === 'B&B' ? 'B&B' : \Illuminate\Support\Str::singular($group['label']) }}</span>
                                                <span class="dv-card-name block">{{ $row[$group['name']] ?? $row['name'] ?? $group['unnamed'] }}</span>
                                                @if (filled($sub))
                                                    <span class="dv-card-sub line-clamp-1 block">{{ \Illuminate\Support\Str::limit(strip_tags((string) $sub), 70) }}</span>
                                                @endif
                                            </span>
                                            <span class="dv-card-arrow" aria-hidden="true"><x-line-icon name="arrow-up-right" :size="17" /></span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
        </section>
    </div>
@else
    {{-- ───────────────────────  Embedded header search  ─────────────────── --}}
    <div class="flex w-full justify-center">
        <div
            x-data="{ showDropdown: false, highlightIndex: 0, loading: false }"
            x-effect="
                showDropdown = $wire.query.length > 2 &&
                               Object.values($wire.results).some(r => r.length > 0);
                if (!showDropdown) highlightIndex = 0;
            "
            class="relative w-full max-w-2xl"
        >
            <div class="relative">
                <input
                    wire:model.live="query"
                    x-on:input="loading = true"
                    x-on:keydown.arrow-down.prevent="if (showDropdown) highlightIndex++"
                    x-on:keydown.arrow-up.prevent="if (showDropdown && highlightIndex > 0) highlightIndex--"
                    x-on:keydown.enter.prevent="$refs.dropdown.querySelectorAll('li')[highlightIndex]?.click()"
                    type="text"
                    placeholder="Search hotels, packages, destinations..."
                    aria-label="Search"
                    class="w-full rounded-full border border-line bg-white py-3 pl-11 pr-10 text-[13px] font-semibold text-ink shadow-sm transition placeholder:text-muted focus:border-forest focus:ring-0"
                />
                <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-clay"><x-line-icon name="search" :size="17" /></span>
                <span wire:loading wire:target="query" class="absolute inset-y-0 right-4 flex items-center">
                    <span class="block h-4 w-4 animate-spin rounded-full border-2 border-clay/30 border-t-clay"></span>
                </span>
            </div>

            <div
                x-show="showDropdown"
                x-ref="dropdown"
                x-on:click.outside="showDropdown = false"
                x-on:keydown.escape.window="showDropdown = false"
                x-transition
                x-cloak
                class="absolute z-50 mt-2 max-h-80 w-full overflow-y-auto rounded-[1rem] border border-line bg-surface p-2 text-ink shadow-[0_20px_60px_rgba(20,36,28,0.18)]"
            >
                @foreach ([
                    'hotels' => ['Hotels', 'hotel', 'name', 'Unnamed Hotel'],
                    'packages' => ['Packages', 'package', 'title', 'Unnamed Package'],
                    'destinations' => ['Destinations', 'destination', 'name', 'Unnamed Destination'],
                    'bnbs' => ['B&B', 'bnb', 'name', 'Unnamed B&B'],
                    'villas' => ['Villas', 'villa', 'name', 'Unnamed Villa'],
                    'activities' => ['Activities', 'activity', 'name', 'Unnamed Activity'],
                ] as $key => [$label, $type, $field, $unnamed])
                    <template x-if="$wire.results.{{ $key }} && $wire.results.{{ $key }}.length">
                        <div class="px-2 py-2">
                            <h3 class="mb-1 px-2 text-[10px] font-extrabold uppercase tracking-[0.12em] text-clay">{{ $label }}</h3>
                            <ul class="space-y-0.5">
                                <template x-for="(row, i) in $wire.results.{{ $key }}" :key="row.id ?? i">
                                    <li
                                        x-on:click="$wire.selectResult('{{ $type }}', row.id)"
                                        :class="{ 'bg-cream text-forest': i === highlightIndex }"
                                        class="cursor-pointer rounded-[0.6rem] px-3 py-2 text-[13px] font-semibold transition hover:bg-cream"
                                    >
                                        <span x-text="row.{{ $field }} ?? '{{ $unnamed }}'"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </template>
                @endforeach

                <template x-if="!Object.values($wire.results).some(r => r.length > 0)">
                    <div class="p-4 text-center text-[13px] text-muted">No results found. Try another search.</div>
                </template>
            </div>
        </div>
    </div>
@endif
</div>
