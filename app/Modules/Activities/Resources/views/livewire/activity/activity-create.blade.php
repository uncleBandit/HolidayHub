@php
    use App\Support\Media;

    $statusLabel = $status ? match ($status->value) {
        'published' => ['Live', 'bg-forest text-white'],
        'pending_review' => ['In review', 'bg-[#F6EEDD] text-[#8A6A3A]'],
        'rejected' => ['Changes requested', 'bg-[#F6D6C9] text-clay-dark'],
        'suspended' => ['Suspended', 'bg-clay text-white'],
        default => ['Draft', 'bg-line text-ink'],
    } : null;
    $done = fn (int $s) => $managing || $s < $step;
    $lastStep = count($steps);
    $money = fn ($amount) => filled($amount) && (float) $amount > 0 ? $symbol.' '.number_format((float) $amount) : '—';
    $hm = fn (int $m) => $m <= 0 ? '—' : trim(($m >= 60 ? intdiv($m, 60).'h ' : '').($m % 60 ? ($m % 60).'m' : ''));
    $categoryName = $categories->firstWhere('id', $category_id)?->name;
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
                    <p class="dv-eyebrow">{{ $managing ? 'Manage your experience' : 'Add an experience' }}</p>
                    <h1 class="mt-2 font-dm-serif text-[clamp(2.2rem,4.5vw,3.8rem)] leading-[1] text-forest">
                        @if ($managing)
                            {{ $name }}
                        @else
                            Set up your <span class="italic text-clay">experience.</span>
                        @endif
                    </h1>
                    <p class="mt-3 max-w-[38rem] text-[14px] leading-relaxed text-muted">
                        @if ($managing)
                            Update the moments, times and price any time. Changes to the story, photos, plan, meeting point or who it’s for are reviewed before they go live; price, times and sessions update straight away.
                        @else
                            Eight short steps: tell guests what they’ll feel, film it moment by moment, lay out the plan, then open your times. Guests should feel they’ve already been.
                        @endif
                    </p>
                </div>
                @if ($managing)
                    <div class="flex flex-wrap items-center gap-2.5">
                        @if ($statusLabel)
                            <span class="rounded-full px-3 py-1.5 text-[11px] font-extrabold {{ $statusLabel[1] }}">{{ $statusLabel[0] }}</span>
                        @endif
                        @if ($status?->value === 'published')
                            <a href="{{ route('activity.show', $activity) }}" class="dv-btn dv-btn-ghost">View as a guest <x-line-icon name="arrow-up-right" :size="14" /></a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </section>

    @if ($managing)
        <x-host.moderation-note :listing="$activity" class="mb-4" />
    @endif

    @if ($resumed)
        <div class="px-5 md:px-[max(5vw,calc((100vw-1350px)/2))]">
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-[1rem] border border-forest/20 bg-[#E7EEE8] px-4 py-3 text-[13px] text-forest">
                <span class="flex items-center gap-2"><x-line-icon name="check" :size="16" /> <span><b>Welcome back.</b> We’ve restored <b>{{ $name ?: 'your experience' }}</b> where you left off, at step {{ $step }}.</span></span>
                <a href="{{ route('provider.activity-create', ['new' => 1]) }}" class="text-[12px] font-extrabold text-clay hover:underline">Start a different experience instead</a>
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
                <nav class="-mx-5 flex gap-2 overflow-x-auto px-5 pb-2 no-scrollbar lg:mx-0 lg:flex-col lg:gap-1 lg:px-0" aria-label="Experience setup steps">
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
                    <p class="mt-2 font-dm-serif text-[1.25rem] leading-tight text-forest">{{ $name ?: 'Your experience' }}</p>
                    <p class="mt-0.5 text-[11.5px] text-muted">
                        {{ collect([$categoryName, $durationMinutes ? $hm($durationMinutes) : null, 'up to '.max(1, (int) $capacity).' guests'])->filter()->implode(' · ') }}
                    </p>
                    <dl class="mt-3 grid grid-cols-3 gap-2 border-t border-line pt-3 text-center">
                        <div><dt class="text-[9.5px] font-extrabold uppercase tracking-[0.08em] text-muted">Moments</dt><dd class="text-[14px] font-extrabold text-ink">{{ $sceneCount }}</dd></div>
                        <div><dt class="text-[9.5px] font-extrabold uppercase tracking-[0.08em] text-muted">A week</dt><dd class="text-[14px] font-extrabold text-ink">{{ $weeklySessions }}</dd></div>
                        <div><dt class="text-[9.5px] font-extrabold uppercase tracking-[0.08em] text-muted">Person</dt><dd class="text-[14px] font-extrabold text-ink">{{ filled($base_price) && (float) $base_price > 0 ? ((float) $base_price >= 1000 ? number_format((float) $base_price / 1000, 1).'k' : number_format((float) $base_price)) : '—' }}</dd></div>
                    </dl>
                </div>
            </aside>

            {{-- ──────────────────────────  Step panel  ──────────────────────── --}}
            <div class="min-w-0">
                <div class="rounded-[1.4rem] border border-line bg-white p-5 shadow-[0_20px_60px_rgba(20,36,28,0.06)] sm:p-8" wire:key="step-{{ $step }}">
                    <p class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-clay">Step {{ $step }} of {{ $lastStep }}</p>
                    <h2 class="mt-1 font-dm-serif text-[clamp(1.7rem,3vw,2.3rem)] leading-tight text-forest">{{ $steps[$step]['label'] }}</h2>

                    {{-- ── 1. The experience ── --}}
                    @if ($step === 1)
                        <div class="mt-7 space-y-7">
                            <label class="block">
                                <span class="auth-label">Name of the experience</span>
                                <input type="text" wire:model.live.debounce.500ms="name" placeholder="Sunset Dhow Sail off Lamu" @class(['auth-input !h-14 !text-[18px] font-dm-serif', 'auth-input-error' => $errors->has('name')])>
                                @error('name') <span class="auth-error">{{ $message }}</span> @enderror
                                <span class="mt-1.5 block text-[11px] text-muted">Web address: holidayhub/activity/<b class="text-ink">{{ $slug ?: 'your-experience' }}</b></span>
                                @error('slug') <span class="auth-error">{{ $message }}</span> @enderror
                            </label>

                            <div>
                                <span class="auth-label">What kind of experience is it?</span>
                                <div class="mt-1 flex flex-wrap gap-2">
                                    @foreach ($categories as $category)
                                        <label class="cursor-pointer" wire:key="cat-{{ $category->id }}">
                                            <input type="radio" value="{{ $category->id }}" wire:model.live="category_id" class="peer sr-only">
                                            <span class="inline-flex items-center gap-1.5 rounded-full border border-line bg-white px-3.5 py-2 text-[12px] font-bold text-muted transition-colors peer-checked:border-forest peer-checked:bg-forest peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-clay/40 hover:border-forest">{{ $category->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                @error('category_id') <span class="auth-error">{{ $message }}</span> @enderror
                            </div>

                            <label class="block" x-data="{ count: @js(mb_strlen($short_description)) }">
                                <span class="auth-label">The hook</span>
                                <textarea wire:model.blur="short_description" x-on:input="count = $event.target.value.length" rows="2" maxlength="300" placeholder="Three hours under a lateen sail as the sun drops into the Lamu channel, with fresh juice and grilled snacks on board." @class(['auth-input !h-auto py-3 font-dm-serif !text-[17px] leading-snug', 'auth-input-error' => $errors->has('short_description')])></textarea>
                                <span class="mt-1.5 flex justify-between text-[11px] text-muted"><span>One line that makes guests want to go. It sits under the name on your page and on reels.</span><span x-text="count + ' / 300'"></span></span>
                                @error('short_description') <span class="auth-error">{{ $message }}</span> @enderror
                            </label>

                            <label class="block" x-data="{ count: @js(mb_strlen($description)) }">
                                <span class="auth-label">The story</span>
                                <textarea wire:model.blur="description" x-on:input="count = $event.target.value.length" rows="7" placeholder="Where you meet and what guests feel in the first minute, the moment everyone goes quiet, what your guide knows that no one else does, how it ends…" @class(['auth-input !h-auto py-3 leading-relaxed', 'auth-input-error' => $errors->has('description')])></textarea>
                                <span class="mt-1.5 flex justify-between text-[11px] text-muted"><span>Write for guests, not search engines.</span><span x-text="count + ' / 5000'"></span></span>
                                @error('description') <span class="auth-error">{{ $message }}</span> @enderror
                            </label>

                            <div class="border-t border-line pt-7">
                                <span class="auth-label !mb-0">Highlights</span>
                                <span class="mb-3 block text-[11.5px] text-muted">The three to six things guests will tell their friends about. Shown near the top of your page.</span>
                                <div class="space-y-2">
                                    @foreach ($highlights as $i => $line)
                                        <div class="flex items-center gap-2" wire:key="hl-{{ $i }}">
                                            <span class="grid h-8 w-8 flex-none place-items-center rounded-full bg-forest text-white"><x-line-icon name="check" :size="13" /></span>
                                            <input type="text" wire:model.blur="highlights.{{ $i }}" placeholder="{{ ['Traditional lateen-sail dhow', 'Sunset over the channel', 'Swim stop at Shela'][$i] ?? 'Another highlight' }}" @class(['auth-input', 'auth-input-error' => $errors->has("highlights.$i")])>
                                            <button type="button" wire:click="removeLine('highlights', {{ $i }})" class="grid h-[3.1rem] w-10 flex-none place-items-center rounded-[0.8rem] border border-line bg-white text-muted hover:border-clay hover:text-clay" aria-label="Remove highlight"><x-line-icon name="close" :size="13" /></button>
                                        </div>
                                    @endforeach
                                </div>
                                <button type="button" wire:click="addLine('highlights')" class="mt-2 text-[12px] font-extrabold text-clay hover:underline">+ Add a highlight</button>
                                @error('highlights.*') <span class="auth-error">{{ $message }}</span> @enderror
                            </div>

                            <div class="border-t border-line pt-7">
                                <div class="flex items-end justify-between gap-3">
                                    <span class="auth-label !mb-0">Languages you guide in</span>
                                    <span class="text-[11px] text-muted">{{ count($languages) }} selected</span>
                                </div>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach ($languageOptions as $code => $language)
                                        <label class="cursor-pointer" wire:key="lang-{{ $code }}">
                                            <input type="checkbox" value="{{ $code }}" wire:model.live="languages" class="peer sr-only">
                                            <span class="inline-flex items-center gap-1.5 rounded-full border border-line bg-white px-3.5 py-2 text-[12px] font-bold text-muted transition-colors peer-checked:border-forest peer-checked:bg-forest peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-clay/40 hover:border-forest">{{ $language }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                @error('languages') <span class="auth-error">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    @endif

                    {{-- ── 2. Where it happens ── --}}
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
                                        <span class="auth-label !mb-0">Pin the meeting point</span>
                                        <span class="block text-[11.5px] text-muted">Search for the jetty, gate, café or street, or click the map, then drag the pin to the exact spot guests meet you.</span>
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

                            {{-- Meeting point --}}
                            <div class="grid gap-5 sm:grid-cols-2">
                                <label class="block sm:col-span-2">
                                    <span class="auth-label">Meeting point</span>
                                    <input type="text" wire:model.blur="meeting_point" placeholder="Lamu Seafront Jetty" @class(['auth-input', 'auth-input-error' => $errors->has('meeting_point')])>
                                    @error('meeting_point') <span class="auth-error">{{ $message }}</span> @enderror
                                </label>
                                <label class="block sm:col-span-2">
                                    <span class="auth-label">Street address or landmark</span>
                                    <input type="text" x-ref="address" wire:model.blur="address" x-on:input="addressFromMap = false" placeholder="Harambee Avenue, opposite the fort" @class(['auth-input', 'auth-input-error' => $errors->has('address')])>
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
                                <label class="block sm:col-span-2" x-data="{ count: @js(mb_strlen($meeting_instructions)) }">
                                    <span class="auth-label">How guests find you <span class="font-medium normal-case tracking-normal text-muted">(optional)</span></span>
                                    <textarea wire:model.blur="meeting_instructions" x-on:input="count = $event.target.value.length" rows="3" placeholder="Look for the blue dhow with the white sail. Your guide waves a HolidayHub flag 15 minutes before the start." @class(['auth-input !h-auto py-3 leading-relaxed', 'auth-input-error' => $errors->has('meeting_instructions')])></textarea>
                                    <span class="mt-1.5 flex justify-between text-[11px] text-muted"><span>Sent with every booking, and shown on your page.</span><span x-text="count + ' / 1000'"></span></span>
                                    @error('meeting_instructions') <span class="auth-error">{{ $message }}</span> @enderror
                                </label>
                            </div>
                        </div>
                    @endif

                    {{-- ── 3. Photos & film ── --}}
                    @if ($step === 3)
                        <div class="mt-7 space-y-8">
                            <p class="rounded-[0.9rem] bg-cream px-4 py-3 text-[12.5px] leading-relaxed text-muted">
                                <b class="text-forest">HolidayHub leads with film.</b> Guests discover experiences as reels, so a short cover film (15–60 seconds of the best of it, shot in portrait or landscape) does more than any photo. Uploads save as soon as they finish.
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
                                    <span class="text-[11px] text-muted">{{ $mediaCount }} items · {{ $filmCount }} {{ Str::plural('film', $filmCount) }} · show the setting, the people, the best moment, the details</span>
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

                    {{-- ── 4. Moment by moment ── --}}
                    @if ($step === 4)
                        <x-host.walkthrough-step
                            :owner="$activity"
                            noun="experience"
                            headline="Let guests live the experience before they book it, moment by moment."
                            story="Guests play your moments full screen, one after another, like stories: the welcome, the first sight, the moment everyone goes quiet. It’s the closest thing to being there, and the part of your page that makes them book."
                            :tips="[
                                ['Film the welcome', 'Your guide saying hello at the meeting point. Guests book people, not just places.'],
                                ['5–20 seconds each', 'The first sight, the peak moment, the people, the light at the end. Photos hold for five seconds.'],
                                ['In the order it happens', 'Drag the moments into the order guests live them. Save the best for last.'],
                            ]"
                            spaces="the meeting point, your guide, the first sight, the moment itself and the people"
                            :suggested="$suggestedScenes"
                        />
                    @endif

                    {{-- ── 5. The plan ── --}}
                    @if ($step === 5)
                        <div class="mt-7 space-y-8">
                            <div>
                                <div class="flex flex-wrap items-end justify-between gap-3">
                                    <div>
                                        <span class="auth-label !mb-0">How it unfolds</span>
                                        <span class="block text-[11.5px] text-muted">The plan, stop by stop, from meeting your guide to saying goodbye. Guests see it as a timeline.</span>
                                    </div>
                                    @if ($timelineMinutes)
                                        <span class="rounded-full bg-cream px-3 py-1.5 text-[11px] font-bold text-forest">{{ $hm($timelineMinutes) }} on the timeline</span>
                                    @endif
                                </div>

                                <ol class="relative mt-4 space-y-3 before:absolute before:bottom-6 before:left-[1.05rem] before:top-6 before:w-px before:bg-line">
                                    @foreach ($itinerary as $i => $item)
                                        <li class="relative flex gap-3" wire:key="stop-{{ $i }}">
                                            <span class="relative z-10 mt-3 grid h-[2.1rem] w-[2.1rem] flex-none place-items-center rounded-full border border-line bg-white text-[12px] font-extrabold text-forest">{{ $i + 1 }}</span>
                                            <div class="min-w-0 flex-1 rounded-[0.9rem] bg-cream p-3">
                                                <div class="grid gap-2 sm:grid-cols-[1fr_8.5rem_auto]">
                                                    <input type="text" wire:model.blur="itinerary.{{ $i }}.title" placeholder="{{ ['Meet your guide at the jetty', 'Sail down the channel', 'Sunset and snacks on deck'][$i] ?? 'What happens next' }}" @class(['auth-input font-bold', 'auth-input-error' => $errors->has("itinerary.$i.title")]) aria-label="Title">
                                                    <div @class(['flex overflow-hidden rounded-[0.8rem] border bg-surface focus-within:border-forest', 'border-clay' => $errors->has("itinerary.$i.minutes"), 'border-line' => ! $errors->has("itinerary.$i.minutes")])>
                                                        <input type="number" min="1" max="1440" inputmode="numeric" wire:model.blur="itinerary.{{ $i }}.minutes" placeholder="30" aria-label="Minutes" class="h-[3.1rem] min-w-0 flex-1 border-0 bg-transparent px-3 text-[14px] font-extrabold text-ink focus:ring-0">
                                                        <span class="grid flex-none place-items-center border-l border-line bg-cream px-3 text-[11px] font-extrabold text-forest">min</span>
                                                    </div>
                                                    <div class="flex gap-1">
                                                        <button type="button" wire:click="moveStop({{ $i }}, -1)" @disabled($loop->first) class="grid h-[3.1rem] w-9 place-items-center rounded-[0.8rem] border border-line bg-white text-muted hover:border-forest hover:text-forest disabled:opacity-40" aria-label="Move up"><span class="-rotate-90"><x-line-icon name="arrow" :size="13" /></span></button>
                                                        <button type="button" wire:click="moveStop({{ $i }}, 1)" @disabled($loop->last) class="grid h-[3.1rem] w-9 place-items-center rounded-[0.8rem] border border-line bg-white text-muted hover:border-forest hover:text-forest disabled:opacity-40" aria-label="Move down"><span class="rotate-90"><x-line-icon name="arrow" :size="13" /></span></button>
                                                        <button type="button" wire:click="removeStop({{ $i }})" class="grid h-[3.1rem] w-9 place-items-center rounded-[0.8rem] border border-line bg-white text-muted hover:border-clay hover:text-clay" aria-label="Remove"><x-line-icon name="close" :size="13" /></button>
                                                    </div>
                                                </div>
                                                <textarea wire:model.blur="itinerary.{{ $i }}.description" rows="2" placeholder="What guests see, do and feel here." class="auth-input mt-2 !h-auto py-2.5 text-[13px] leading-relaxed" aria-label="Description"></textarea>
                                                @foreach (['title', 'minutes', 'description'] as $f)
                                                    @error("itinerary.$i.$f") <span class="auth-error">{{ $message }}</span> @enderror
                                                @endforeach
                                            </div>
                                        </li>
                                    @endforeach
                                </ol>
                                <button type="button" wire:click="addStop" class="mt-3 text-[12px] font-extrabold text-clay hover:underline">+ Add {{ count($itinerary) ? 'the next part' : 'the first part of the plan' }}</button>
                            </div>

                            <div class="border-t border-line pt-7">
                                <span class="auth-label">Total duration</span>
                                <div class="flex flex-wrap items-center gap-3">
                                    <div @class(['flex w-[9rem] overflow-hidden rounded-[0.8rem] border bg-surface focus-within:border-forest', 'border-clay' => $errors->has('duration_hours'), 'border-line' => ! $errors->has('duration_hours')])>
                                        <input type="number" min="0" max="240" inputmode="numeric" wire:model.blur="duration_hours" aria-label="Hours" class="h-[3.1rem] min-w-0 flex-1 border-0 bg-transparent px-4 text-[15px] font-extrabold text-ink focus:ring-0">
                                        <span class="grid flex-none place-items-center border-l border-line bg-cream px-3 text-[12px] font-extrabold text-forest">hours</span>
                                    </div>
                                    <div @class(['flex w-[9rem] overflow-hidden rounded-[0.8rem] border bg-surface focus-within:border-forest', 'border-clay' => $errors->has('duration_mins'), 'border-line' => ! $errors->has('duration_mins')])>
                                        <input type="number" min="0" max="59" step="5" inputmode="numeric" wire:model.blur="duration_mins" aria-label="Minutes" class="h-[3.1rem] min-w-0 flex-1 border-0 bg-transparent px-4 text-[15px] font-extrabold text-ink focus:ring-0">
                                        <span class="grid flex-none place-items-center border-l border-line bg-cream px-3 text-[12px] font-extrabold text-forest">min</span>
                                    </div>
                                    @if ($timelineMinutes && $timelineMinutes !== $durationMinutes)
                                        <button type="button" wire:click="useTimelineTotal" class="text-[12px] font-extrabold text-clay hover:underline">Use the timeline’s {{ $hm($timelineMinutes) }}</button>
                                    @endif
                                </div>
                                <span class="mt-1.5 block text-[11px] text-muted">From meeting your guide to the goodbye. Your session times follow it.</span>
                                @error('duration_hours') <span class="auth-error">{{ $message }}</span> @enderror
                                @error('duration_mins') <span class="auth-error">{{ $message }}</span> @enderror
                            </div>

                            <div class="grid gap-7 border-t border-line pt-7 lg:grid-cols-3 lg:gap-5">
                                @foreach ([
                                    'inclusions' => ['What’s included', 'Guide, gear, food, fees, transfers.', 'check', 'bg-forest text-white', 'Fresh juice and grilled snacks'],
                                    'exclusions' => ['Not included', 'So nobody is surprised on the day.', 'close', 'bg-clay text-white', 'Tips for the crew'],
                                    'bring' => ['What to bring', 'Guests get this list with their booking.', 'leaf', 'bg-cream text-clay', 'Swimwear and a towel'],
                                ] as $list => [$label, $hint, $icon, $badge, $placeholder])
                                    <div>
                                        <span class="auth-label !mb-0">{{ $label }}</span>
                                        <span class="mb-3 block text-[11.5px] text-muted">{{ $hint }}</span>
                                        <div class="space-y-2">
                                            @foreach ($this->{$list} as $i => $line)
                                                <div class="flex items-center gap-2" wire:key="{{ $list }}-{{ $i }}">
                                                    <span class="grid h-7 w-7 flex-none place-items-center rounded-full {{ $badge }}"><x-line-icon :name="$icon" :size="12" /></span>
                                                    <input type="text" wire:model.blur="{{ $list }}.{{ $i }}" placeholder="{{ $i === 0 ? $placeholder : '' }}" @class(['auth-input min-w-0', 'auth-input-error' => $errors->has("$list.$i")])>
                                                    <button type="button" wire:click="removeLine('{{ $list }}', {{ $i }})" class="grid h-[3.1rem] w-9 flex-none place-items-center rounded-[0.8rem] border border-line bg-white text-muted hover:border-clay hover:text-clay" aria-label="Remove"><x-line-icon name="close" :size="12" /></button>
                                                </div>
                                            @endforeach
                                        </div>
                                        <button type="button" wire:click="addLine('{{ $list }}')" class="mt-2 text-[12px] font-extrabold text-clay hover:underline">+ Add</button>
                                        @error("$list.*") <span class="auth-error">{{ $message }}</span> @enderror
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- ── 6. Who it's for ── --}}
                    @if ($step === 6)
                        <div class="mt-7 space-y-8">
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                                @foreach (['capacity' => ['Largest group', 1, 500, '12'], 'min_age' => ['Youngest age', 0, 99, 'Any'], 'max_age' => ['Oldest age', 1, 120, 'Any']] as $field => [$label, $min, $max, $placeholder])
                                    <label class="block">
                                        <span class="mb-1 block text-[11px] font-extrabold text-muted">{{ $label }}</span>
                                        <input type="number" min="{{ $min }}" max="{{ $max }}" inputmode="numeric" wire:model.live.debounce.500ms="{{ $field }}" placeholder="{{ $placeholder }}" @class(['auth-input text-center !text-[16px] font-extrabold', 'auth-input-error' => $errors->has($field)])>
                                        @error($field) <span class="auth-error">{{ $message }}</span> @enderror
                                    </label>
                                @endforeach
                            </div>

                            <div class="border-t border-line pt-7">
                                <span class="auth-label">How active is it?</span>
                                <div class="mt-1 grid gap-2 sm:grid-cols-3">
                                    @foreach ($fitnessOptions as $key => [$label, $line])
                                        <label class="cursor-pointer" wire:key="fit-{{ $key }}">
                                            <input type="radio" value="{{ $key }}" wire:model.live="fitness" class="peer sr-only">
                                            <span class="block h-full rounded-[0.9rem] border border-line bg-white p-3.5 transition-colors peer-checked:border-clay peer-checked:bg-[#FBE7DF] hover:border-forest">
                                                <span class="block text-[13px] font-extrabold text-ink">{{ $label }}</span>
                                                <span class="block text-[11px] text-muted">{{ $line }}</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                @error('fitness') <span class="auth-error">{{ $message }}</span> @enderror
                            </div>

                            <div class="border-t border-line pt-7">
                                <span class="auth-label !mb-0">Before guests book</span>
                                <span class="mb-3 block text-[11.5px] text-muted">Anything a guest must be or have: able to swim, a valid passport, closed shoes. Shown as required.</span>
                                <div class="space-y-2">
                                    @foreach ($requirements as $i => $line)
                                        <div class="flex items-center gap-2" wire:key="req-{{ $i }}">
                                            <span class="grid h-7 w-7 flex-none place-items-center rounded-full bg-clay/10 text-clay"><x-line-icon name="user" :size="12" /></span>
                                            <input type="text" wire:model.blur="requirements.{{ $i }}" placeholder="{{ $i === 0 ? 'Able to swim 50 metres' : '' }}" @class(['auth-input', 'auth-input-error' => $errors->has("requirements.$i")])>
                                            <button type="button" wire:click="removeLine('requirements', {{ $i }})" class="grid h-[3.1rem] w-10 flex-none place-items-center rounded-[0.8rem] border border-line bg-white text-muted hover:border-clay hover:text-clay" aria-label="Remove"><x-line-icon name="close" :size="13" /></button>
                                        </div>
                                    @endforeach
                                </div>
                                <button type="button" wire:click="addLine('requirements')" class="mt-2 text-[12px] font-extrabold text-clay hover:underline">+ Add a requirement</button>
                                @error('requirements.*') <span class="auth-error">{{ $message }}</span> @enderror
                            </div>

                            <div class="border-t border-line pt-7">
                                <div class="flex items-end justify-between gap-3">
                                    <span class="auth-label !mb-0">Accessibility</span>
                                    <span class="text-[11px] text-muted">{{ count($accessibility) }} selected</span>
                                </div>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach ($accessibilityOptions as $key => $label)
                                        <label class="cursor-pointer" wire:key="acc-{{ $key }}">
                                            <input type="checkbox" value="{{ $key }}" wire:model.live="accessibility" class="peer sr-only">
                                            <span class="inline-flex items-center gap-1.5 rounded-full border border-line bg-white px-3.5 py-2 text-[12px] font-bold text-muted transition-colors peer-checked:border-forest peer-checked:bg-forest peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-clay/40 hover:border-forest">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="grid gap-5 border-t border-line pt-7 sm:grid-cols-2">
                                <label class="flex cursor-pointer items-center justify-between gap-4 rounded-[1rem] border border-line p-4 transition-colors hover:border-forest has-[:checked]:border-forest has-[:checked]:bg-[#E7EEE8] sm:col-span-2">
                                    <span class="flex items-center gap-3">
                                        <span class="grid h-10 w-10 flex-none place-items-center rounded-full bg-cream text-clay"><x-line-icon name="waves" :size="18" /></span>
                                        <span><span class="block text-[13.5px] font-extrabold text-ink">Depends on the weather</span><span class="block text-[11.5px] text-muted">Wind, rain or sea state can call it off.</span></span>
                                    </span>
                                    <input type="checkbox" wire:model.live="weather_dependent" class="auth-check">
                                </label>
                                @if ($weather_dependent)
                                    <label class="block sm:col-span-2">
                                        <span class="auth-label">If the weather turns</span>
                                        <textarea wire:model.blur="weather_cancellation_policy" rows="2" @class(['auth-input !h-auto py-3', 'auth-input-error' => $errors->has('weather_cancellation_policy')])></textarea>
                                        @error('weather_cancellation_policy') <span class="auth-error">{{ $message }}</span> @enderror
                                    </label>
                                @endif

                                <div class="sm:col-span-2">
                                    <span class="auth-label">Cancellation policy</span>
                                    <div class="mt-1 grid gap-2 sm:grid-cols-4">
                                        @foreach (['flexible' => 'Flexible', 'moderate' => 'Moderate', 'strict' => 'Strict', 'custom' => 'Custom'] as $key => $label)
                                            <label class="cursor-pointer">
                                                <input type="radio" value="{{ $key }}" wire:model.live="cancellation_type" class="peer sr-only">
                                                <span class="block rounded-[0.8rem] border border-line bg-white px-3 py-2.5 text-center text-[12.5px] font-extrabold text-ink transition-colors peer-checked:border-clay peer-checked:bg-[#FBE7DF] peer-checked:text-clay-dark hover:border-forest">{{ $label }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    <textarea wire:model="cancellation" rows="2" @readonly($cancellation_type !== 'custom') @class(['auth-input mt-2 !h-auto py-3', 'bg-cream text-muted' => $cancellation_type !== 'custom', 'auth-input-error' => $errors->has('cancellation')])></textarea>
                                    @error('cancellation') <span class="auth-error">{{ $message }}</span> @enderror
                                </div>

                                <label class="block sm:col-span-2">
                                    <span class="auth-label">Safety <span class="font-medium normal-case tracking-normal text-muted">(optional)</span></span>
                                    <textarea wire:model.blur="safety_instructions" rows="3" placeholder="Life jackets are worn on deck. Tell us about any medical conditions before you start." @class(['auth-input !h-auto py-3 leading-relaxed', 'auth-input-error' => $errors->has('safety_instructions')])></textarea>
                                    @error('safety_instructions') <span class="auth-error">{{ $message }}</span> @enderror
                                </label>
                            </div>
                        </div>
                    @endif

                    {{-- ── 7. Times & price ── --}}
                    @if ($step === 7)
                        <div class="mt-7 space-y-8">
                            <p class="rounded-[0.9rem] bg-cream px-4 py-3 text-[12.5px] leading-relaxed text-muted">
                                Guests pay per person and book a session. Set your weekly times once and we open sessions ahead for you. Price, times and sessions go live as soon as you save, without a new review.
                            </p>

                            <div class="grid gap-5 sm:grid-cols-[1.5fr_1fr]">
                                <div class="block">
                                    <span class="auth-label">Price per person</span>
                                    {{-- Currency sits with the price; one currency for the whole listing. --}}
                                    <div @class(['flex overflow-hidden rounded-[0.8rem] border bg-surface transition-colors focus-within:border-forest', 'border-clay' => $errors->has('base_price') || $errors->has('currency'), 'border-line' => ! ($errors->has('base_price') || $errors->has('currency'))])>
                                        <select wire:model.live="currency" aria-label="Currency" class="w-[6.5rem] flex-none border-0 border-r border-line bg-cream py-0 pl-3 pr-7 text-[13px] font-extrabold text-forest focus:ring-0">
                                            @foreach ($currencies as $code => $label)
                                                <option value="{{ $code }}" title="{{ $label }}">{{ $code }}</option>
                                            @endforeach
                                        </select>
                                        <input type="number" min="1" step="any" inputmode="decimal" wire:model.blur="base_price" placeholder="{{ in_array($currency, ['USD', 'EUR', 'GBP', 'CHF', 'AUD', 'CAD']) ? '85' : '6500' }}" aria-label="Price per person" class="h-[3.1rem] min-w-0 flex-1 border-0 bg-transparent px-4 text-[15px] font-extrabold text-ink focus:ring-0">
                                    </div>
                                    <span class="mt-1.5 block text-[11px] text-muted">
                                        {{ $currencies[$currency] ?? $currency }}@if (! $currencyChosen && $country) · suggested for {{ $country }}@endif. One currency for the whole listing.
                                    </span>
                                    @error('currency') <span class="auth-error">{{ $message }}</span> @enderror
                                    @error('base_price') <span class="auth-error">{{ $message }}</span> @enderror
                                </div>

                                <label class="block">
                                    <span class="auth-label">Times are in</span>
                                    <select wire:model.live="timezone" @class(['auth-input', 'auth-input-error' => $errors->has('timezone')])>
                                        @foreach ($timezones as $zone)
                                            <option value="{{ $zone }}">{{ str_replace('_', ' ', $zone) }}</option>
                                        @endforeach
                                    </select>
                                    <span class="mt-1.5 block text-[11px] text-muted">The local time where it happens.</span>
                                    @error('timezone') <span class="auth-error">{{ $message }}</span> @enderror
                                </label>
                            </div>

                            {{-- Weekly timetable --}}
                            <div class="border-t border-line pt-7">
                                <div class="flex flex-wrap items-end justify-between gap-3">
                                    <div>
                                        <span class="auth-label !mb-0">When it runs</span>
                                        <span class="block text-[11.5px] text-muted">Each row is a start time on the days you pick. Add a row for a second departure.</span>
                                    </div>
                                    @if ($activity)
                                        <span class="rounded-full bg-cream px-3 py-1.5 text-[11px] font-bold text-forest">{{ $upcomingSessions }} upcoming {{ Str::plural('session', $upcomingSessions) }} open</span>
                                    @endif
                                </div>
                                <div class="mt-3 space-y-2">
                                    @foreach ($schedules as $r => $row)
                                        <div class="rounded-[0.9rem] bg-cream p-3" wire:key="slot-{{ $r }}">
                                            <div class="flex flex-wrap gap-1.5">
                                                @foreach ($dayNames as $day => $dayName)
                                                    <label class="cursor-pointer">
                                                        <input type="checkbox" value="{{ $day }}" wire:model.live="schedules.{{ $r }}.days" class="peer sr-only">
                                                        <span class="grid h-9 w-12 place-items-center rounded-[0.7rem] border border-line bg-white text-[12px] font-extrabold text-muted transition-colors peer-checked:border-forest peer-checked:bg-forest peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-clay/40 hover:border-forest">{{ $dayName }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                            <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-[1fr_1fr_1fr_auto]">
                                                <label class="block"><span class="mb-1 block text-[10.5px] font-extrabold text-muted">Starts</span><input type="time" wire:model.blur="schedules.{{ $r }}.start" @class(['auth-input', 'auth-input-error' => $errors->has("schedules.$r.start")])></label>
                                                <label class="block"><span class="mb-1 block text-[10.5px] font-extrabold text-muted">Ends</span><input type="time" wire:model.blur="schedules.{{ $r }}.end" @class(['auth-input', 'auth-input-error' => $errors->has("schedules.$r.end")])></label>
                                                <label class="block"><span class="mb-1 block text-[10.5px] font-extrabold text-muted">Places</span><input type="number" min="1" max="500" inputmode="numeric" wire:model.blur="schedules.{{ $r }}.capacity" @class(['auth-input', 'auth-input-error' => $errors->has("schedules.$r.capacity")])></label>
                                                <button type="button" wire:click="removeSlot({{ $r }})" class="grid h-[3.1rem] w-10 place-items-center self-end rounded-[0.8rem] border border-line bg-white text-muted hover:border-clay hover:text-clay" aria-label="Remove this time"><x-line-icon name="close" :size="13" /></button>
                                            </div>
                                            @foreach (['days', 'start', 'end', 'capacity'] as $f)
                                                @error("schedules.$r.$f") <span class="auth-error">{{ $message }}</span> @enderror
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                                <button type="button" wire:click="addSlot" class="mt-2 text-[12px] font-extrabold text-clay hover:underline">+ Add another time</button>
                                @error('schedules') <span class="auth-error">{{ $message }}</span> @enderror
                                @if ($scheduleNotice)
                                    <p class="mt-2 text-[12px] font-semibold text-[#8A6A3A]">{{ $scheduleNotice }}</p>
                                @endif
                            </div>

                            <div class="grid gap-5 border-t border-line pt-7 sm:grid-cols-3">
                                <label class="block">
                                    <span class="auth-label">Open bookings</span>
                                    <select wire:model.live="horizon_weeks" class="auth-input">
                                        @foreach ($horizonOptions as $weeks => $label)
                                            <option value="{{ $weeks }}">{{ $label }} ahead</option>
                                        @endforeach
                                    </select>
                                    @error('horizon_weeks') <span class="auth-error">{{ $message }}</span> @enderror
                                </label>
                                <label class="block">
                                    <span class="auth-label">Bookings close</span>
                                    <select wire:model.live="booking_cutoff_minutes" class="auth-input">
                                        @foreach ($cutoffOptions as $minutes => $label)
                                            <option value="{{ $minutes }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('booking_cutoff_minutes') <span class="auth-error">{{ $message }}</span> @enderror
                                </label>
                                <label class="block">
                                    <span class="auth-label">Notice you need</span>
                                    <select wire:model.live="minimum_notice_minutes" class="auth-input">
                                        @foreach ($noticeOptions as $minutes => $label)
                                            <option value="{{ $minutes }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('minimum_notice_minutes') <span class="auth-error">{{ $message }}</span> @enderror
                                </label>
                            </div>
                        </div>
                    @endif

                    {{-- ── 8. Review ── --}}
                    @if ($step === 8)
                        @php
                            $filmCount = collect($gallery_paths)->filter(fn ($p) => Media::isVideo($p))->count();
                            $times = collect($schedules)->filter(fn ($row) => filled($row['start'] ?? null) && count((array) ($row['days'] ?? [])))
                                ->map(fn ($row) => collect($dayNames)->only(array_map('intval', (array) $row['days']))->implode(', ').' at '.$row['start'])
                                ->implode(' · ');
                            $languageNames = collect($languages)->map(fn ($code) => $languageOptions[$code] ?? null)->filter()->implode(', ');
                            $planLines = collect($itinerary)->filter(fn ($item) => filled($item['title'] ?? null))->count();
                        @endphp
                        <div class="mt-7 space-y-4">
                            @foreach ([
                                1 => [$name ?: 'No name yet', collect([$categoryName, $languageNames ? 'in '.$languageNames : null, Str::limit($short_description, 110)])->filter()->implode(' · ')],
                                2 => [collect([$meeting_point, $city, $country])->filter()->implode(', ') ?: 'No meeting point yet', ($this->selectedDestination?->name ?? 'No destination chosen').(filled($latitude) ? ' · pinned at '.round((float) $latitude, 4).', '.round((float) $longitude, 4) : ' · no map pin yet')],
                                3 => [collect([($coverVideo || $cover_video_path) ? 'Cover film' : null, ($cover || $cover_path) ? 'cover photo' : 'No cover photo yet'])->filter()->implode(' + '), (count($gallery_paths) + count($new_gallery)).' gallery items, '.$filmCount.' of them '.Str::plural('film', $filmCount)],
                                4 => [$sceneCount ? $sceneCount.' '.Str::plural('moment', $sceneCount).' to live through' : 'No moments yet', $sceneCount ? $sceneFilms.' '.Str::plural('film', $sceneFilms).' · '.($sceneCount < $suggestedScenes ? 'add a few more so guests feel the whole thing' : 'the whole experience, moment by moment') : 'Guests book what they can picture. Add at least '.$suggestedScenes.' moments.'],
                                5 => [$hm($durationMinutes).' · '.($planLines ? $planLines.'-part plan' : 'no timeline yet'), collect([count(array_filter($inclusions, 'filled')).' included', count(array_filter($exclusions, 'filled')).' not included', count(array_filter($bring, 'filled')).' to bring'])->implode(' · ')],
                                6 => ['Up to '.max(1, (int) $capacity).' guests · '.($fitnessOptions[$fitness][0] ?? 'Easy').(filled($min_age) ? ' · ages '.$min_age.'+' : ' · all ages'), ($weather_dependent ? 'Weather dependent · ' : '').$cancellation],
                                7 => [$money($base_price).' per person · '.$weeklySessions.' '.Str::plural('session', $weeklySessions).' a week', ($times ?: 'No times yet').' · '.str_replace('_', ' ', $timezone)],
                            ] as $s => [$headline, $detail])
                                <div @class(['flex items-start justify-between gap-4 rounded-[1rem] border p-4', 'border-[#E9D3A6] bg-[#F6EEDD]/40' => $s === 4 && $sceneCount < $suggestedScenes, 'border-line' => ! ($s === 4 && $sceneCount < $suggestedScenes)])>
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
                                    Saving updates your price, times and sessions straight away. If you changed the story, photos, cover film, plan, meeting point or who it’s for, the experience goes back to our team for a quick review first. Moments save as you edit them.
                                @else
                                    When you submit, our team checks the experience, usually within a day. You can keep adding moments and adjusting your times while it’s in review.
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
                            @if ($activity)
                                <span class="text-[11.5px] font-semibold text-muted" wire:loading.remove><x-line-icon name="check" :size="12" /> Progress saved</span>
                                <span class="text-[11.5px] font-semibold text-clay" wire:loading>Saving…</span>
                            @endif
                        @endunless
                        </div>
                        <div class="flex flex-wrap gap-2.5">
                            @if ($managing && $step < $lastStep)
                                <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save" class="dv-btn dv-btn-ghost">
                                    <span wire:loading.remove wire:target="save">Save changes</span><span wire:loading wire:target="save">Saving…</span>
                                </button>
                            @endif
                            @if ($step < $lastStep)
                                <button type="button" wire:click="nextStep" wire:loading.attr="disabled" wire:target="nextStep" class="dv-btn dv-btn-primary">
                                    Continue <x-line-icon name="arrow" :size="15" />
                                </button>
                            @else
                                <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save" class="dv-btn dv-btn-primary min-h-[3.3rem] px-7 text-[13px]">
                                    <span wire:loading.remove wire:target="save">{{ $managing ? 'Save changes' : 'Submit for review' }}</span>
                                    <span wire:loading wire:target="save">Saving your experience…</span>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
