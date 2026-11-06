@php
    use App\Shared\Support\Media;

    $statusLabel = $status ? match ($status->value) {
        'published' => ['Live', 'bg-forest text-white'],
        'pending_review' => ['In review', 'bg-[#F6EEDD] text-[#8A6A3A]'],
        'rejected' => ['Changes requested', 'bg-[#F6D6C9] text-clay-dark'],
        'suspended' => ['Suspended', 'bg-clay text-white'],
        default => ['Draft', 'bg-line text-ink'],
    } : null;
    $done = fn (int $s) => $managing || $s < $step;
    $last = count($steps);
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
                    <p class="dv-eyebrow">{{ $managing ? 'Manage your B&B' : 'Add a bed & breakfast' }}</p>
                    <h1 class="mt-2 font-dm-serif text-[clamp(2.2rem,4.5vw,3.8rem)] leading-[1] text-forest">
                        @if ($managing)
                            {{ $name }}
                        @else
                            Open your <span class="italic text-clay">door.</span>
                        @endif
                    </h1>
                    <p class="mt-3 max-w-[38rem] text-[14px] leading-relaxed text-muted">
                        @if ($managing)
                            Update your rate, seasons, breakfast and walk-through any time. Changes to the story, address, photos or house rules are reviewed before they go live; rates and seasons update straight away.
                        @else
                            Seven short steps: introduce your house and yourself, walk guests through it on film, tell them about breakfast, then set your nightly rate.
                        @endif
                    </p>
                </div>
                @if ($managing)
                    <div class="flex flex-wrap items-center gap-2.5">
                        @if ($statusLabel)
                            <span class="rounded-full px-3 py-1.5 text-[11px] font-extrabold {{ $statusLabel[1] }}">{{ $statusLabel[0] }}</span>
                        @endif
                        @if ($status?->value === 'published')
                            <a href="{{ route('bedandbreakfast.show', $bnb) }}" class="dv-btn dv-btn-ghost">View as a guest <x-line-icon name="arrow-up-right" :size="14" /></a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </section>

    @if ($managing)
        <x-host.moderation-note :listing="$bnb?->accommodation" class="mb-4" />
    @endif

    @if ($resumed)
        <div class="px-5 md:px-[max(5vw,calc((100vw-1350px)/2))]">
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-[1rem] border border-forest/20 bg-[#E7EEE8] px-4 py-3 text-[13px] text-forest">
                <span class="flex items-center gap-2"><x-line-icon name="check" :size="16" /> <span><b>Welcome back.</b> We’ve restored <b>{{ $name ?: 'your B&B' }}</b> where you left off, at step {{ $step }}.</span></span>
                <a href="{{ route('provider.bedandbreakfast-create', ['new' => 1]) }}" class="text-[12px] font-extrabold text-clay hover:underline">Start a different B&amp;B instead</a>
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
                <nav class="-mx-5 flex gap-2 overflow-x-auto px-5 pb-2 no-scrollbar lg:mx-0 lg:flex-col lg:gap-1 lg:px-0" aria-label="B&B setup steps">
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
                    <p class="mt-2 font-dm-serif text-[1.25rem] leading-tight text-forest">{{ $name ?: 'Your B&B' }}</p>
                    <p class="mt-0.5 text-[11.5px] text-muted">{{ $has_breakfast ? 'Breakfast included' : 'Room only' }}{{ $city ? ' · '.$city : '' }}</p>
                    <dl class="mt-3 grid grid-cols-3 gap-2 border-t border-line pt-3 text-center">
                        <div><dt class="text-[9.5px] font-extrabold uppercase tracking-[0.08em] text-muted">Rooms</dt><dd class="text-[14px] font-extrabold text-ink">{{ (int) $rooms }}</dd></div>
                        <div><dt class="text-[9.5px] font-extrabold uppercase tracking-[0.08em] text-muted">Scenes</dt><dd class="text-[14px] font-extrabold text-ink">{{ $sceneCount }}</dd></div>
                        <div><dt class="text-[9.5px] font-extrabold uppercase tracking-[0.08em] text-muted">Night</dt><dd class="text-[14px] font-extrabold text-ink">{{ (float) $price_per_night > 0 ? ((float) $price_per_night >= 10000 ? number_format((float) $price_per_night / 1000, 1).'k' : number_format((float) $price_per_night)) : '—' }}</dd></div>
                    </dl>
                </div>
            </aside>

            {{-- ──────────────────────────  Step panel  ──────────────────────── --}}
            <div class="min-w-0">
                <div class="rounded-[1.4rem] border border-line bg-white p-5 shadow-[0_20px_60px_rgba(20,36,28,0.06)] sm:p-8" wire:key="step-{{ $step }}">
                    <p class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-clay">Step {{ $step }} of {{ $last }}</p>
                    <h2 class="mt-1 font-dm-serif text-[clamp(1.7rem,3vw,2.3rem)] leading-tight text-forest">{{ $steps[$step]['label'] }}</h2>

                    {{-- ── 1. The house ── --}}
                    @if ($step === 1)
                        <div class="mt-7 space-y-6">
                            <label class="block">
                                <span class="auth-label">Name of your B&amp;B</span>
                                <input type="text" wire:model.live.debounce.500ms="name" placeholder="The Courtyard House" @class(['auth-input !h-14 !text-[18px] font-dm-serif', 'auth-input-error' => $errors->has('name')])>
                                @error('name') <span class="auth-error">{{ $message }}</span> @enderror
                                <span class="mt-1.5 block text-[11px] text-muted">Web address: holidayhub/bed-and-breakfast/<b class="text-ink">{{ $slug ?: 'your-bnb' }}</b></span>
                                @error('slug') <span class="auth-error">{{ $message }}</span> @enderror
                            </label>

                            <div class="grid gap-5 sm:grid-cols-2">
                                <label class="block">
                                    <span class="auth-label">Guest rooms</span>
                                    <input type="number" min="1" max="50" wire:model.live.debounce.500ms="rooms" @class(['auth-input', 'auth-input-error' => $errors->has('rooms')])>
                                    <span class="mt-1.5 block text-[11px] text-muted">The rooms you let to guests, not counting your own.</span>
                                    @error('rooms') <span class="auth-error">{{ $message }}</span> @enderror
                                </label>
                                <label class="block">
                                    <span class="auth-label">Most guests at once</span>
                                    <input type="number" min="1" max="100" wire:model.live.debounce.500ms="max_guests" @class(['auth-input', 'auth-input-error' => $errors->has('max_guests')])>
                                    <span class="mt-1.5 block text-[11px] text-muted">Across all your rooms. A booking can’t go above this.</span>
                                    @error('max_guests') <span class="auth-error">{{ $message }}</span> @enderror
                                </label>
                            </div>

                            <label class="block" x-data="{ count: @js(mb_strlen($description)) }">
                                <span class="auth-label">The story of your house</span>
                                <textarea wire:model.blur="description" x-on:input="count = $event.target.value.length" rows="7" placeholder="How the house came to be, what the rooms are like, the sounds of the street in the morning, what guests remember…" @class(['auth-input !h-auto py-3 leading-relaxed', 'auth-input-error' => $errors->has('description')])></textarea>
                                <span class="mt-1.5 flex justify-between text-[11px] text-muted"><span>Write for guests, not search engines.</span><span x-text="count + ' / 5000'"></span></span>
                                @error('description') <span class="auth-error">{{ $message }}</span> @enderror
                            </label>

                            <div class="rounded-[1.1rem] border border-line bg-cream/60 p-4 sm:p-5" x-data="{ count: @js(mb_strlen((string) ($policies['host_welcome'] ?? ''))) }">
                                <label class="block">
                                    <span class="auth-label">Your welcome <span class="font-medium normal-case tracking-normal text-muted">(a note from you, the host)</span></span>
                                    <textarea wire:model.blur="policies.host_welcome" x-on:input="count = $event.target.value.length" rows="3" maxlength="600" placeholder="Karibu! I’m Amina. I’ll meet you at the door with a cold tamarind juice, and I’m always happy to point you to the best dhow captain in town." @class(['auth-input !h-auto bg-white py-3 font-dm-serif !text-[16px] italic leading-relaxed', 'auth-input-error' => $errors->has('policies.host_welcome')])></textarea>
                                </label>
                                <span class="mt-1.5 flex justify-between text-[11px] text-muted"><span>Shown on your page, signed by you. Guests choose B&amp;Bs for their hosts.</span><span x-text="count + ' / 600'"></span></span>
                                @error('policies.host_welcome') <span class="auth-error">{{ $message }}</span> @enderror
                            </div>
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
                                        <span class="block text-[11.5px] text-muted">Search for your street or the house, or click the map, then drag the pin onto your front door.</span>
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
                                    <input type="text" x-ref="address" wire:model.blur="address" x-on:input="addressFromMap = false" placeholder="Kenyatta Road, Shela" @class(['auth-input', 'auth-input-error' => $errors->has('address')])>
                                    @error('address') <span class="auth-error">{{ $message }}</span> @enderror
                                </label>
                                <label class="block">
                                    <span class="auth-label">Town or city</span>
                                    <input type="text" x-ref="city" wire:model.blur="city" placeholder="Lamu" @class(['auth-input', 'auth-input-error' => $errors->has('city')])>
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
                                    <span class="text-[11px] text-muted">{{ $mediaCount }} items · {{ $filmCount }} {{ Str::plural('film', $filmCount) }} · show the rooms, the breakfast table, the garden, the street outside</span>
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

                    {{-- ── 4. The walk-through ── --}}
                    @if ($step === 4)
                        <x-host.walkthrough-step
                            :owner="$bnb"
                            noun="B&B"
                            headline="Walk guests through your house the way you’d show a friend, from the front door to the breakfast table."
                            story="Guests play your walk-through full screen, scene by scene, like stories, from the top of your page. Add a line of caption where you’d say something out loud: “Breakfast is served here from 7.30”."
                            :tips="[
                                ['One space per film', 'The arrival, the courtyard, a guest room or two, breakfast, the neighbourhood.'],
                                ['5–20 seconds each', 'Phone held steady and upright, doors opening in shot. Photos hold for five seconds.'],
                                ['In walking order', 'Drag scenes into the order a guest would walk in. End out in the neighbourhood.'],
                            ]"
                            spaces="the arrival, the courtyard, a guest room, breakfast and the neighbourhood"
                            :suggested="5"
                        />
                    @endif

                    {{-- ── 5. Breakfast & rules ── --}}
                    @if ($step === 5)
                        <div class="mt-7 space-y-8">
                            {{-- Breakfast --}}
                            <div class="overflow-hidden rounded-[1.2rem] border border-line">
                                <label class="flex cursor-pointer items-start gap-4 bg-[#F6EEDD]/60 p-4 sm:p-5">
                                    <span class="grid h-11 w-11 flex-none place-items-center rounded-full bg-white text-clay"><x-line-icon name="utensils" :size="19" /></span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block font-dm-serif text-[1.35rem] leading-tight text-forest">Breakfast is included</span>
                                        <span class="block text-[12px] text-muted">Guests see it on your card and at the top of your page. Turn it off if you let rooms only.</span>
                                    </span>
                                    <input type="checkbox" wire:model.live="has_breakfast" class="auth-check mt-1">
                                </label>
                                @if ($has_breakfast)
                                    <label class="block border-t border-line bg-white p-4 sm:p-5">
                                        <span class="auth-label">What’s served, and when</span>
                                        <textarea wire:model.blur="breakfast_details" rows="4" maxlength="1000" placeholder="Served 7.30–10.00 on the rooftop: fresh mango and passion fruit, mandazi from the bakery next door, eggs any way, Kenyan coffee and spiced chai. Vegetarian and gluten-free on request." @class(['auth-input !h-auto py-3 leading-relaxed', 'auth-input-error' => $errors->has('breakfast_details')])></textarea>
                                        <span class="mt-1.5 block text-[11px] text-muted">Hours, where it’s served, a few things on the table, and any diets you cater for.</span>
                                        @error('breakfast_details') <span class="auth-error">{{ $message }}</span> @enderror
                                    </label>
                                @endif
                            </div>

                            {{-- Facilities --}}
                            @foreach ([['Around the house', $houseAmenities, 'ha'], ['In the guest rooms', $roomAmenities, 'ra']] as [$heading, $list, $prefix])
                                <div>
                                    <div class="flex items-end justify-between gap-3">
                                        <span class="auth-label !mb-0">{{ $heading }}</span>
                                        @if ($loop->first)
                                            <span class="text-[11px] text-muted">{{ count($selectedAmenities) }} selected</span>
                                        @endif
                                    </div>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @foreach ($list as $amenity)
                                            <label class="cursor-pointer" title="{{ $amenity->description }}" wire:key="{{ $prefix }}-{{ $amenity->id }}">
                                                <input type="checkbox" value="{{ $amenity->id }}" wire:model.live="selectedAmenities" class="peer sr-only">
                                                <span class="inline-flex items-center gap-1.5 rounded-full border border-line bg-white px-3.5 py-2 text-[12px] font-bold text-muted transition-colors peer-checked:border-forest peer-checked:bg-forest peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-clay/40 hover:border-forest">
                                                    {{ $amenity->name }}
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                            @error('selectedAmenities') <span class="auth-error">{{ $message }}</span> @enderror

                            {{-- House rules --}}
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
                                    <input type="text" wire:model="policies.children" placeholder="Children of all ages are welcome. A cot on request." class="auth-input">
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
                                        <option value="no">Non-smoking house</option>
                                        <option value="areas">Designated areas only</option>
                                        <option value="yes">Allowed</option>
                                    </select>
                                </label>
                                <label class="block sm:col-span-2">
                                    <span class="auth-label">Deposit or house notes <span class="font-medium normal-case tracking-normal text-muted">(optional)</span></span>
                                    <input type="text" wire:model="policies.deposit" placeholder="A refundable deposit of {{ $symbol }} 5,000 is taken on arrival." class="auth-input">
                                </label>
                            </div>
                        </div>
                    @endif

                    {{-- ── 6. Pricing ── --}}
                    @if ($step === 6)
                        <div class="mt-7 space-y-8">
                            <div class="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <span class="auth-label">Nightly rate per room</span>
                                    {{-- Currency sits with the price; it's one setting for the whole B&B. --}}
                                    <div @class(['flex overflow-hidden rounded-[0.8rem] border bg-surface transition-colors focus-within:border-forest', 'border-clay' => $errors->has('price_per_night') || $errors->has('currency'), 'border-line' => ! ($errors->has('price_per_night') || $errors->has('currency'))])>
                                        <select wire:model.live="currency" aria-label="Currency" class="w-[6.5rem] flex-none border-0 border-r border-line bg-cream py-0 pl-3 pr-7 text-[13px] font-extrabold text-forest focus:ring-0">
                                            @foreach ($currencies as $code => $label)
                                                <option value="{{ $code }}" title="{{ $label }}">{{ $code }}</option>
                                            @endforeach
                                        </select>
                                        <input type="number" min="1" step="any" inputmode="decimal" wire:model.blur="price_per_night" placeholder="{{ in_array($currency, ['USD', 'EUR', 'GBP', 'CHF', 'AUD', 'CAD']) ? '95' : '8500' }}" aria-label="Nightly rate per room" class="h-[3.1rem] min-w-0 flex-1 border-0 bg-transparent px-4 text-[13px] font-semibold text-ink focus:ring-0">
                                    </div>
                                    <span class="mt-1.5 block text-[11px] text-muted">
                                        {{ $currencies[$currency] ?? $currency }}@if (! $currencyChosen && $country) · suggested for {{ $country }}@endif. One currency for your rate and all your seasons.
                                    </span>
                                    @error('currency') <span class="auth-error">{{ $message }}</span> @enderror
                                    @error('price_per_night') <span class="auth-error">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            {{-- Seasons --}}
                            <div class="border-t border-line pt-6">
                                <span class="auth-label !mb-0">Seasonal rates</span>
                                <span class="block text-[11.5px] text-muted">Override the nightly rate for busy or quiet times: the festive season, a local festival, the long rains. Rates are in {{ $currency }}.</span>
                                @if ($seasonsFromLegacy)
                                    <p class="mt-3 rounded-[0.9rem] border border-[#E9D3A6] bg-[#F6EEDD] px-4 py-2.5 text-[12px] text-[#8A6A3A]">We’ve brought over the seasons saved with your listing before. Check them and save; they’ll be used for pricing from then on.</p>
                                @endif
                                <div class="mt-3 space-y-2">
                                    @foreach ($seasons as $r => $season)
                                        <div class="grid grid-cols-2 gap-2 rounded-[0.9rem] bg-cream p-3 sm:grid-cols-[1.4fr_1fr_1fr_1fr_auto]" wire:key="season-{{ $r }}">
                                            <input type="text" wire:model.blur="seasons.{{ $r }}.label" placeholder="Maulidi festival" class="auth-input col-span-2 sm:col-span-1" aria-label="Season name">
                                            <input type="date" wire:model.blur="seasons.{{ $r }}.start" @class(['auth-input', 'auth-input-error' => $errors->has("seasons.$r.start")]) aria-label="Starts">
                                            <input type="date" wire:model.blur="seasons.{{ $r }}.end" @class(['auth-input', 'auth-input-error' => $errors->has("seasons.$r.end")]) aria-label="Ends">
                                            <input type="number" min="1" step="any" wire:model.blur="seasons.{{ $r }}.rate" placeholder="Rate in {{ $currency }}" @class(['auth-input', 'auth-input-error' => $errors->has("seasons.$r.rate")]) aria-label="Nightly rate">
                                            <button type="button" wire:click="removeSeason({{ $r }})" class="grid h-[3.1rem] w-10 place-items-center rounded-[0.8rem] border border-line bg-white text-muted hover:border-clay hover:text-clay" aria-label="Remove season"><x-line-icon name="close" :size="13" /></button>
                                            @foreach (['start', 'end', 'rate'] as $f)
                                                @error("seasons.$r.$f") <span class="auth-error col-span-full">{{ $message }}</span> @enderror
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                                <button type="button" wire:click="addSeason" class="mt-2 text-[12px] font-extrabold text-clay hover:underline">+ Add a season</button>
                            </div>
                        </div>
                    @endif

                    {{-- ── 7. Review ── --}}
                    @if ($step === 7)
                        <div class="mt-7 space-y-4">
                            @foreach ([
                                1 => [$name ?: 'No name yet', (int) $rooms.' '.Str::plural('guest room', (int) $rooms).' · up to '.(int) $max_guests.' guests · '.Str::limit($description, 120).(filled($policies['host_welcome'] ?? '') ? ' · with your welcome note' : ' · no welcome note yet')],
                                2 => [collect([$address, $city, $country])->filter()->implode(', ') ?: 'No address yet', ($this->selectedDestination?->name ?? 'No destination chosen').(filled($latitude) ? ' · pinned at '.round((float) $latitude, 4).', '.round((float) $longitude, 4) : ' · no map pin yet')],
                                3 => [collect([($coverVideo || $cover_video_path) ? 'Cover film' : null, ($cover || $cover_path) ? 'cover photo' : 'No cover photo yet'])->filter()->implode(' + '), (count($gallery_paths) + count($new_gallery)).' gallery items, '.collect($gallery_paths)->filter(fn ($p) => Media::isVideo($p))->count().' of them films'],
                                4 => [$sceneCount ? $sceneCount.' '.Str::plural('scene', $sceneCount).' in your walk-through' : 'No walk-through yet', $sceneCount ? $sceneFilms.' '.Str::plural('film', $sceneFilms).', '.($sceneCount - $sceneFilms).' '.Str::plural('photo', $sceneCount - $sceneFilms) : 'Optional, but it’s what guests open first.'],
                                5 => [($has_breakfast ? 'Breakfast included' : 'Room only').' · '.count($selectedAmenities).' facilities', ($has_breakfast && filled($breakfast_details) ? Str::limit($breakfast_details, 80).' · ' : '').'Check-in '.$policies['check_in'].' · check-out '.$policies['check_out'].' · '.$policies['cancellation']],
                                6 => [(float) $price_per_night > 0 ? $symbol.' '.number_format((float) $price_per_night).' per room, per night' : 'No nightly rate yet', count($seasons) ? count($seasons).' '.Str::plural('season', count($seasons)).': '.collect($seasons)->map(fn ($s) => ($s['label'] ?: 'Season').' '.($s['rate'] ? $symbol.' '.number_format((float) $s['rate']) : ''))->implode(', ') : 'No seasonal rates'],
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

                            <p class="rounded-[0.9rem] bg-cream px-4 py-3 text-[12.5px] leading-relaxed text-muted">
                                @if ($managing)
                                    Saving updates your rate, seasons, breakfast and walk-through straight away. If you changed the name, story, welcome note, address, photos or house rules, the listing goes back to our team for a quick review first.
                                @else
                                    When you submit, our team checks the listing, usually within a day. You can keep editing your rate and seasons while it’s in review.
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
                                @if ($bnb)
                                    <span class="text-[11.5px] font-semibold text-muted" wire:loading.remove><x-line-icon name="check" :size="12" /> Progress saved</span>
                                    <span class="text-[11.5px] font-semibold text-clay" wire:loading>Saving…</span>
                                @endif
                            @endunless
                        </div>
                        <div class="flex flex-wrap gap-2.5">
                            @if ($managing && $step < $last)
                                <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save" class="dv-btn dv-btn-ghost">
                                    <span wire:loading.remove wire:target="save">Save changes</span><span wire:loading wire:target="save">Saving…</span>
                                </button>
                            @endif
                            @if ($step < $last)
                                <button type="button" wire:click="nextStep" wire:loading.attr="disabled" wire:target="nextStep" class="dv-btn dv-btn-primary">
                                    Continue <x-line-icon name="arrow" :size="15" />
                                </button>
                            @else
                                <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save" class="dv-btn dv-btn-primary min-h-[3.3rem] px-7 text-[13px]">
                                    <span wire:loading.remove wire:target="save">{{ $managing ? 'Save changes' : 'Submit for review' }}</span>
                                    <span wire:loading wire:target="save">Saving your B&amp;B…</span>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
