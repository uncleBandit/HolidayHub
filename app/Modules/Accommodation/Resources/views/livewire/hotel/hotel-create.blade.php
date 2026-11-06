@php
    use App\Shared\Support\Media;

    $src = fn (?string $path) => blank($path) ? null : (Str::startsWith($path, ['http://', 'https://', '/']) ? $path : asset('storage/'.ltrim($path, '/')));
    $statusLabel = $status ? match ($status->value) {
        'published' => ['Live', 'bg-forest text-white'],
        'pending_review' => ['In review', 'bg-[#F6EEDD] text-[#8A6A3A]'],
        'rejected' => ['Changes requested', 'bg-[#F6D6C9] text-clay-dark'],
        'suspended' => ['Suspended', 'bg-clay text-white'],
        default => ['Draft', 'bg-line text-ink'],
    } : null;
    $done = fn (int $s) => $managing || $s < $step;
@endphp

<div>
    {{-- ────────────────────────────  Header  ───────────────────────────── --}}
    <section class="dv-section dv-foryou-head !pb-8">
        <div class="relative">
            <a href="{{ route('provider.dashboard') }}" class="inline-flex items-center gap-1.5 text-[12px] font-extrabold text-muted transition-colors hover:text-forest">
                <span class="rotate-180"><x-line-icon name="arrow" :size="14" /></span> Host studio
            </a>
            <div class="mt-4 flex flex-wrap items-end justify-between gap-5">
                <div>
                    <p class="dv-eyebrow">{{ $managing ? 'Manage your hotel' : 'Add a hotel' }}</p>
                    <h1 class="mt-2 font-dm-serif text-[clamp(2.2rem,4.5vw,3.8rem)] leading-[1] text-forest">
                        @if ($managing)
                            {{ $name }}
                        @else
                            Set up your <span class="italic text-clay">hotel.</span>
                        @endif
                    </h1>
                    <p class="mt-3 max-w-[38rem] text-[14px] leading-relaxed text-muted">
                        @if ($managing)
                            Update rooms, rates and inventory any time. Changes to the property details are reviewed before they go live; rates and rooms update straight away.
                        @else
                            Six short steps: tell guests who you are, show them around, then build your rooms and suites exactly as you sell them.
                        @endif
                    </p>
                </div>
                @if ($managing)
                    <div class="flex flex-wrap items-center gap-2.5">
                        @if ($statusLabel)
                            <span class="rounded-full px-3 py-1.5 text-[11px] font-extrabold {{ $statusLabel[1] }}">{{ $statusLabel[0] }}</span>
                        @endif
                        @if ($status?->value === 'published')
                            <a href="{{ route('hotel-show', $hotel) }}" class="dv-btn dv-btn-ghost">View as a guest <x-line-icon name="arrow-up-right" :size="14" /></a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </section>

    @if ($managing)
        <x-host.moderation-note :listing="$hotel?->accommodation" class="mb-4" />
    @endif

    @if ($resumed)
        <div class="px-5 md:px-[max(5vw,calc((100vw-1350px)/2))]">
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-[1rem] border border-forest/20 bg-[#E7EEE8] px-4 py-3 text-[13px] text-forest">
                <span class="flex items-center gap-2"><x-line-icon name="check" :size="16" /> <span><b>Welcome back.</b> We’ve restored <b>{{ $name ?: 'your hotel' }}</b> where you left off, at step {{ $step }}.</span></span>
                <a href="{{ route('provider.hotel-create', ['new' => 1]) }}" class="text-[12px] font-extrabold text-clay hover:underline">Start a different hotel instead</a>
            </div>
        </div>
    @endif

    @if (session('success'))
        <div class="px-5 md:px-[max(5vw,calc((100vw-1350px)/2))]">
            <div class="auth-alert auth-alert-ok flex items-center gap-2"><x-line-icon name="check" :size="16" /> {{ session('success') }}</div>
        </div>
    @endif

    <section class="dv-section !pt-6">
        <div class="grid gap-8 lg:grid-cols-[17rem_1fr]">
            {{-- ──────────────────────────  Step rail  ───────────────────────── --}}
            <aside class="lg:sticky lg:top-28 lg:self-start" data-no-reveal>
                <nav class="-mx-5 flex gap-2 overflow-x-auto px-5 pb-2 no-scrollbar lg:mx-0 lg:flex-col lg:gap-1 lg:px-0" aria-label="Hotel setup steps">
                    @foreach ($steps as $number => $item)
                        @php $reachable = $managing || $number <= $step; @endphp
                        <button
                            type="button"
                            wire:click="goTo({{ $number }})"
                            @disabled(! $reachable)
                            @class([
                                'flex flex-none items-center gap-3 rounded-[0.9rem] px-3 py-2.5 text-left transition-colors lg:w-full',
                                'bg-forest text-white' => $step === $number,
                                'hover:bg-cream' => $step !== $number && $reachable,
                                'cursor-not-allowed opacity-50' => ! $reachable,
                            ])
                            aria-current="{{ $step === $number ? 'step' : 'false' }}"
                        >
                            <span @class([
                                'grid h-8 w-8 flex-none place-items-center rounded-full text-[12px] font-extrabold',
                                'bg-white text-forest' => $step === $number,
                                'bg-clay text-white' => $step !== $number && $done($number),
                                'border border-line bg-white text-muted' => $step !== $number && ! $done($number),
                            ])>
                                @if ($step !== $number && $done($number) && ! $managing) <x-line-icon name="check" :size="14" /> @else {{ $number }} @endif
                            </span>
                            <span class="min-w-0">
                                <span class="block whitespace-nowrap text-[13px] font-extrabold">{{ $item['label'] }}</span>
                                <span @class(['hidden truncate text-[11px] lg:block', 'text-white/70' => $step === $number, 'text-muted' => $step !== $number])>{{ $item['hint'] }}</span>
                            </span>
                        </button>
                    @endforeach
                </nav>

                <div class="mt-5 hidden rounded-[1rem] border border-line bg-white p-4 lg:block">
                    <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-muted">At a glance</p>
                    <p class="mt-2 font-dm-serif text-[1.25rem] leading-tight text-forest">{{ $name ?: 'Your hotel' }}</p>
                    <p class="mt-0.5 text-[13px] tracking-[0.15em] text-[#C49A5C]">{{ str_repeat('★', $stars) }}<span class="text-line">{{ str_repeat('★', 5 - $stars) }}</span></p>
                    <dl class="mt-3 grid grid-cols-3 gap-2 border-t border-line pt-3 text-center">
                        <div><dt class="text-[9.5px] font-extrabold uppercase tracking-[0.08em] text-muted">Types</dt><dd class="text-[14px] font-extrabold text-ink">{{ $totals['types'] }}</dd></div>
                        <div><dt class="text-[9.5px] font-extrabold uppercase tracking-[0.08em] text-muted">Rooms</dt><dd class="text-[14px] font-extrabold text-ink">{{ $totals['rooms'] }}</dd></div>
                        <div><dt class="text-[9.5px] font-extrabold uppercase tracking-[0.08em] text-muted">From</dt><dd class="text-[14px] font-extrabold text-ink">{{ $totals['from'] ? number_format($totals['from'] / 1000, 1).'k' : '—' }}</dd></div>
                    </dl>
                </div>
            </aside>

            {{-- ──────────────────────────  Step panel  ──────────────────────── --}}
            <div class="min-w-0">
                <div class="rounded-[1.4rem] border border-line bg-white p-5 shadow-[0_20px_60px_rgba(20,36,28,0.06)] sm:p-8" wire:key="step-{{ $step }}">
                    <p class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-clay">Step {{ $step }} of {{ count($steps) }}</p>
                    <h2 class="mt-1 font-dm-serif text-[clamp(1.7rem,3vw,2.3rem)] leading-tight text-forest">{{ $steps[$step]['label'] }}</h2>

                    {{-- ── 1. The property ── --}}
                    @if ($step === 1)
                        <div class="mt-7 space-y-6">
                            <label class="block">
                                <span class="auth-label">Hotel name</span>
                                <input type="text" wire:model.live.debounce.500ms="name" placeholder="The Coral Tide" @class(['auth-input !h-14 !text-[18px] font-dm-serif', 'auth-input-error' => $errors->has('name')])>
                                @error('name') <span class="auth-error">{{ $message }}</span> @enderror
                                <span class="mt-1.5 block text-[11px] text-muted">Web address: holidayhub/hotel/<b class="text-ink">{{ $slug ?: 'your-hotel' }}</b></span>
                                @error('slug') <span class="auth-error">{{ $message }}</span> @enderror
                            </label>

                            <div>
                                <span class="auth-label">Star rating</span>
                                <div class="mt-1 flex flex-wrap gap-2" role="radiogroup" aria-label="Star rating">
                                    @foreach (range(1, 5) as $n)
                                        <button
                                            type="button"
                                            wire:click="$set('stars', {{ $n }})"
                                            role="radio"
                                            aria-checked="{{ $stars === $n ? 'true' : 'false' }}"
                                            @class([
                                                'flex items-center gap-1.5 rounded-full border px-4 py-2.5 text-[12px] font-extrabold transition-colors',
                                                'border-forest bg-forest text-white' => $stars === $n,
                                                'border-line bg-white text-ink hover:border-forest' => $stars !== $n,
                                            ])
                                        >
                                            <span class="tracking-[0.1em] {{ $stars === $n ? 'text-[#F3E4C4]' : 'text-[#C49A5C]' }}">{{ str_repeat('★', $n) }}</span>
                                            {{ $n }} {{ Str::plural('star', $n) }}
                                        </button>
                                    @endforeach
                                </div>
                                <span class="mt-1.5 block text-[11px] text-muted">Use your official classification. Guests filter and compare on it.</span>
                            </div>

                            <label class="block" x-data="{ count: @js(mb_strlen($description)) }">
                                <span class="auth-label">Describe your hotel</span>
                                <textarea wire:model.blur="description" x-on:input="count = $event.target.value.length" rows="7" placeholder="What makes a stay here unforgettable: the setting, the service, the food, the little rituals…" @class(['auth-input !h-auto py-3 leading-relaxed', 'auth-input-error' => $errors->has('description')])></textarea>
                                <span class="mt-1.5 flex justify-between text-[11px] text-muted"><span>Write for guests, not search engines.</span><span x-text="count + ' / 5000'"></span></span>
                                @error('description') <span class="auth-error">{{ $message }}</span> @enderror
                            </label>
                        </div>
                    @endif

                    {{-- ── 2. Location ── --}}
                    @if ($step === 2)
                        @php
                            $picked = $this->selectedDestination;
                            $results = $this->destinationResults;
                            $typed = trim($destinationSearch);
                            $exact = $typed !== '' && $results->contains(fn ($d) => Str::lower($d->name) === Str::lower($typed));
                        @endphp
                        <div
                            class="mt-7 space-y-7"
                            x-data="locationPicker({
                                key: @js($maps['key']),
                                mapId: @js($maps['id']),
                                lat: @js(filled($latitude) ? (float) $latitude : null),
                                lng: @js(filled($longitude) ? (float) $longitude : null),
                                country: @js($country_code),
                                countryName: @js($country),
                            })"
                        >
                            {{-- Country --}}
                            <label class="block">
                                <span class="auth-label">Country</span>
                                <select x-ref="country" wire:model.live="country_code" @class(['auth-input', 'auth-input-error' => $errors->has('country_code')])>
                                    <option value="">Choose a country…</option>
                                    @foreach ($countries as $code => $countryName)
                                        <option value="{{ $code }}">{{ $countryName }}</option>
                                    @endforeach
                                </select>
                                @error('country_code') <span class="auth-error">{{ $message }}</span> @enderror
                            </label>

                            {{-- Destination: search first, add only if it's missing --}}
                            <div>
                                <span class="auth-label">Destination</span>
                                <span class="-mt-1 mb-2 block text-[11.5px] text-muted">The place guests search for: a town, an island, a park. Search first; add it only if it isn’t listed.</span>

                                @if ($picked)
                                    <div class="flex items-center gap-3 rounded-[0.9rem] border border-forest bg-[#E7EEE8] px-4 py-3">
                                        <span class="grid h-10 w-10 flex-none place-items-center rounded-full bg-forest text-white"><x-line-icon name="map" :size="17" /></span>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-[14px] font-extrabold text-forest">{{ $picked->name }}</p>
                                            <p class="truncate text-[11.5px] text-muted">{{ collect([$picked->city !== $picked->name ? $picked->city : null, $picked->country])->filter()->implode(' · ') }}</p>
                                        </div>
                                        <button type="button" wire:click="clearDestination" class="text-[12px] font-extrabold text-clay hover:underline">Change</button>
                                    </div>
                                @else
                                    <div class="relative">
                                        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-muted"><x-line-icon name="search" :size="16" /></span>
                                        <input
                                            type="search"
                                            wire:model.live.debounce.300ms="destinationSearch"
                                            placeholder="{{ $country ? 'Search destinations in '.$country.'…' : 'Choose a country, then search…' }}"
                                            @class(['auth-input !pl-11', 'auth-input-error' => $errors->has('destination_id') || $errors->has('destinationSearch')])
                                            autocomplete="off"
                                        >
                                    </div>

                                    @if ($country_code)
                                        <div class="mt-2 overflow-hidden rounded-[0.9rem] border border-line bg-white" wire:loading.class="opacity-60" wire:target="destinationSearch">
                                            @forelse ($results as $result)
                                                <button type="button" wire:click="selectDestination({{ $result->id }})" wire:key="dest-{{ $result->id }}" class="flex w-full items-center gap-3 border-b border-line px-4 py-2.5 text-left transition-colors last:border-0 hover:bg-cream">
                                                    <x-line-icon name="map" :size="15" />
                                                    <span class="min-w-0 flex-1">
                                                        <span class="block truncate text-[13px] font-extrabold text-ink">{{ $result->name }}</span>
                                                        <span class="block truncate text-[11px] text-muted">{{ collect([$result->city !== $result->name ? $result->city : null, $result->country])->filter()->implode(' · ') }}</span>
                                                    </span>
                                                    <span class="text-[11px] font-extrabold text-clay">Select</span>
                                                </button>
                                            @empty
                                                <p class="px-4 py-3 text-[12px] text-muted">{{ $typed ? 'No destination called “'.$typed.'” in '.$country.' yet.' : 'No destinations in '.$country.' yet. Type one to add it.' }}</p>
                                            @endforelse

                                            @if (mb_strlen($typed) >= 2 && ! $exact)
                                                <button type="button" wire:click="createDestination" wire:loading.attr="disabled" wire:target="createDestination" class="flex w-full items-center gap-3 border-t border-dashed border-line bg-[#FBE7DF]/50 px-4 py-3 text-left transition-colors hover:bg-[#FBE7DF]">
                                                    <span class="grid h-7 w-7 flex-none place-items-center rounded-full bg-clay text-[15px] font-extrabold text-white">+</span>
                                                    <span class="min-w-0 flex-1 text-[13px] text-ink">Add <b>“{{ Str::title($typed) }}”</b> as a new destination in {{ $country }}</span>
                                                </button>
                                            @endif
                                        </div>
                                    @endif
                                @endif

                                @if ($destinationNotice)
                                    <p class="mt-2 text-[12px] font-semibold text-forest">{{ $destinationNotice }}</p>
                                @endif
                                @error('destination_id') <span class="auth-error">{{ $message }}</span> @enderror
                                @error('destinationSearch') <span class="auth-error">{{ $message }}</span> @enderror
                            </div>

                            {{-- Map --}}
                            <div>
                                <div class="flex flex-wrap items-end justify-between gap-2">
                                    <div>
                                        <span class="auth-label !mb-0">Pin your entrance</span>
                                        <span class="block text-[11.5px] text-muted">Search for the hotel or street, or click the map, then drag the pin onto your front door.</span>
                                    </div>
                                    <span class="rounded-full bg-cream px-3 py-1.5 text-[11px] font-bold text-forest" x-show="lat !== null" x-cloak>
                                        <span x-text="lat?.toFixed(5)"></span>, <span x-text="lng?.toFixed(5)"></span>
                                    </span>
                                </div>

                                <div class="relative mt-3 overflow-hidden rounded-[1.1rem] border border-line">
                                    <div wire:ignore x-ref="search" x-show="status === 'ready'" class="border-b border-line bg-white p-2 [&_gmp-place-autocomplete]:w-full"></div>
                                    <div wire:ignore x-ref="map" class="h-[22rem] w-full bg-cream sm:h-[26rem]"></div>

                                    <div x-show="status !== 'ready'" class="absolute inset-0 grid place-items-center bg-cream/95 p-6 text-center">
                                        <div x-show="status === 'loading'" class="text-[13px] font-bold text-muted">Loading the map…</div>
                                        <div x-show="status === 'nokey'" x-cloak class="max-w-sm">
                                            <span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-white text-clay"><x-line-icon name="map" :size="22" /></span>
                                            <p class="mt-3 font-dm-serif text-[1.3rem] text-forest">The map isn’t switched on yet</p>
                                            <p class="mt-1 text-[12px] leading-relaxed text-muted">Enter your coordinates below for now. (Site admins: set <code class="rounded bg-white px-1">GOOGLE_MAPS_API_KEY</code>.)</p>
                                        </div>
                                        <div x-show="status === 'error'" x-cloak class="max-w-sm">
                                            <p class="font-dm-serif text-[1.3rem] text-forest">The map couldn’t load</p>
                                            <p class="mt-1 text-[12px] leading-relaxed text-muted">Enter your coordinates below instead; you can come back and pin it later.</p>
                                        </div>
                                    </div>
                                </div>
                                @error('latitude') <span class="auth-error">{{ $message }}</span> @enderror

                                {{-- Alpine owns the open state: a <details> "open" attribute is
                                     reset by every Livewire re-render, closing it mid-typing. --}}
                                <div class="mt-3">
                                    <button type="button" x-on:click="manual = ! manual" x-show="status === 'ready'" class="text-[12px] font-extrabold text-clay" :aria-expanded="manual || status !== 'ready'">
                                        <span x-text="manual ? '▾' : '▸'"></span> Enter coordinates manually
                                    </button>
                                    <p x-show="status !== 'ready'" x-cloak class="text-[12px] font-extrabold text-clay">Enter coordinates manually</p>
                                    <div x-show="manual || status !== 'ready'" x-cloak class="mt-3 grid gap-3 sm:grid-cols-2">
                                        <label class="block">
                                            <span class="auth-label">Latitude</span>
                                            <input type="number" step="any" inputmode="decimal" wire:model.blur="latitude" x-on:input="lat = $event.target.value === '' ? null : +$event.target.value" x-on:change="moveTo()" placeholder="-4.31670" @class(['auth-input', 'auth-input-error' => $errors->has('latitude')])>
                                        </label>
                                        <label class="block">
                                            <span class="auth-label">Longitude</span>
                                            <input type="number" step="any" inputmode="decimal" wire:model.blur="longitude" x-on:input="lng = $event.target.value === '' ? null : +$event.target.value" x-on:change="moveTo()" placeholder="39.58330" @class(['auth-input', 'auth-input-error' => $errors->has('longitude')])>
                                        </label>
                                        <p class="text-[11px] text-muted sm:col-span-2">Decimal degrees, e.g. −4.31670 and 39.58330. South and west are negative.</p>
                                    </div>
                                    @error('longitude') <span class="auth-error">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            {{-- Address --}}
                            <div class="grid gap-5 sm:grid-cols-2">
                                <label class="block sm:col-span-2">
                                    <span class="auth-label">Street address</span>
                                    <input type="text" x-ref="address" wire:model.blur="address" x-on:input="addressFromMap = false" placeholder="Diani Beach Road" @class(['auth-input', 'auth-input-error' => $errors->has('address')])>
                                    @error('address') <span class="auth-error">{{ $message }}</span> @enderror
                                </label>
                                <label class="block">
                                    <span class="auth-label">Town or city</span>
                                    <input type="text" x-ref="city" wire:model.blur="city" placeholder="Diani" @class(['auth-input', 'auth-input-error' => $errors->has('city')])>
                                    @error('city') <span class="auth-error">{{ $message }}</span> @enderror
                                </label>
                                <div class="block">
                                    <span class="auth-label">Country</span>
                                    <p class="auth-input flex items-center bg-cream text-muted">{{ $country ?: 'Chosen above' }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- ── 3. Photos & film ── --}}
                    @if ($step === 3)
                        <div class="mt-7 space-y-8">
                            <p class="rounded-[0.9rem] bg-cream px-4 py-3 text-[12.5px] leading-relaxed text-muted">
                                <b class="text-forest">HolidayHub leads with film.</b> Guests discover stays as reels, so a short cover film (15–60 seconds, shot in portrait or landscape) does more than any photo. Uploads save as soon as they finish.
                            </p>

                            <div class="grid gap-5 lg:grid-cols-[1.2fr_1fr]">
                                {{-- Cover film --}}
                                <div>
                                    <span class="auth-label">Cover film <span class="font-medium normal-case tracking-normal text-muted">(recommended)</span></span>
                                    @php $filmUrl = $coverVideo ? ($coverVideo->isPreviewable() ? $coverVideo->temporaryUrl() : null) : Media::url($cover_video_path); @endphp
                                    <div class="relative mt-1 aspect-video overflow-hidden rounded-[1.1rem] border-2 border-dashed border-line bg-[#0C1511]">
                                        @if ($filmUrl)
                                            <video src="{{ $filmUrl }}" @if ($cover_path) poster="{{ Media::url($cover_path) }}" @endif class="absolute inset-0 h-full w-full object-cover" muted loop playsinline autoplay controls wire:key="film-{{ md5($filmUrl) }}"></video>
                                            <button type="button" wire:click="removeCoverVideo" wire:confirm="Remove the cover film?" class="absolute right-3 top-3 z-10 rounded-full bg-black/55 px-3 py-1.5 text-[11px] font-extrabold text-white backdrop-blur-md hover:bg-clay">Remove film</button>
                                        @else
                                            <label class="absolute inset-0 flex cursor-pointer flex-col items-center justify-center gap-2 text-center text-white transition-colors hover:bg-white/5">
                                                <span class="grid h-14 w-14 place-items-center rounded-full border border-white/30 bg-white/10"><x-line-icon name="play" :size="20" filled /></span>
                                                <span class="text-[13px] font-extrabold">Upload your cover film</span>
                                                <span class="text-[11px] text-white/60">MP4, WebM or MOV · up to 200 MB</span>
                                                <input type="file" accept="video/mp4,video/webm,video/quicktime,.mov,.m4v" wire:model="coverVideo" class="sr-only">
                                            </label>
                                        @endif
                                    </div>
                                    <div wire:loading wire:target="coverVideo" class="mt-2 text-[12px] font-bold text-clay">Uploading your film… large files can take a minute.</div>
                                    @if ($filmUrl)
                                        <label class="mt-2 inline-flex cursor-pointer text-[12px] font-extrabold text-clay hover:underline">
                                            Replace film <input type="file" accept="video/mp4,video/webm,video/quicktime,.mov,.m4v" wire:model="coverVideo" class="sr-only">
                                        </label>
                                    @endif
                                    @error('coverVideo') <span class="auth-error">{{ $message }}</span> @enderror
                                </div>

                                {{-- Cover photo --}}
                                <div>
                                    <span class="auth-label">Cover photo</span>
                                    <label class="group relative mt-1 flex aspect-video cursor-pointer items-center justify-center overflow-hidden rounded-[1.1rem] border-2 border-dashed border-line bg-cream transition-colors hover:border-clay">
                                        @if ($cover && $cover->isPreviewable())
                                            <img src="{{ $cover->temporaryUrl() }}" alt="New cover photo" class="absolute inset-0 h-full w-full object-cover">
                                        @elseif ($cover_path)
                                            <img src="{{ Media::url($cover_path) }}" alt="Current cover photo" class="absolute inset-0 h-full w-full object-cover">
                                        @endif
                                        <span @class(['relative z-10 flex flex-col items-center gap-1.5 rounded-[0.9rem] px-4 py-2.5 text-center', 'bg-white/90 backdrop-blur' => $cover || $cover_path])>
                                            <x-line-icon name="grid" :size="20" />
                                            <span class="text-[12.5px] font-extrabold text-ink">{{ $cover || $cover_path ? 'Replace the cover photo' : 'Upload a cover photo' }}</span>
                                            <span class="text-[10.5px] text-muted">Shown on cards and before the film plays</span>
                                        </span>
                                        <input type="file" accept="image/*" wire:model="cover" class="sr-only">
                                    </label>
                                    <div wire:loading wire:target="cover" class="mt-2 text-[12px] font-bold text-clay">Uploading…</div>
                                    @error('cover') <span class="auth-error">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            {{-- Gallery: photos and films --}}
                            <div>
                                <div class="flex flex-wrap items-end justify-between gap-3">
                                    <span class="auth-label !mb-0">Gallery · photos &amp; films</span>
                                    @php $mediaCount = count($gallery_paths) + count($new_gallery); $filmCount = collect($gallery_paths)->filter(fn ($p) => Media::isVideo($p))->count(); @endphp
                                    <span class="text-[11px] text-muted">{{ $mediaCount }} items · {{ $filmCount }} {{ Str::plural('film', $filmCount) }} · show the pool, the restaurant, the spa, the view</span>
                                </div>
                                <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                                    @foreach ($gallery_paths as $i => $path)
                                        <div class="group relative aspect-[4/3] overflow-hidden rounded-[0.9rem] bg-[#0C1511]" wire:key="g-{{ $i }}-{{ md5($path) }}">
                                            @if (Media::isVideo($path))
                                                <video src="{{ Media::url($path) }}" class="h-full w-full object-cover" muted loop playsinline preload="metadata" x-on:mouseenter="$el.play()" x-on:mouseleave="$el.pause()"></video>
                                                <span class="pointer-events-none absolute left-2 top-2 inline-flex items-center gap-1 rounded-full bg-black/55 px-2 py-0.5 text-[9px] font-extrabold uppercase text-white"><x-line-icon name="play" :size="9" filled /> Film</span>
                                            @else
                                                <img src="{{ Media::url($path) }}" alt="" class="h-full w-full object-cover">
                                            @endif
                                            <button type="button" wire:click="removeGalleryPhoto({{ $i }})" class="absolute right-2 top-2 grid h-8 w-8 place-items-center rounded-full bg-black/50 text-white opacity-0 transition-opacity group-hover:opacity-100 focus:opacity-100" aria-label="Remove"><x-line-icon name="close" :size="14" /></button>
                                        </div>
                                    @endforeach
                                    @foreach ($new_gallery as $i => $upload)
                                        <div class="group relative aspect-[4/3] overflow-hidden rounded-[0.9rem] bg-[#0C1511] ring-2 ring-clay/40" wire:key="ng-{{ $i }}">
                                            @if (! $upload->isPreviewable())
                                                <span class="grid h-full place-items-center px-2 text-center text-[11px] font-bold text-white/80">{{ Str::limit($upload->getClientOriginalName(), 24) }}</span>
                                            @elseif (Media::isVideo($upload->getClientOriginalName()))
                                                <video src="{{ $upload->temporaryUrl() }}" class="h-full w-full object-cover" muted playsinline preload="metadata"></video>
                                            @else
                                                <img src="{{ $upload->temporaryUrl() }}" alt="" class="h-full w-full object-cover">
                                            @endif
                                            <span class="absolute left-2 top-2 rounded-full bg-clay px-2 py-0.5 text-[9px] font-extrabold uppercase text-white">New</span>
                                            <button type="button" wire:click="removeNewGalleryPhoto({{ $i }})" class="absolute right-2 top-2 grid h-8 w-8 place-items-center rounded-full bg-black/50 text-white" aria-label="Remove"><x-line-icon name="close" :size="14" /></button>
                                        </div>
                                    @endforeach
                                    <label class="flex aspect-[4/3] cursor-pointer flex-col items-center justify-center gap-1.5 rounded-[0.9rem] border-2 border-dashed border-line bg-cream text-center transition-colors hover:border-clay">
                                        <span class="text-[22px] leading-none text-clay">+</span>
                                        <span class="text-[11.5px] font-extrabold text-ink">Add photos or films</span>
                                        <input type="file" accept="image/*,video/mp4,video/webm,video/quicktime,.mov,.m4v" multiple wire:model="galleryUpload" class="sr-only">
                                    </label>
                                </div>
                                <div wire:loading wire:target="galleryUpload" class="mt-2 text-[12px] font-bold text-clay">Uploading… films can take a minute.</div>
                                @error('galleryUpload.*') <span class="auth-error">{{ $message }}</span> @enderror
                                @error('new_gallery.*') <span class="auth-error">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    @endif

                    {{-- ── 4. Facilities & rules ── --}}
                    @if ($step === 4)
                        <div class="mt-7 space-y-8">
                            <div>
                                <div class="flex items-end justify-between gap-3">
                                    <span class="auth-label !mb-0">On-site facilities</span>
                                    <span class="text-[11px] text-muted">{{ count($selectedAmenities) }} selected</span>
                                </div>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach ($propertyAmenities as $amenity)
                                        <label class="cursor-pointer" title="{{ $amenity->description }}" wire:key="pa-{{ $amenity->id }}">
                                            <input type="checkbox" value="{{ $amenity->id }}" wire:model.live="selectedAmenities" class="peer sr-only">
                                            <span class="inline-flex items-center gap-1.5 rounded-full border border-line bg-white px-3.5 py-2 text-[12px] font-bold text-muted transition-colors peer-checked:border-forest peer-checked:bg-forest peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-clay/40 hover:border-forest">
                                                {{ $amenity->name }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                @error('selectedAmenities') <span class="auth-error">{{ $message }}</span> @enderror
                            </div>

                            <div class="grid gap-5 border-t border-line pt-7 sm:grid-cols-2">
                                <label class="block">
                                    <span class="auth-label">Check-in from</span>
                                    <input type="time" wire:model="policies.check_in" @class(['auth-input', 'auth-input-error' => $errors->has('policies.check_in')])>
                                    @error('policies.check_in') <span class="auth-error">{{ $message }}</span> @enderror
                                </label>
                                <label class="block">
                                    <span class="auth-label">Check-out by</span>
                                    <input type="time" wire:model="policies.check_out" @class(['auth-input', 'auth-input-error' => $errors->has('policies.check_out')])>
                                    @error('policies.check_out') <span class="auth-error">{{ $message }}</span> @enderror
                                </label>

                                <div class="sm:col-span-2">
                                    <span class="auth-label">Cancellation policy</span>
                                    <div class="mt-1 grid gap-2 sm:grid-cols-4">
                                        @foreach (['flexible' => 'Flexible', 'moderate' => 'Moderate', 'strict' => 'Strict', 'custom' => 'Custom'] as $key => $label)
                                            <label class="cursor-pointer">
                                                <input type="radio" value="{{ $key }}" wire:model.live="policies.cancellation_type" class="peer sr-only">
                                                <span class="block rounded-[0.8rem] border border-line bg-white px-3 py-2.5 text-center text-[12.5px] font-extrabold text-ink transition-colors peer-checked:border-clay peer-checked:bg-[#FBE7DF] peer-checked:text-clay-dark hover:border-forest">{{ $label }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    <textarea wire:model="policies.cancellation" rows="2" @readonly($policies['cancellation_type'] !== 'custom') @class(['auth-input mt-2 !h-auto py-3', 'bg-cream text-muted' => $policies['cancellation_type'] !== 'custom', 'auth-input-error' => $errors->has('policies.cancellation')])></textarea>
                                    @error('policies.cancellation') <span class="auth-error">{{ $message }}</span> @enderror
                                </div>

                                <label class="block sm:col-span-2">
                                    <span class="auth-label">Children</span>
                                    <input type="text" wire:model="policies.children" placeholder="Children of all ages are welcome. Cots on request." class="auth-input">
                                </label>
                                <label class="block">
                                    <span class="auth-label">Pets</span>
                                    <select wire:model="policies.pets" class="auth-input">
                                        <option value="no">Not allowed</option>
                                        <option value="request">On request</option>
                                        <option value="yes">Welcome</option>
                                    </select>
                                </label>
                                <label class="block">
                                    <span class="auth-label">Smoking</span>
                                    <select wire:model="policies.smoking" class="auth-input">
                                        <option value="no">Non-smoking property</option>
                                        <option value="areas">Designated areas only</option>
                                        <option value="yes">Allowed</option>
                                    </select>
                                </label>
                                <label class="block sm:col-span-2">
                                    <span class="auth-label">Deposit or incidentals <span class="font-medium normal-case tracking-normal text-muted">(optional)</span></span>
                                    <input type="text" wire:model="policies.deposit" placeholder="A card pre-authorisation of {{ $symbol }} 10,000 is taken at check-in." class="auth-input">
                                </label>
                            </div>
                        </div>
                    @endif

                    {{-- ── 5. Rooms & suites ── --}}
                    @if ($step === 5)
                        <div class="mt-3">
                            <p class="text-[13px] leading-relaxed text-muted">Add each kind of room you sell: from your standard double to the presidential suite. Guests compare them side by side.</p>

                            @error('roomTypes') <p class="auth-error">{{ $message }}</p> @enderror

                            <div class="mt-6 space-y-5">
                                @foreach ($roomTypes as $i => $type)
                                    @php
                                        $bedLine = collect($type['beds'])->map(fn ($bed) => ($bed['count'] ?? 1).' × '.Str::lower($bedTypes[$bed['type']] ?? 'bed'))->implode(', ');
                                        $hasErrors = collect($errors->keys())->contains(fn ($key) => Str::startsWith($key, "roomTypes.$i."));
                                    @endphp
                                    <article
                                        wire:key="room-type-{{ $type['key'] }}"
                                        x-data="{ open: @js(! $type['id'] || $hasErrors) }"
                                        @class(['overflow-hidden rounded-[1.2rem] border bg-surface', 'border-clay' => $hasErrors, 'border-line' => ! $hasErrors])
                                    >
                                        {{-- Summary row --}}
                                        <div class="flex flex-wrap items-center gap-3 p-4 sm:p-5">
                                            <button type="button" x-on:click="open = ! open" class="flex min-w-0 flex-1 items-center gap-3 text-left" :aria-expanded="open">
                                                <span class="grid h-10 w-10 flex-none place-items-center rounded-full bg-forest font-dm-serif text-[1rem] text-white">{{ $i + 1 }}</span>
                                                <span class="min-w-0">
                                                    <span class="block truncate font-dm-serif text-[1.3rem] leading-tight text-ink">{{ $type['name'] ?: 'New room type' }}</span>
                                                    <span class="block truncate text-[11.5px] text-muted">
                                                        {{ $type['size_sqm'] ? $type['size_sqm'].' m² · ' : '' }}{{ $views[$type['view']] ?? '' }} · {{ $bedLine }} · sleeps {{ (int) $type['max_adults'] + (int) $type['max_children'] }}
                                                        · {{ (int) $type['room_count'] }} {{ Str::plural('room', (int) $type['room_count']) }}
                                                    </span>
                                                </span>
                                            </button>
                                            <span class="font-dm-serif text-[1.2rem] text-forest">{{ $type['price_per_night'] ? $symbol.' '.number_format((float) $type['price_per_night']) : '—' }}<span class="font-sans text-[11px] text-muted"> / night</span></span>
                                            <div class="flex items-center gap-1.5">
                                                <button type="button" wire:click="duplicateRoomType({{ $i }})" class="grid h-9 w-9 place-items-center rounded-full border border-line bg-white text-muted transition-colors hover:border-forest hover:text-forest" title="Duplicate" aria-label="Duplicate {{ $type['name'] ?: 'room type' }}"><x-line-icon name="grid" :size="14" /></button>
                                                @if (count($roomTypes) > 1)
                                                    <button type="button" wire:click="removeRoomType({{ $i }})" wire:confirm="Remove {{ $type['name'] ?: 'this room type' }} and its rooms?" class="grid h-9 w-9 place-items-center rounded-full border border-line bg-white text-muted transition-colors hover:border-clay hover:text-clay" title="Remove" aria-label="Remove {{ $type['name'] ?: 'room type' }}"><x-line-icon name="close" :size="14" /></button>
                                                @endif
                                                <button type="button" x-on:click="open = ! open" class="grid h-9 w-9 place-items-center rounded-full border border-line bg-white text-forest" aria-label="Show details"><span class="transition-transform" :class="open && 'rotate-180'"><x-line-icon name="chevron-down" :size="15" /></span></button>
                                            </div>
                                        </div>

                                        <div x-show="open" x-collapse class="border-t border-line bg-white">
                                            <div class="space-y-8 p-4 sm:p-6">
                                                {{-- Basics --}}
                                                <div class="grid gap-4 sm:grid-cols-6">
                                                    <label class="block sm:col-span-3">
                                                        <span class="auth-label">Room type name</span>
                                                        <input type="text" wire:model.blur="roomTypes.{{ $i }}.name" placeholder="Deluxe Ocean King" @class(['auth-input', 'auth-input-error' => $errors->has("roomTypes.$i.name")])>
                                                        @error("roomTypes.$i.name") <span class="auth-error">{{ $message }}</span> @enderror
                                                    </label>
                                                    <div class="block sm:col-span-3">
                                                        <span class="auth-label">Base rate per night</span>
                                                        {{-- Currency sits with the price; it's one setting for the whole hotel. --}}
                                                        <div @class(['flex overflow-hidden rounded-[0.8rem] border bg-surface transition-colors focus-within:border-forest', 'border-clay' => $errors->has("roomTypes.$i.price_per_night") || $errors->has('currency'), 'border-line' => ! ($errors->has("roomTypes.$i.price_per_night") || $errors->has('currency'))])>
                                                            <select wire:model.live="currency" aria-label="Currency" class="w-[6.5rem] flex-none border-0 border-r border-line bg-cream py-0 pl-3 pr-7 text-[13px] font-extrabold text-forest focus:ring-0">
                                                                @foreach ($currencies as $code => $label)
                                                                    <option value="{{ $code }}" title="{{ $label }}">{{ $code }}</option>
                                                                @endforeach
                                                            </select>
                                                            <input type="number" min="1" step="any" inputmode="decimal" wire:model.blur="roomTypes.{{ $i }}.price_per_night" placeholder="{{ in_array($currency, ['USD', 'EUR', 'GBP', 'CHF', 'AUD', 'CAD']) ? '180' : '28000' }}" aria-label="Base rate per night" class="h-[3.1rem] min-w-0 flex-1 border-0 bg-transparent px-4 text-[13px] font-semibold text-ink focus:ring-0">
                                                        </div>
                                                        <span class="mt-1.5 block text-[11px] text-muted">
                                                            {{ $currencies[$currency] ?? $currency }}@if (! $currencyChosen && $country) · suggested for {{ $country }}@endif. One currency for all your rooms: changing it here changes it everywhere.
                                                        </span>
                                                        @error('currency') <span class="auth-error">{{ $message }}</span> @enderror
                                                        @error("roomTypes.$i.price_per_night") <span class="auth-error">{{ $message }}</span> @enderror
                                                    </div>
                                                    <label class="block sm:col-span-2">
                                                        <span class="auth-label">Size · m²</span>
                                                        <input type="number" min="5" wire:model.blur="roomTypes.{{ $i }}.size_sqm" placeholder="42" @class(['auth-input', 'auth-input-error' => $errors->has("roomTypes.$i.size_sqm")])>
                                                        @error("roomTypes.$i.size_sqm") <span class="auth-error">{{ $message }}</span> @enderror
                                                    </label>
                                                    <label class="block sm:col-span-4">
                                                        <span class="auth-label">View</span>
                                                        <select wire:model.live="roomTypes.{{ $i }}.view" class="auth-input">
                                                            @foreach ($views as $key => $label)
                                                                <option value="{{ $key }}">{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                    </label>
                                                    <label class="block sm:col-span-6">
                                                        <span class="auth-label">Description</span>
                                                        <textarea rows="3" wire:model.blur="roomTypes.{{ $i }}.description" placeholder="Floor-to-ceiling windows over the Indian Ocean, a deep soaking tub and a private terrace." class="auth-input !h-auto py-3"></textarea>
                                                    </label>
                                                </div>

                                                {{-- Beds & occupancy --}}
                                                <div class="grid gap-6 border-t border-line pt-6 lg:grid-cols-[1.3fr_1fr]">
                                                    <div>
                                                        <span class="auth-label">Bed set-up</span>
                                                        <div class="space-y-2">
                                                            @foreach ($type['beds'] as $b => $bed)
                                                                <div class="flex items-center gap-2" wire:key="bed-{{ $type['key'] }}-{{ $b }}">
                                                                    <select wire:model.live="roomTypes.{{ $i }}.beds.{{ $b }}.type" class="auth-input flex-1">
                                                                        @foreach ($bedTypes as $key => $label)
                                                                            <option value="{{ $key }}">{{ $label }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                    <input type="number" min="1" max="10" wire:model.live.debounce.400ms="roomTypes.{{ $i }}.beds.{{ $b }}.count" class="auth-input !w-20 text-center" aria-label="How many">
                                                                    @if (count($type['beds']) > 1)
                                                                        <button type="button" wire:click="removeBed({{ $i }}, {{ $b }})" class="grid h-10 w-10 flex-none place-items-center rounded-full border border-line text-muted hover:border-clay hover:text-clay" aria-label="Remove bed"><x-line-icon name="close" :size="13" /></button>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                        <button type="button" wire:click="addBed({{ $i }})" class="mt-2 text-[12px] font-extrabold text-clay hover:underline">+ Add a bed</button>
                                                        @error("roomTypes.$i.beds") <span class="auth-error">{{ $message }}</span> @enderror
                                                    </div>
                                                    <div class="grid grid-cols-2 gap-3">
                                                        <label class="block">
                                                            <span class="auth-label">Max adults</span>
                                                            <input type="number" min="1" max="20" wire:model.live.debounce.400ms="roomTypes.{{ $i }}.max_adults" @class(['auth-input', 'auth-input-error' => $errors->has("roomTypes.$i.max_adults")])>
                                                        </label>
                                                        <label class="block">
                                                            <span class="auth-label">Max children</span>
                                                            <input type="number" min="0" max="10" wire:model.live.debounce.400ms="roomTypes.{{ $i }}.max_children" @class(['auth-input', 'auth-input-error' => $errors->has("roomTypes.$i.max_children")])>
                                                        </label>
                                                        <p class="col-span-2 text-[11.5px] text-muted">Sleeps <b class="text-ink">{{ (int) $type['max_adults'] + (int) $type['max_children'] }}</b> in total. This is the most you’ll accept per booking.</p>
                                                    </div>
                                                </div>

                                                {{-- Inclusions --}}
                                                <div class="border-t border-line pt-6">
                                                    <span class="auth-label">The rate includes</span>
                                                    <div class="grid gap-2 sm:grid-cols-2">
                                                        @foreach (['breakfast_included' => ['Breakfast included', 'Guests see “Breakfast included” on the room.'], 'refundable' => ['Free cancellation', 'Follows your cancellation policy. Off means non-refundable.'], 'accessible' => ['Accessible room', 'Step-free access, adapted bathroom.'], 'smoking_allowed' => ['Smoking allowed', 'Only if smoking is permitted in this room.']] as $field => [$label, $help])
                                                            <label class="flex cursor-pointer items-start gap-3 rounded-[0.9rem] border border-line p-3.5 transition-colors hover:border-forest">
                                                                <input type="checkbox" wire:model.live="roomTypes.{{ $i }}.{{ $field }}" class="auth-check mt-0.5">
                                                                <span><span class="block text-[13px] font-extrabold text-ink">{{ $label }}</span><span class="block text-[11px] text-muted">{{ $help }}</span></span>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                </div>

                                                {{-- Room amenities --}}
                                                <div class="border-t border-line pt-6">
                                                    <span class="auth-label">In the room</span>
                                                    <div class="flex flex-wrap gap-2">
                                                        @foreach ($roomAmenities as $amenity)
                                                            <label class="cursor-pointer" wire:key="ra-{{ $type['key'] }}-{{ $amenity->id }}">
                                                                <input type="checkbox" value="{{ $amenity->id }}" wire:model.live="roomTypes.{{ $i }}.amenities" class="peer sr-only">
                                                                <span class="inline-flex rounded-full border border-line bg-white px-3 py-1.5 text-[11.5px] font-bold text-muted transition-colors peer-checked:border-clay peer-checked:bg-[#FBE7DF] peer-checked:text-clay-dark hover:border-forest">{{ $amenity->name }}</span>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                </div>

                                                {{-- Photos & films --}}
                                                <div class="border-t border-line pt-6">
                                                    <span class="auth-label">Room photos &amp; films</span>
                                                    <span class="-mt-1 mb-2 block text-[11.5px] text-muted">A short walk-through film of the room plays on its card for guests.</span>
                                                    <div class="grid grid-cols-3 gap-2 sm:grid-cols-5">
                                                        @foreach ($type['gallery_paths'] as $p => $path)
                                                            <div class="group relative aspect-square overflow-hidden rounded-[0.7rem] bg-[#0C1511]" wire:key="rp-{{ $type['key'] }}-{{ $p }}">
                                                                @if (Media::isVideo($path))
                                                                    <video src="{{ Media::url($path) }}" class="h-full w-full object-cover" muted loop playsinline preload="metadata" x-on:mouseenter="$el.play()" x-on:mouseleave="$el.pause()"></video>
                                                                    <span class="pointer-events-none absolute left-1.5 top-1.5 rounded-full bg-black/55 px-1.5 py-0.5 text-[8.5px] font-extrabold uppercase text-white">Film</span>
                                                                @else
                                                                    <img src="{{ Media::url($path) }}" alt="" class="h-full w-full object-cover">
                                                                @endif
                                                                <button type="button" wire:click="removeRoomImage({{ $i }}, {{ $p }})" class="absolute right-1.5 top-1.5 grid h-7 w-7 place-items-center rounded-full bg-black/50 text-white opacity-0 transition-opacity group-hover:opacity-100 focus:opacity-100" aria-label="Remove"><x-line-icon name="close" :size="12" /></button>
                                                            </div>
                                                        @endforeach
                                                        @foreach ($type['new_images'] as $upload)
                                                            @if (is_object($upload) && method_exists($upload, 'temporaryUrl'))
                                                                <div class="relative aspect-square overflow-hidden rounded-[0.7rem] bg-[#0C1511] ring-2 ring-clay/40">
                                                                    @if (! $upload->isPreviewable())
                                                                        <span class="grid h-full place-items-center px-1 text-center text-[10px] font-bold text-white/80">{{ Str::limit($upload->getClientOriginalName(), 16) }}</span>
                                                                    @elseif (Media::isVideo($upload->getClientOriginalName()))
                                                                        <video src="{{ $upload->temporaryUrl() }}" class="h-full w-full object-cover" muted playsinline preload="metadata"></video>
                                                                    @else
                                                                        <img src="{{ $upload->temporaryUrl() }}" alt="" class="h-full w-full object-cover">
                                                                    @endif
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                        <label class="flex aspect-square cursor-pointer flex-col items-center justify-center rounded-[0.7rem] border-2 border-dashed border-line bg-cream text-center hover:border-clay">
                                                            <span class="text-[20px] leading-none text-clay">+</span>
                                                            <span class="text-[10.5px] font-extrabold text-ink">Photos or films</span>
                                                            <input type="file" accept="image/*,video/mp4,video/webm,video/quicktime,.mov,.m4v" multiple wire:model="roomTypes.{{ $i }}.new_images" class="sr-only">
                                                        </label>
                                                    </div>
                                                    <div wire:loading wire:target="roomTypes.{{ $i }}.new_images" class="mt-2 text-[12px] font-bold text-clay">Uploading…</div>
                                                    @error("roomTypes.$i.new_images.*") <span class="auth-error">{{ $message }}</span> @enderror
                                                </div>

                                                {{-- Inventory --}}
                                                <div class="border-t border-line pt-6">
                                                    <div class="flex flex-wrap items-end justify-between gap-3">
                                                        <div>
                                                            <span class="auth-label !mb-0">Rooms of this type</span>
                                                            <span class="block text-[11.5px] text-muted">Each room is a unit of inventory. Guests can book as many as you have available.</span>
                                                        </div>
                                                    </div>
                                                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                                        <label class="block">
                                                            <span class="auth-label">How many rooms</span>
                                                            <input type="number" min="1" max="500" wire:model.live.debounce.500ms="roomTypes.{{ $i }}.room_count" @class(['auth-input', 'auth-input-error' => $errors->has("roomTypes.$i.room_count")])>
                                                            @error("roomTypes.$i.room_count") <span class="auth-error">{{ $message }}</span> @enderror
                                                        </label>
                                                        <label class="block">
                                                            <span class="auth-label">New rooms numbered from</span>
                                                            <input type="number" min="1" wire:model.blur="roomTypes.{{ $i }}.first_room" @class(['auth-input', 'auth-input-error' => $errors->has("roomTypes.$i.first_room")])>
                                                            @error("roomTypes.$i.first_room") <span class="auth-error">{{ $message }}</span> @enderror
                                                        </label>
                                                    </div>

                                                    @if (count($type['units']))
                                                        <div class="mt-4">
                                                            <p class="text-[11px] font-extrabold uppercase tracking-[0.1em] text-muted">Existing rooms · set a room to maintenance to stop it being booked</p>
                                                            <div class="mt-2 grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                                                                @foreach ($type['units'] as $u => $unit)
                                                                    <div @class(['flex items-center gap-2 rounded-[0.8rem] border p-2', 'border-line bg-white' => $unit['status'] === 'available', 'border-[#E9D3A6] bg-[#F6EEDD]' => $unit['status'] !== 'available']) wire:key="unit-{{ $unit['id'] }}">
                                                                        <span class="pl-1 text-[11px] font-extrabold text-muted">Nº</span>
                                                                        <input type="number" min="1" wire:model.blur="roomTypes.{{ $i }}.units.{{ $u }}.room_number" class="w-16 rounded-[0.5rem] border border-line px-2 py-1.5 text-[13px] font-extrabold text-ink focus:border-forest focus:ring-0" aria-label="Room number">
                                                                        <select wire:model.live="roomTypes.{{ $i }}.units.{{ $u }}.status" class="min-w-0 flex-1 rounded-[0.5rem] border border-line py-1.5 pl-2 pr-7 text-[12px] font-bold text-ink focus:border-forest focus:ring-0" aria-label="Room status">
                                                                            <option value="available">Available</option>
                                                                            <option value="maintenance">Maintenance</option>
                                                                            <option value="out_of_service">Out of service</option>
                                                                        </select>
                                                                    </div>
                                                                    @error("roomTypes.$i.units.$u.room_number") <span class="auth-error sm:col-span-2 xl:col-span-3">{{ $message }}</span> @enderror
                                                                @endforeach
                                                            </div>
                                                            @if ((int) $type['room_count'] < count($type['units']))
                                                                <p class="mt-2 text-[11.5px] font-bold text-clay-dark">{{ count($type['units']) - (int) $type['room_count'] }} {{ Str::plural('room', count($type['units']) - (int) $type['room_count']) }} with the highest numbers will be removed when you save.</p>
                                                            @elseif ((int) $type['room_count'] > count($type['units']))
                                                                <p class="mt-2 text-[11.5px] font-bold text-forest">{{ (int) $type['room_count'] - count($type['units']) }} new {{ Str::plural('room', (int) $type['room_count'] - count($type['units'])) }} will be added from Nº {{ $type['first_room'] }}.</p>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </div>

                                                {{-- Seasonal rates --}}
                                                <div class="border-t border-line pt-6">
                                                    <span class="auth-label !mb-0">Seasonal rates</span>
                                                    <span class="block text-[11.5px] text-muted">Override the base rate for peak or quiet periods, e.g. Christmas or the migration season.</span>
                                                    <div class="mt-3 space-y-2">
                                                        @foreach ($type['rates'] as $r => $rate)
                                                            <div class="grid grid-cols-2 gap-2 rounded-[0.9rem] bg-cream p-3 sm:grid-cols-[1.4fr_1fr_1fr_1fr_auto]" wire:key="rate-{{ $type['key'] }}-{{ $r }}">
                                                                <input type="text" wire:model.blur="roomTypes.{{ $i }}.rates.{{ $r }}.label" placeholder="Festive season" class="auth-input col-span-2 sm:col-span-1">
                                                                <input type="date" wire:model.blur="roomTypes.{{ $i }}.rates.{{ $r }}.start" @class(['auth-input', 'auth-input-error' => $errors->has("roomTypes.$i.rates.$r.start")]) aria-label="Starts">
                                                                <input type="date" wire:model.blur="roomTypes.{{ $i }}.rates.{{ $r }}.end" @class(['auth-input', 'auth-input-error' => $errors->has("roomTypes.$i.rates.$r.end")]) aria-label="Ends">
                                                                <input type="number" min="1" step="any" wire:model.blur="roomTypes.{{ $i }}.rates.{{ $r }}.rate" placeholder="Rate in {{ $currency }}" @class(['auth-input', 'auth-input-error' => $errors->has("roomTypes.$i.rates.$r.rate")]) aria-label="Nightly rate">
                                                                <button type="button" wire:click="removeRate({{ $i }}, {{ $r }})" class="grid h-[3.1rem] w-10 place-items-center rounded-[0.8rem] border border-line bg-white text-muted hover:border-clay hover:text-clay" aria-label="Remove season"><x-line-icon name="close" :size="13" /></button>
                                                                @foreach (['start', 'end', 'rate'] as $f)
                                                                    @error("roomTypes.$i.rates.$r.$f") <span class="auth-error col-span-full">{{ $message }}</span> @enderror
                                                                @endforeach
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                    <button type="button" wire:click="addRate({{ $i }})" class="mt-2 text-[12px] font-extrabold text-clay hover:underline">+ Add a season</button>
                                                </div>
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>

                            <button type="button" wire:click="addRoomType" class="mt-5 flex w-full items-center justify-center gap-2 rounded-[1.2rem] border-2 border-dashed border-line py-5 text-[13px] font-extrabold text-forest transition-colors hover:border-clay hover:text-clay">
                                + Add another room type
                            </button>
                        </div>
                    @endif

                    {{-- ── 6. Review ── --}}
                    @if ($step === 6)
                        <div class="mt-7 space-y-4">
                            @foreach ([
                                1 => [$name ?: 'No name yet', str_repeat('★', $stars).' · '.Str::limit($description, 140)],
                                2 => [collect([$address, $city, $country])->filter()->implode(', ') ?: 'No address yet', ($this->selectedDestination?->name ?? 'No destination chosen').(filled($latitude) ? ' · pinned at '.round((float) $latitude, 4).', '.round((float) $longitude, 4) : ' · no map pin yet')],
                                3 => [collect([($coverVideo || $cover_video_path) ? 'Cover film' : null, ($cover || $cover_path) ? 'cover photo' : 'No cover photo yet'])->filter()->implode(' + '), (count($gallery_paths) + count($new_gallery)).' gallery items, '.collect($gallery_paths)->filter(fn ($p) => Media::isVideo($p))->count().' of them films'],
                                4 => [count($selectedAmenities).' facilities', 'Check-in '.$policies['check_in'].' · check-out '.$policies['check_out'].' · '.$policies['cancellation']],
                            ] as $s => [$headline, $detail])
                                <div class="flex items-start justify-between gap-4 rounded-[1rem] border border-line p-4">
                                    <div class="min-w-0">
                                        <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-clay">{{ $steps[$s]['label'] }}</p>
                                        <p class="mt-1 truncate text-[14px] font-extrabold text-ink">{{ $headline }}</p>
                                        <p class="mt-0.5 line-clamp-2 text-[12px] text-muted">{{ $detail }}</p>
                                    </div>
                                    <button type="button" wire:click="goTo({{ $s }})" class="flex-none text-[12px] font-extrabold text-clay hover:underline">Edit</button>
                                </div>
                            @endforeach

                            <div class="rounded-[1rem] border border-line p-4">
                                <div class="flex items-start justify-between gap-4">
                                    <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-clay">Rooms &amp; suites · {{ $totals['types'] }} types · {{ $totals['rooms'] }} rooms</p>
                                    <button type="button" wire:click="goTo(5)" class="flex-none text-[12px] font-extrabold text-clay hover:underline">Edit</button>
                                </div>
                                <div class="mt-3 divide-y divide-line">
                                    @foreach ($roomTypes as $type)
                                        <div class="flex flex-wrap items-center justify-between gap-2 py-2.5 text-[13px]">
                                            <span class="font-extrabold text-ink">{{ $type['name'] ?: 'Unnamed room type' }}</span>
                                            <span class="text-muted">{{ (int) $type['room_count'] }} rooms · sleeps {{ (int) $type['max_adults'] + (int) $type['max_children'] }}{{ $type['breakfast_included'] ? ' · breakfast' : '' }}{{ count($type['rates']) ? ' · '.count($type['rates']).' seasons' : '' }}</span>
                                            <span class="font-dm-serif text-[1.1rem] text-forest">{{ $type['price_per_night'] ? $symbol.' '.number_format((float) $type['price_per_night']) : '—' }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <p class="rounded-[0.9rem] bg-cream px-4 py-3 text-[12.5px] leading-relaxed text-muted">
                                @if ($managing)
                                    Saving updates your rooms and rates straight away. If you changed the property’s name, story, address, photos or house rules, the listing goes back to our team for a quick review first.
                                @else
                                    When you submit, our team checks the listing, usually within a day. You can keep editing rooms and rates while it’s in review.
                                @endif
                            </p>
                        </div>
                    @endif

                    @error('save') <div class="auth-alert auth-alert-error mt-6">{{ $message }}</div> @enderror

                    {{-- ── Navigation ── --}}
                    <div class="mt-8 flex flex-wrap items-center justify-between gap-3 border-t border-line pt-6">
                        <div class="flex items-center gap-3">
                        <button type="button" wire:click="previousStep" @class(['dv-btn dv-btn-ghost', 'invisible' => $step === 1])>
                            <span class="rotate-180"><x-line-icon name="arrow" :size="15" /></span> Back
                        </button>
                        @unless ($managing)
                            @if ($hotel)
                                <span class="text-[11.5px] font-semibold text-muted" wire:loading.remove><x-line-icon name="check" :size="12" /> Progress saved</span>
                                <span class="text-[11.5px] font-semibold text-clay" wire:loading>Saving…</span>
                            @endif
                        @endunless
                        </div>
                        <div class="flex flex-wrap gap-2.5">
                            @if ($managing && $step < 6)
                                <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save" class="dv-btn dv-btn-ghost">
                                    <span wire:loading.remove wire:target="save">Save changes</span><span wire:loading wire:target="save">Saving…</span>
                                </button>
                            @endif
                            @if ($step < 6)
                                <button type="button" wire:click="nextStep" wire:loading.attr="disabled" wire:target="nextStep" class="dv-btn dv-btn-primary">
                                    Continue <x-line-icon name="arrow" :size="15" />
                                </button>
                            @else
                                <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save" class="dv-btn dv-btn-primary min-h-[3.3rem] px-7 text-[13px]">
                                    <span wire:loading.remove wire:target="save">{{ $managing ? 'Save changes' : 'Submit for review' }}</span>
                                    <span wire:loading wire:target="save">Saving your hotel…</span>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
