@php
    use App\Shared\Support\Currencies;
    use App\Shared\Support\Media;

    $statusLabel = $status ? match ($status) {
        'published' => ['Live', 'bg-forest text-white'],
        'pending_review' => ['In review', 'bg-[#F6EEDD] text-[#8A6A3A]'],
        'rejected' => ['Changes requested', 'bg-[#F6D6C9] text-clay-dark'],
        'suspended' => ['Suspended', 'bg-clay text-white'],
        default => ['Draft', 'bg-line text-ink'],
    } : null;
    $done = fn (int $s) => $managing || $s < $step;
    $lastStep = count($steps);
    $money = fn ($amount, $code = null) => Currencies::format($amount, $code ?? $currency, (float) $amount >= 1000 ? 0 : 2);
    $listingCount = collect($components)->whereIn('kind', ['stay', 'experience'])->count();
    $nights = (int) collect($components)->where('kind', 'stay')->sum(fn ($c) => (int) ($c['nights'] ?? 0));
    $kindIcon = ['stay' => 'bed', 'experience' => 'compass', 'transfer' => 'arrow', 'free' => 'leaf', 'note' => 'bookmark'];
    $primary = $chosenDestinations->first();
@endphp

<div>
    {{-- ────────────────────────────  Header  ───────────────────────────── --}}
    <section class="dv-section dv-foryou-head !pb-8">
        <div class="relative">
            <a href="{{ route('agent.dashboard') }}" class="inline-flex items-center gap-1.5 text-[12px] font-extrabold text-muted transition-colors hover:text-forest">
                <span class="rotate-180"><x-line-icon name="arrow" :size="14" /></span> Agent studio
            </a>
            <div class="mt-4 flex flex-wrap items-end justify-between gap-5">
                <div>
                    <p class="dv-eyebrow">{{ $managing ? 'Manage your journey' : 'Build a package' }}</p>
                    <h1 class="mt-2 font-dm-serif text-[clamp(2.2rem,4.5vw,3.8rem)] leading-[1] text-forest">
                        @if ($managing)
                            {{ $name }}
                        @else
                            Build a <span class="italic text-clay">journey.</span>
                        @endif
                    </h1>
                    <p class="mt-3 max-w-[38rem] text-[14px] leading-relaxed text-muted">
                        @if ($managing)
                            Update the journey, film and price any time. Changes to the story, the days or the photos are reviewed before they go live; your currency, margin and group size update straight away.
                        @else
                            Six short steps: tell the story, lay out each day with stays and experiences that are already live on HolidayHub, add film, then set your margin. Prices are always the listings’ live prices.
                        @endif
                    </p>
                </div>
                @if ($managing)
                    <div class="flex flex-wrap items-center gap-2.5">
                        @if ($statusLabel)
                            <span class="rounded-full px-3 py-1.5 text-[11px] font-extrabold {{ $statusLabel[1] }}">{{ $statusLabel[0] }}</span>
                        @endif
                        @if ($status === 'published')
                            <a href="{{ route('packages-show', $package->slug) }}" class="dv-btn dv-btn-ghost">View as a guest <x-line-icon name="arrow-up-right" :size="14" /></a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </section>

    @if ($managing)
        <x-host.moderation-note :listing="$package" class="mb-4" />
    @endif

    @if ($resumed)
        <div class="px-5 md:px-[max(5vw,calc((100vw-1350px)/2))]">
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-[1rem] border border-forest/20 bg-[#E7EEE8] px-4 py-3 text-[13px] text-forest">
                <span class="flex items-center gap-2"><x-line-icon name="check" :size="16" /> <span><b>Welcome back.</b> We’ve restored <b>{{ $name ?: 'your journey' }}</b> where you left off, at step {{ $step }}.</span></span>
                <a href="{{ route('agent.packages.create', ['new' => 1]) }}" class="text-[12px] font-extrabold text-clay hover:underline">Start a different journey instead</a>
            </div>
        </div>
    @endif

    @if (session('success'))
        <div class="px-5 md:px-[max(5vw,calc((100vw-1350px)/2))]">
            <div class="auth-alert auth-alert-ok flex items-center gap-2"><x-line-icon name="check" :size="16" /> {{ session('success') }}</div>
        </div>
    @endif

    @if ($rejection)
        <div class="px-5 md:px-[max(5vw,calc((100vw-1350px)/2))]">
            <div class="mt-3 rounded-[1rem] border border-clay/30 bg-[#FBE7DF] px-4 py-3 text-[13px] text-clay-dark"><b>Our team asked for changes:</b> {{ $rejection }}</div>
        </div>
    @endif

    <section class="dv-section !pt-6">
        <div class="grid gap-8 lg:grid-cols-[17rem_1fr]">
            {{-- ──────────────────────────  Step rail  ───────────────────────── --}}
            <aside class="lg:sticky lg:top-28 lg:self-start" data-no-reveal>
                <nav class="-mx-5 flex gap-2 overflow-x-auto px-5 pb-2 no-scrollbar lg:mx-0 lg:flex-col lg:gap-1 lg:px-0" aria-label="Package setup steps">
                    @foreach ($steps as $number => $item)
                        @php $reachable = $managing || $number <= $reached; @endphp
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
                    <p class="mt-2 font-dm-serif text-[1.25rem] leading-tight text-forest">{{ $name ?: 'Your journey' }}</p>
                    <p class="mt-0.5 text-[11.5px] text-muted">
                        {{ collect([$primary?->name, $days.' '.Str::plural('day', $days), $chosenDestinations->count() > 1 ? $chosenDestinations->count().' places' : null])->filter()->implode(' · ') }}
                    </p>
                    <dl class="mt-3 grid grid-cols-3 gap-2 border-t border-line pt-3 text-center">
                        <div><dt class="text-[9.5px] font-extrabold uppercase tracking-[0.08em] text-muted">Listings</dt><dd class="text-[14px] font-extrabold text-ink">{{ $listingCount }}</dd></div>
                        <div><dt class="text-[9.5px] font-extrabold uppercase tracking-[0.08em] text-muted">Nights</dt><dd class="text-[14px] font-extrabold text-ink">{{ $nights }}</dd></div>
                        <div><dt class="text-[9.5px] font-extrabold uppercase tracking-[0.08em] text-muted">Margin</dt><dd class="text-[14px] font-extrabold text-ink">{{ $markup_type === 'percent' ? (float) $markup_value.'%' : $symbol.' '.number_format((float) $markup_value) }}</dd></div>
                    </dl>
                    @unless ($verified)
                        <p class="mt-3 rounded-[0.7rem] bg-[#F6EEDD] px-3 py-2 text-[11px] leading-snug text-[#6B5230]"><b>Agency not verified yet.</b> You can build; you can submit once we’ve verified you.</p>
                    @endunless
                </div>
            </aside>

            {{-- ──────────────────────────  Step panel  ──────────────────────── --}}
            <div class="min-w-0">
                <div class="rounded-[1.4rem] border border-line bg-white p-5 shadow-[0_20px_60px_rgba(20,36,28,0.06)] sm:p-8" wire:key="step-{{ $step }}">
                    <p class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-clay">Step {{ $step }} of {{ $lastStep }}</p>
                    <h2 class="mt-1 font-dm-serif text-[clamp(1.7rem,3vw,2.3rem)] leading-tight text-forest">{{ $steps[$step]['label'] }}</h2>

                    {{-- ── 1. The journey ── --}}
                    @if ($step === 1)
                        <div class="mt-7 space-y-7">
                            <label class="block">
                                <span class="auth-label">Name of the journey</span>
                                <input type="text" wire:model.live.debounce.500ms="name" placeholder="Coast to Mara in Seven Days" @class(['auth-input !h-14 !text-[18px] font-dm-serif', 'auth-input-error' => $errors->has('name')])>
                                @error('name') <span class="auth-error">{{ $message }}</span> @enderror
                                <span class="mt-1.5 block text-[11px] text-muted">Web address: holidayhub/package/<b class="text-ink">{{ $slug ?: 'your-journey' }}</b></span>
                                @error('slug') <span class="auth-error">{{ $message }}</span> @enderror
                            </label>

                            <label class="block" x-data="{ count: @js(mb_strlen($short_description)) }">
                                <span class="auth-label">The hook</span>
                                <textarea wire:model.blur="short_description" x-on:input="count = $event.target.value.length" rows="2" maxlength="300" placeholder="Dhow sunsets in Lamu, a balloon over the Mara at dawn, and the last night barefoot on Diani sand." @class(['auth-input !h-auto py-3 font-dm-serif !text-[17px] leading-snug', 'auth-input-error' => $errors->has('short_description')])></textarea>
                                <span class="mt-1.5 flex justify-between text-[11px] text-muted"><span>One line that sells the whole trip. It sits under the name on cards and reels.</span><span x-text="count + ' / 300'"></span></span>
                                @error('short_description') <span class="auth-error">{{ $message }}</span> @enderror
                            </label>

                            <label class="block" x-data="{ count: @js(mb_strlen($full_description)) }">
                                <span class="auth-label">The story</span>
                                <textarea wire:model.blur="full_description" x-on:input="count = $event.target.value.length" rows="7" placeholder="Who it’s for, how the days flow from one place to the next, the moment people talk about afterwards, and why you put it together this way…" @class(['auth-input !h-auto py-3 leading-relaxed', 'auth-input-error' => $errors->has('full_description')])></textarea>
                                <span class="mt-1.5 flex justify-between text-[11px] text-muted"><span>Write for travellers, not search engines.</span><span x-text="count + ' / 5000'"></span></span>
                                @error('full_description') <span class="auth-error">{{ $message }}</span> @enderror
                            </label>

                            <div class="border-t border-line pt-7">
                                <span class="auth-label !mb-0">Where it goes</span>
                                <span class="mb-3 block text-[11.5px] text-muted">The first destination is the one guests find it under; add the others the journey visits.</span>

                                @if ($chosenDestinations->isNotEmpty())
                                    <div class="mb-3 flex flex-wrap gap-2">
                                        @foreach ($chosenDestinations as $destination)
                                            <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-[12px] font-bold {{ $loop->first ? 'border-forest bg-forest text-white' : 'border-line bg-white text-ink' }}" wire:key="dest-chip-{{ $destination->id }}">
                                                <x-line-icon name="map" :size="13" /> {{ $destination->name }}
                                                @if ($loop->first)<span class="text-[10px] font-extrabold uppercase tracking-[0.1em] text-white/70">main</span>@endif
                                                <button type="button" wire:click="removeDestination({{ $destination->id }})" class="opacity-70 hover:opacity-100" aria-label="Remove {{ $destination->name }}"><x-line-icon name="close" :size="12" /></button>
                                            </span>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="relative">
                                    <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-muted"><x-line-icon name="search" :size="16" /></span>
                                    <input type="search" wire:model.live.debounce.300ms="destinationSearch" placeholder="{{ $chosenDestinations->isEmpty() ? 'Search destinations: Diani, Maasai Mara, Kyoto…' : 'Add another destination…' }}" @class(['auth-input !pl-11', 'auth-input-error' => $errors->has('destination_id')]) autocomplete="off">
                                </div>
                                @if (trim($destinationSearch) !== '')
                                    <div class="mt-2 overflow-hidden rounded-[0.9rem] border border-line bg-white">
                                        @forelse ($this->destinationResults as $result)
                                            <button type="button" wire:click="addDestination({{ $result->id }})" wire:key="dres-{{ $result->id }}" class="flex w-full items-center gap-3 border-b border-line px-4 py-2.5 text-left transition-colors last:border-0 hover:bg-cream">
                                                <x-line-icon name="map" :size="15" />
                                                <span class="min-w-0 flex-1"><span class="block truncate text-[13px] font-extrabold text-ink">{{ $result->name }}</span><span class="block truncate text-[11px] text-muted">{{ $result->country }}</span></span>
                                                <span class="text-[11px] font-extrabold text-clay">Add</span>
                                            </button>
                                        @empty
                                            <p class="px-4 py-3 text-[12px] text-muted">No destination called “{{ trim($destinationSearch) }}” on HolidayHub. Packages are built from places that already have listings.</p>
                                        @endforelse
                                    </div>
                                @endif
                                @if ($suggestedDestinations->isNotEmpty())
                                    <p class="mt-3 flex flex-wrap items-center gap-2 text-[11.5px] text-muted">
                                        In your journey:
                                        @foreach ($suggestedDestinations as $suggestion)
                                            <button type="button" wire:click="addDestination({{ $suggestion->id }})" class="rounded-full bg-cream px-2.5 py-1 text-[11px] font-extrabold text-forest hover:bg-sand">+ {{ $suggestion->name }}</button>
                                        @endforeach
                                    </p>
                                @endif
                                @error('destination_id') <span class="auth-error">{{ $message }}</span> @enderror
                            </div>

                            <div class="border-t border-line pt-7">
                                <span class="auth-label">How many days</span>
                                <div class="flex flex-wrap items-center gap-3">
                                    <div @class(['flex w-[10rem] overflow-hidden rounded-[0.8rem] border bg-surface focus-within:border-forest', 'border-clay' => $errors->has('duration_days'), 'border-line' => ! $errors->has('duration_days')])>
                                        <input type="number" min="1" max="30" inputmode="numeric" wire:model.live.debounce.500ms="duration_days" aria-label="Days" class="h-[3.1rem] min-w-0 flex-1 border-0 bg-transparent px-4 text-[15px] font-extrabold text-ink focus:ring-0">
                                        <span class="grid flex-none place-items-center border-l border-line bg-cream px-3 text-[12px] font-extrabold text-forest">days</span>
                                    </div>
                                    <span class="text-[12px] text-muted">{{ max(0, $days - 1) }} {{ Str::plural('night', max(0, $days - 1)) }}. You’ll fill each day next.</span>
                                </div>
                                @error('duration_days') <span class="auth-error">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    @endif

                    {{-- ── 2. Day by day ── --}}
                    @if ($step === 2)
                        <div class="mt-7 space-y-5">
                            <p class="rounded-[0.9rem] bg-cream px-4 py-3 text-[12.5px] leading-relaxed text-muted">
                                <b class="text-forest">Build each day from what’s live on HolidayHub.</b> Stays are booked for the nights you set from that day; experiences on the day itself. Their prices come live from the listings for the guest’s dates. Add transfers, free time or a note where it helps; you price those yourself.
                            </p>

                            @foreach (range(1, $days) as $day)
                                @php $rows = collect($components)->filter(fn ($row) => (int) $row['day'] === $day); @endphp
                                <div class="rounded-[1.1rem] border border-line" wire:key="day-{{ $day }}">
                                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line bg-cream/60 px-4 py-3">
                                        <p class="flex items-center gap-3">
                                            <span class="grid h-9 w-9 place-items-center rounded-full bg-forest font-dm-serif text-[1rem] text-white">{{ $day }}</span>
                                            <span><span class="block text-[13.5px] font-extrabold text-ink">Day {{ $day }}</span><span class="block text-[11px] text-muted">{{ $rows->isEmpty() ? 'Nothing planned yet' : $rows->count().' '.Str::plural('part', $rows->count()) }}</span></span>
                                        </p>
                                        <div class="flex flex-wrap gap-1.5">
                                            <button type="button" wire:click="openPicker({{ $day }})" @class(['rounded-full px-3 py-1.5 text-[11.5px] font-extrabold transition-colors', 'bg-forest text-white' => $addingDay === $day, 'bg-white text-forest ring-1 ring-line hover:ring-forest' => $addingDay !== $day])>+ Stay or experience</button>
                                            <button type="button" wire:click="addExtra({{ $day }}, 'transfer')" class="rounded-full bg-white px-3 py-1.5 text-[11.5px] font-extrabold text-muted ring-1 ring-line hover:text-forest hover:ring-forest">+ Transfer</button>
                                            <button type="button" wire:click="addExtra({{ $day }}, 'free')" class="rounded-full bg-white px-3 py-1.5 text-[11.5px] font-extrabold text-muted ring-1 ring-line hover:text-forest hover:ring-forest">+ Free time</button>
                                            <button type="button" wire:click="addExtra({{ $day }}, 'note')" class="rounded-full bg-white px-3 py-1.5 text-[11.5px] font-extrabold text-muted ring-1 ring-line hover:text-forest hover:ring-forest">+ Note</button>
                                        </div>
                                    </div>

                                    <div class="space-y-2.5 p-3 sm:p-4">
                                        @foreach ($rows as $i => $row)
                                            @php $card = $cards[$i] ?? ['listing' => false, 'missing' => false]; @endphp
                                            <div class="rounded-[0.9rem] border border-line bg-white p-3" wire:key="comp-{{ $i }}-{{ $row['kind'] }}-{{ $row['listing_id'] ?? 'x' }}">
                                                <div class="flex items-start gap-3">
                                                    @if ($card['listing'])
                                                        <img src="{{ $card['image'] }}" alt="" class="h-16 w-20 flex-none rounded-[0.7rem] object-cover">
                                                    @else
                                                        <span class="grid h-16 w-16 flex-none place-items-center rounded-[0.7rem] bg-cream text-clay"><x-line-icon :name="$kindIcon[$row['kind']] ?? 'bookmark'" :size="20" /></span>
                                                    @endif
                                                    <div class="min-w-0 flex-1">
                                                        <p class="text-[10px] font-extrabold uppercase tracking-[0.12em] text-clay">{{ $card['listing'] ? $card['kind'] : ($kinds[$row['kind']] ?? 'Extra') }}@if ($card['listing'] && $card['place']) · <span class="text-muted">{{ $card['place'] }}</span>@endif</p>
                                                        @if ($card['listing'])
                                                            <p class="truncate text-[14px] font-extrabold text-ink">{{ $card['name'] }}@if ($card['detail']) <span class="font-semibold text-muted">· {{ $card['detail'] }}</span>@endif</p>
                                                            <p class="text-[11.5px] text-muted">From {{ $card['from'] }} · live price for the guest’s dates</p>
                                                        @elseif ($card['missing'])
                                                            <p class="text-[13px] font-bold text-clay-dark">This listing is no longer on HolidayHub. Remove it and choose another.</p>
                                                        @else
                                                            <input type="text" wire:model.blur="components.{{ $i }}.title" placeholder="{{ $row['kind'] === 'note' ? 'A note for the day: e.g. Pack light for the bush plane' : 'Title' }}" class="auth-input !h-10 font-bold" aria-label="Title">
                                                        @endif
                                                    </div>
                                                    <div class="flex flex-none gap-1">
                                                        <button type="button" wire:click="moveComponent({{ $i }}, -1)" class="grid h-9 w-8 place-items-center rounded-[0.7rem] border border-line bg-white text-muted hover:border-forest hover:text-forest" aria-label="Move up"><span class="-rotate-90"><x-line-icon name="arrow" :size="12" /></span></button>
                                                        <button type="button" wire:click="moveComponent({{ $i }}, 1)" class="grid h-9 w-8 place-items-center rounded-[0.7rem] border border-line bg-white text-muted hover:border-forest hover:text-forest" aria-label="Move down"><span class="rotate-90"><x-line-icon name="arrow" :size="12" /></span></button>
                                                        <button type="button" wire:click="removeComponent({{ $i }})" class="grid h-9 w-8 place-items-center rounded-[0.7rem] border border-line bg-white text-muted hover:border-clay hover:text-clay" aria-label="Remove"><x-line-icon name="close" :size="12" /></button>
                                                    </div>
                                                </div>

                                                <div class="mt-2.5 flex flex-wrap items-end gap-2 pl-0 sm:pl-[5.75rem]">
                                                    <label class="block">
                                                        <span class="mb-1 block text-[10px] font-extrabold uppercase tracking-[0.08em] text-muted">Day</span>
                                                        <select wire:model.live="components.{{ $i }}.day" class="auth-input !h-9 !w-[5.5rem] !py-0 text-[12.5px]">
                                                            @foreach (range(1, $days) as $d)
                                                                <option value="{{ $d }}">Day {{ $d }}</option>
                                                            @endforeach
                                                        </select>
                                                    </label>
                                                    @if ($row['kind'] === 'stay')
                                                        <label class="block">
                                                            <span class="mb-1 block text-[10px] font-extrabold uppercase tracking-[0.08em] text-muted">Nights</span>
                                                            <input type="number" min="1" max="30" wire:model.live.debounce.400ms="components.{{ $i }}.nights" class="auth-input !h-9 !w-[5.5rem] text-[12.5px] font-extrabold">
                                                        </label>
                                                        <span class="pb-2 text-[11px] text-muted">Day {{ $row['day'] }} to day {{ (int) $row['day'] + max(1, (int) $row['nights']) }}</span>
                                                    @elseif ($row['kind'] === 'experience' && ! empty($card['options']))
                                                        <label class="block">
                                                            <span class="mb-1 block text-[10px] font-extrabold uppercase tracking-[0.08em] text-muted">Option</span>
                                                            <select wire:model.live="components.{{ $i }}.option_id" class="auth-input !h-9 !py-0 text-[12.5px]">
                                                                <option value="">Any session</option>
                                                                @foreach ($card['options'] as $option)
                                                                    <option value="{{ $option['id'] }}">{{ $option['name'] }}</option>
                                                                @endforeach
                                                            </select>
                                                        </label>
                                                    @elseif ($row['kind'] === 'transfer' || $row['kind'] === 'free')
                                                        <label class="block">
                                                            <span class="mb-1 block text-[10px] font-extrabold uppercase tracking-[0.08em] text-muted">Your price · per booking</span>
                                                            <div class="flex h-9 overflow-hidden rounded-[0.8rem] border border-line bg-surface focus-within:border-forest">
                                                                <span class="grid flex-none place-items-center border-r border-line bg-cream px-2.5 text-[11px] font-extrabold text-forest">{{ $currency }}</span>
                                                                <input type="number" min="0" step="any" wire:model.blur="components.{{ $i }}.price" placeholder="{{ $row['kind'] === 'free' ? '0' : '60' }}" class="w-[7rem] border-0 bg-transparent px-3 text-[12.5px] font-extrabold text-ink focus:ring-0">
                                                            </div>
                                                        </label>
                                                    @endif
                                                    @if (! in_array($row['kind'], ['stay', 'experience'], true))
                                                        <label class="block min-w-[12rem] flex-1">
                                                            <span class="mb-1 block text-[10px] font-extrabold uppercase tracking-[0.08em] text-muted">Details <span class="normal-case tracking-normal">(optional)</span></span>
                                                            <input type="text" wire:model.blur="components.{{ $i }}.description" placeholder="{{ $row['kind'] === 'transfer' ? 'Air-conditioned van from Diani to Watamu, about 3 hours' : 'What guests should know' }}" class="auth-input !h-9 text-[12.5px]">
                                                        </label>
                                                    @endif
                                                </div>
                                                @foreach (['day', 'nights', 'title', 'price', 'description'] as $f)
                                                    @error("components.$i.$f") <span class="auth-error">{{ $message }}</span> @enderror
                                                @endforeach
                                                @error("components.$i") <span class="auth-error">{{ $message }}</span> @enderror
                                            </div>
                                        @endforeach

                                        @if ($rows->isEmpty() && $addingDay !== $day)
                                            <p class="rounded-[0.9rem] border border-dashed border-line px-4 py-4 text-center text-[12px] text-muted">Add a stay, an experience, a transfer, free time or a note.</p>
                                        @endif

                                        {{-- Listing picker --}}
                                        @if ($addingDay === $day)
                                            <div class="rounded-[1rem] border-2 border-forest/20 bg-[#F4F7F4] p-3 sm:p-4">
                                                <div class="grid gap-2 sm:grid-cols-[1fr_12rem]">
                                                    <div class="relative">
                                                        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-muted"><x-line-icon name="search" :size="16" /></span>
                                                        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search stays and experiences by name or place…" class="auth-input !pl-11" autocomplete="off" autofocus>
                                                    </div>
                                                    <select wire:model.live="searchDestination" class="auth-input" aria-label="Destination">
                                                        <option value="">Anywhere</option>
                                                        @foreach ($allDestinations as $d)
                                                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="mt-2 flex flex-wrap gap-1.5">
                                                    @foreach ($searchTypes as $key => $label)
                                                        <label class="cursor-pointer">
                                                            <input type="radio" value="{{ $key }}" wire:model.live="searchType" class="peer sr-only">
                                                            <span class="inline-flex rounded-full border border-line bg-white px-3 py-1.5 text-[11.5px] font-bold text-muted transition-colors peer-checked:border-forest peer-checked:bg-forest peer-checked:text-white hover:border-forest">{{ $label }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>

                                                <div class="mt-3 grid gap-2.5 md:grid-cols-2" wire:loading.class="opacity-60" wire:target="search,searchType,searchDestination">
                                                    @forelse ($this->listingResults as $result)
                                                        <div class="flex gap-3 rounded-[0.9rem] border border-line bg-white p-2.5" wire:key="res-{{ $result['key'] }}">
                                                            <img src="{{ $result['image'] }}" alt="" class="h-[4.5rem] w-[4.5rem] flex-none rounded-[0.7rem] object-cover" loading="lazy">
                                                            <div class="min-w-0 flex-1">
                                                                <p class="text-[9.5px] font-extrabold uppercase tracking-[0.12em] text-clay">{{ $result['kind'] }}@if ($result['place']) · <span class="text-muted">{{ $result['place'] }}</span>@endif</p>
                                                                <p class="truncate text-[13px] font-extrabold text-ink">{{ $result['name'] }}</p>
                                                                <p class="text-[11px] text-muted">From <b class="text-ink">{{ $result['from'] }}</b> / {{ $result['unit'] }}@if ($result['sub']) · {{ $result['sub'] }}@endif</p>
                                                                <div class="mt-1.5 flex flex-wrap gap-1">
                                                                    @if ($result['type'] === 'hotel')
                                                                        @forelse ($result['options'] as $room)
                                                                            <button type="button" wire:click="addListing('room_type', {{ $room['id'] }})" class="rounded-full bg-forest px-2.5 py-1 text-[10.5px] font-extrabold text-white hover:bg-forest-light">+ {{ $room['label'] }} · {{ Currencies::format($room['price'], $room['currency']) }}</button>
                                                                        @empty
                                                                            <span class="text-[11px] text-muted">No rooms listed yet.</span>
                                                                        @endforelse
                                                                    @elseif ($result['type'] === 'activity')
                                                                        <button type="button" wire:click="addListing('activity', {{ $result['id'] }})" class="rounded-full bg-forest px-2.5 py-1 text-[10.5px] font-extrabold text-white hover:bg-forest-light">+ Add to day {{ $day }}</button>
                                                                        @foreach ($result['options'] as $option)
                                                                            <button type="button" wire:click="addListing('activity', {{ $result['id'] }}, {{ $option['id'] }})" class="rounded-full bg-cream px-2.5 py-1 text-[10.5px] font-extrabold text-forest hover:bg-sand">+ {{ $option['label'] }}</button>
                                                                        @endforeach
                                                                    @else
                                                                        <button type="button" wire:click="addListing('{{ $result['type'] }}', {{ $result['id'] }})" class="rounded-full bg-forest px-2.5 py-1 text-[10.5px] font-extrabold text-white hover:bg-forest-light">+ Stay from day {{ $day }}</button>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @empty
                                                        <p class="col-span-full rounded-[0.9rem] bg-white px-4 py-6 text-center text-[12px] text-muted">No live stays or experiences match. Try another name, type or “Anywhere”.</p>
                                                    @endforelse
                                                </div>
                                                <button type="button" wire:click="openPicker({{ $day }})" class="mt-3 text-[12px] font-extrabold text-muted hover:text-clay">Close</button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                            @error('components') <span class="auth-error">{{ $message }}</span> @enderror

                            <div class="grid gap-7 border-t border-line pt-7 lg:grid-cols-2 lg:gap-5">
                                @foreach ([
                                    'inclusions' => ['Also included', 'Beyond the listings: park fees, a SIM card, your 24/7 line.', 'check', 'bg-forest text-white', 'Your agent on call 24/7'],
                                    'exclusions' => ['Not included', 'So nobody is surprised: flights, visas, tips.', 'close', 'bg-clay text-white', 'International flights'],
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
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- ── 3. Photos & film ── --}}
                    @if ($step === 3)
                        <div class="mt-7 space-y-8">
                            <div class="flex flex-wrap items-center justify-between gap-3 rounded-[0.9rem] bg-cream px-4 py-3">
                                <p class="max-w-[34rem] text-[12.5px] leading-relaxed text-muted"><b class="text-forest">HolidayHub leads with film.</b> A short cover film of the journey does more than any photo. No footage of your own yet? Start from the hosts’ photos and films of the places in your journey.</p>
                                <button type="button" wire:click="useListingMedia" wire:loading.attr="disabled" wire:target="useListingMedia" class="dv-btn dv-btn-ghost !min-h-[2.6rem] flex-none bg-white"><x-line-icon name="grid" :size="14" /> Use media from the listings</button>
                            </div>
                            @if ($mediaNotice)
                                <p class="-mt-4 text-[12px] font-semibold text-forest">{{ $mediaNotice }}</p>
                            @endif

                            <div class="grid gap-5 lg:grid-cols-[1.2fr_1fr]">
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

                            <div>
                                <div class="flex flex-wrap items-end justify-between gap-3">
                                    <span class="auth-label !mb-0">Gallery · photos &amp; films</span>
                                    @php $mediaCount = count($gallery_paths) + count($new_gallery); $filmCount = collect($gallery_paths)->filter(fn ($p) => Media::isVideo($p))->count(); @endphp
                                    <span class="text-[11px] text-muted">{{ $mediaCount }} items · {{ $filmCount }} {{ Str::plural('film', $filmCount) }} · one or two of every place in the journey</span>
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

                    {{-- ── 4. The journey reel ── --}}
                    @if ($step === 4)
                        <x-host.walkthrough-step
                            :owner="$package"
                            noun="journey"
                            headline="Let guests travel the whole journey before they book it, stop by stop."
                            story="Guests play your reel full screen, one stop after another, like stories: the arrival, the road between places, the room with the view, the moment on the water, the last dinner. It’s the part of the page that makes them book."
                            :tips="[
                                ['In the order they travel', 'Arrival first, the farewell last. Drag the stops into the order the days run.'],
                                ['5–20 seconds each', 'One film or photo per stop: the place, the people, the moment. Photos hold for five seconds.'],
                                ['Every place, once', 'A stop for each stay and experience, plus the road in between, so guests feel the flow.'],
                            ]"
                            spaces="the arrival, the road, where they stay, the experiences and the farewell"
                            :suggested="$suggestedScenes"
                        />
                    @endif

                    {{-- ── 5. Price & guests ── --}}
                    @if ($step === 5)
                        <div class="mt-7 space-y-8">
                            <p class="rounded-[0.9rem] bg-cream px-4 py-3 text-[12.5px] leading-relaxed text-muted">
                                Guests pay the <b class="text-forest">live prices of the listings</b> for their dates, converted into your package currency, plus <b class="text-forest">your margin</b>, which is recorded as your commission. Transfers and other extras are charged at your own price. These settings go live as soon as you save, without a new review.
                            </p>

                            <div class="grid gap-5 sm:grid-cols-2">
                                <label class="block">
                                    <span class="auth-label">Package currency</span>
                                    <select wire:model.live="currency" @class(['auth-input', 'auth-input-error' => $errors->has('currency')])>
                                        @foreach ($currencies as $code => $label)
                                            <option value="{{ $code }}">{{ $code }} · {{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <span class="mt-1.5 block text-[11px] text-muted">Guests see and pay the whole journey in this currency.</span>
                                    @error('currency') <span class="auth-error">{{ $message }}</span> @enderror
                                </label>

                                <div class="block">
                                    <span class="auth-label">Your margin</span>
                                    <div @class(['flex overflow-hidden rounded-[0.8rem] border bg-surface transition-colors focus-within:border-forest', 'border-clay' => $errors->has('markup_value'), 'border-line' => ! $errors->has('markup_value')])>
                                        <select wire:model.live="markup_type" aria-label="Margin type" class="w-[8.5rem] flex-none border-0 border-r border-line bg-cream py-0 pl-3 pr-7 text-[13px] font-extrabold text-forest focus:ring-0">
                                            <option value="percent">Percent</option>
                                            <option value="fixed">Fixed {{ $currency }}</option>
                                        </select>
                                        <input type="number" min="0" step="any" inputmode="decimal" wire:model.live.debounce.500ms="markup_value" aria-label="Margin" class="h-[3.1rem] min-w-0 flex-1 border-0 bg-transparent px-4 text-[15px] font-extrabold text-ink focus:ring-0">
                                        <span class="grid flex-none place-items-center border-l border-line bg-cream px-3 text-[12px] font-extrabold text-forest">{{ $markup_type === 'percent' ? '%' : $symbol }}</span>
                                    </div>
                                    <span class="mt-1.5 block text-[11px] text-muted">{{ $markup_type === 'percent' ? 'Added on top of the listings’ live prices.' : 'Added once per booking, whatever the group size.' }}</span>
                                    @error('markup_value') <span class="auth-error">{{ $message }}</span> @enderror
                                </div>

                                <label class="block">
                                    <span class="auth-label">Smallest group</span>
                                    <input type="number" min="1" max="50" inputmode="numeric" wire:model.live.debounce.500ms="min_guests" @class(['auth-input text-center !text-[16px] font-extrabold', 'auth-input-error' => $errors->has('min_guests')])>
                                    @error('min_guests') <span class="auth-error">{{ $message }}</span> @enderror
                                </label>
                                <label class="block">
                                    <span class="auth-label">Largest group</span>
                                    <input type="number" min="1" max="50" inputmode="numeric" wire:model.live.debounce.500ms="max_guests" @class(['auth-input text-center !text-[16px] font-extrabold', 'auth-input-error' => $errors->has('max_guests')])>
                                    @error('max_guests') <span class="auth-error">{{ $message }}</span> @enderror
                                </label>
                            </div>

                            {{-- Live sample quote --}}
                            <div class="rounded-[1.2rem] border border-line">
                                <div class="flex flex-wrap items-end justify-between gap-3 border-b border-line bg-cream/60 px-4 py-3 sm:px-5">
                                    <div>
                                        <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-clay">Live sample quote</p>
                                        <p class="text-[12px] text-muted">What a guest would pay today, priced from the listings.</p>
                                    </div>
                                    <div class="flex flex-wrap items-end gap-2">
                                        <label class="block"><span class="mb-1 block text-[10px] font-extrabold uppercase tracking-[0.08em] text-muted">Start</span><input type="date" wire:model.live="sampleDate" min="{{ today()->addDay()->toDateString() }}" class="auth-input !h-10 text-[12.5px]"></label>
                                        <label class="block"><span class="mb-1 block text-[10px] font-extrabold uppercase tracking-[0.08em] text-muted">Guests</span><input type="number" min="1" max="50" wire:model.live.debounce.400ms="sampleGuests" class="auth-input !h-10 !w-[5rem] text-center text-[12.5px] font-extrabold"></label>
                                    </div>
                                </div>
                                @if ($departures)
                                    <div class="flex flex-wrap items-center gap-1.5 border-b border-line px-4 py-2.5 text-[11px] text-muted sm:px-5">
                                        Next dates with everything available:
                                        @foreach ($departures as $departure)
                                            <button type="button" wire:click="$set('sampleDate', '{{ $departure['date'] }}')" @class(['rounded-full px-2.5 py-1 font-extrabold', 'bg-forest text-white' => $sampleDate === $departure['date'], 'bg-cream text-forest hover:bg-sand' => $sampleDate !== $departure['date']])>{{ \Carbon\Carbon::parse($departure['date'])->format('D j M') }}</button>
                                        @endforeach
                                    </div>
                                @endif

                                @if ($quote)
                                    @include('packages::partials.quote-table', ['quote' => $quote, 'agentView' => true])
                                @else
                                    <p class="px-5 py-6 text-center text-[12.5px] text-muted">Add stays and experiences in step 2 to see a quote.</p>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- ── 6. Review ── --}}
                    @if ($step === 6)
                        @php
                            $filmCount = collect($gallery_paths)->filter(fn ($p) => Media::isVideo($p))->count();
                            $extras = collect($components)->whereNotIn('kind', ['stay', 'experience'])->count();
                            $unmet = collect($requirements)->reject(fn ($r) => $r['met']);
                        @endphp
                        <div class="mt-7 space-y-4">
                            @foreach ([
                                1 => [$name ?: 'No name yet', collect([$chosenDestinations->pluck('name')->implode(', ') ?: 'No destination yet', $days.' '.Str::plural('day', $days), Str::limit($short_description, 90)])->filter()->implode(' · ')],
                                2 => [$listingCount.' '.Str::plural('listing', $listingCount).' · '.$nights.' '.Str::plural('night', $nights), $extras.' '.Str::plural('extra', $extras).' (transfers, free time, notes) · '.count(array_filter($inclusions, 'filled')).' also included'],
                                3 => [collect([($coverVideo || $cover_video_path) ? 'Cover film' : null, ($cover || $cover_path) ? 'cover photo' : null])->filter()->implode(' + ') ?: 'No cover yet', (count($gallery_paths) + count($new_gallery)).' gallery items, '.$filmCount.' of them '.Str::plural('film', $filmCount)],
                                4 => [$sceneCount ? $sceneCount.' '.Str::plural('stop', $sceneCount).' on the reel' : 'No reel yet', $sceneCount < $suggestedScenes ? 'Optional, but journeys with a reel book better. Try '.$suggestedScenes.' stops.' : 'The whole journey, stop by stop'],
                                5 => [$quote ? Currencies::format($quote['per_person'], $quote['currency']).' per person · sample for '.$quote['guests'] : $currency, ($markup_type === 'percent' ? (float) $markup_value.'% margin' : Currencies::format($markup_value, $currency).' margin per booking').' · '.(int) $min_guests.'–'.(int) $max_guests.' guests'],
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

                            @if ($requirements)
                                <div class="rounded-[1rem] border border-line p-4">
                                    <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-muted">Before it can go live</p>
                                    <ul class="mt-3 grid gap-2 sm:grid-cols-2">
                                        @foreach ($requirements as $key => $requirement)
                                            <li class="flex items-start gap-2.5 text-[12.5px]">
                                                <span @class(['mt-0.5 grid h-5 w-5 flex-none place-items-center rounded-full', 'bg-forest text-white' => $requirement['met'], 'bg-[#F6D6C9] text-clay-dark' => ! $requirement['met']])><x-line-icon :name="$requirement['met'] ? 'check' : 'close'" :size="11" /></span>
                                                <span><b class="text-ink">{{ $requirement['label'] }}</b>@unless ($requirement['met'])<span class="block text-[11.5px] text-muted">{{ $requirement['hint'] }}</span>@endunless</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @unless ($verified)
                                <div class="flex items-start gap-3 rounded-[0.9rem] border border-[#E9D3A6] bg-[#F6EEDD] px-4 py-3 text-[12.5px] text-[#6B5230]">
                                    <x-line-icon name="clock" :size="16" class="mt-0.5 flex-none text-clay" />
                                    <p><b class="text-ink">Your agency is still being verified.</b> Your journey is saved and you can keep refining it. Submitting for review unlocks as soon as our team verifies your agency.</p>
                                </div>
                            @endunless

                            <p class="rounded-[0.9rem] bg-cream px-4 py-3 text-[12.5px] leading-relaxed text-muted">
                                @if ($managing)
                                    Saving updates your currency, margin and group size straight away. If you changed the story, the days or the photos, the journey goes back to our team for a quick review first. The reel saves as you edit it.
                                @else
                                    When you submit, our team checks the journey, usually within a day. You can keep adding reel stops while it’s in review.
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
                                @if ($package)
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
                                    <span wire:loading.remove wire:target="save">{{ $managing && ! in_array($status, ['draft', 'rejected'], true) ? 'Save changes' : ($verified ? 'Submit for review' : 'Save · submit once verified') }}</span>
                                    <span wire:loading wire:target="save">Saving your journey…</span>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
