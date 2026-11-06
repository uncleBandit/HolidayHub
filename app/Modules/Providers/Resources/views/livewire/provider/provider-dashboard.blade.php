<div>
    @php
        $max = max(1, $months->max('total'));
        $statusStyles = [
            'pending' => 'bg-[#F6D6C9] text-clay-dark',
            'confirmed' => 'bg-forest/10 text-forest',
            'checked_in' => 'bg-forest text-white',
            'checked_out' => 'bg-line text-muted',
            'cancelled' => 'bg-line/60 text-muted line-through',
        ];
        $listingStatus = [
            'published' => ['Live', 'bg-forest text-white'],
            'review' => ['In review', 'bg-[#F6EEDD] text-[#8A6A3A]'],
            'draft' => ['Draft', 'bg-white/90 text-ink'],
            'rejected' => ['Changes requested', 'bg-[#F6D6C9] text-clay-dark'],
            'suspended' => ['Suspended', 'bg-clay text-white'],
        ];
    @endphp

    {{-- ────────────────────────────  Header  ───────────────────────────── --}}
    <section class="dv-section dv-foryou-head pb-8">
        <div class="relative flex flex-wrap items-end justify-between gap-8">
            <div class="max-w-[46rem]">
                <p class="dv-eyebrow dv-magic-in" style="--i: 0">Host studio</p>
                <h1 class="dv-magic-in mt-3 font-dm-serif text-[clamp(2.4rem,5vw,4.4rem)] leading-[0.98] tracking-[-0.02em] text-forest" style="--i: 1">
                    {{ $greeting }},<br><span class="italic text-clay">{{ $provider->company_name ?: Auth::user()->name }}.</span>
                </h1>
                <p class="dv-magic-in mt-4 text-[14px] leading-relaxed text-muted" style="--i: 2">
                    @if ($kpis['pending'])
                        <b class="text-ink">{{ $kpis['pending'] }} {{ Str::plural('request', $kpis['pending']) }}</b> waiting for your answer ·
                    @endif
                    {{ $kpis['arrivals'] }} {{ Str::plural('arrival', $kpis['arrivals']) }} in the next 30 days · {{ $kpis['live'] }} of {{ $kpis['listings'] }} listings live
                </p>
            </div>

            <div class="dv-magic-in flex flex-wrap gap-2.5" style="--i: 3">
                {{-- The header clips its overflow (for the background glows), so the
                     menu is teleported to <body> and anchored to the button. --}}
                <div x-data="{ open: false }" x-on:keydown.escape.window="open = false">
                    <button type="button" x-ref="addListing" x-on:click="open = ! open" class="dv-btn dv-btn-primary" :aria-expanded="open" aria-haspopup="menu">
                        <x-line-icon name="star" :size="16" /> Add a listing
                        <span class="transition-transform duration-200" :class="open && 'rotate-180'"><x-line-icon name="chevron-down" :size="14" /></span>
                    </button>
                    <template x-teleport="body">
                        <div
                            x-show="open"
                            x-cloak
                            x-anchor.bottom-end.offset.8="$refs.addListing"
                            x-transition.origin.top.right
                            x-on:click.outside="if (! $refs.addListing.contains($event.target)) open = false"
                            class="z-[90] w-64 overflow-hidden rounded-[1rem] border border-line bg-white py-2 shadow-[0_20px_60px_rgba(20,36,28,0.18)]"
                            role="menu"
                        >
                            @foreach ([['Hotel', 'Rooms, suites and room types', 'provider.hotel-create', 'bed'], ['Villa', 'A whole home with its own pool', 'provider.villa-create', 'waves'], ['B&B', 'Rooms with breakfast', 'provider.bedandbreakfast-create', 'utensils'], ['Experience', 'A tour, safari, class or adventure', 'provider.activity-create', 'compass']] as [$label, $sub, $route, $icon])
                                <a href="{{ route($route) }}" class="flex items-center gap-3 px-4 py-2.5 transition-colors hover:bg-cream" role="menuitem">
                                    <span class="grid h-9 w-9 flex-none place-items-center rounded-full bg-cream text-clay"><x-line-icon :name="$icon" :size="16" /></span>
                                    <span>
                                        <span class="block text-[13px] font-extrabold text-ink">{{ $label }}</span>
                                        <span class="block text-[11px] text-muted">{{ $sub }}</span>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </template>
                </div>
                <a href="{{ route('media.provider.media') }}" class="dv-btn dv-btn-ghost"><x-line-icon name="play" :size="14" filled /> Reels · {{ $reels }}</a>
                <a href="{{ route('media.providers.media', $provider) }}" class="dv-btn dv-btn-ghost"><x-line-icon name="user" :size="15" /> Public profile</a>
            </div>
        </div>
    </section>

    {{-- ─────────────────────────────  KPIs  ────────────────────────────── --}}
    <section class="px-5 md:px-[max(5vw,calc((100vw-1350px)/2))]">
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="dv-panel p-5">
                <p class="text-[11px] font-extrabold uppercase tracking-[0.12em] text-muted">Booked · last 30 days</p>
                <p class="mt-2 whitespace-nowrap font-dm-serif text-[clamp(1.3rem,3vw,2.3rem)] leading-none text-forest">KSh {{ number_format($kpis['revenue']) }}</p>
                @if ($kpis['revenue_change'] !== null)
                    <p @class(['mt-2 text-[12px] font-bold', 'text-forest' => $kpis['revenue_change'] >= 0, 'text-clay-dark' => $kpis['revenue_change'] < 0])>
                        {{ $kpis['revenue_change'] >= 0 ? '▲' : '▼' }} {{ abs($kpis['revenue_change']) }}% <span class="font-medium text-muted">vs the 30 days before</span>
                    </p>
                @else
                    <p class="mt-2 text-[12px] text-muted">Your first month of bookings</p>
                @endif
            </div>
            <div class="dv-panel p-5">
                <p class="text-[11px] font-extrabold uppercase tracking-[0.12em] text-muted">Arrivals · next 30 days</p>
                <p class="mt-2 whitespace-nowrap font-dm-serif text-[clamp(1.3rem,3vw,2.3rem)] leading-none text-forest">{{ $kpis['arrivals'] }}</p>
                <p class="mt-2 text-[12px] text-muted">{{ number_format($kpis['nights']) }} {{ Str::plural('night', $kpis['nights']) }} booked ahead</p>
            </div>
            <div class="dv-panel p-5">
                <p class="text-[11px] font-extrabold uppercase tracking-[0.12em] text-muted">Guest rating</p>
                <p class="mt-2 flex items-baseline gap-2 whitespace-nowrap font-dm-serif text-[clamp(1.3rem,3vw,2.3rem)] leading-none text-forest">
                    {{ $kpis['rating'] ?? '—' }}
                    @if ($kpis['rating']) <span class="text-[1rem] text-clay">★</span> @endif
                </p>
                <p class="mt-2 text-[12px] text-muted">Average across your listings</p>
            </div>
            <div class="dv-panel p-5">
                <p class="text-[11px] font-extrabold uppercase tracking-[0.12em] text-muted">Live listings</p>
                <p class="mt-2 whitespace-nowrap font-dm-serif text-[clamp(1.3rem,3vw,2.3rem)] leading-none text-forest">{{ $kpis['live'] }}<span class="text-[1.1rem] text-muted"> / {{ $kpis['listings'] }}</span></p>
                <p class="mt-2 text-[12px] text-muted">{{ $kpis['listings'] - $kpis['live'] }} in draft or review</p>
            </div>
        </div>
    </section>

    {{-- ──────────────────────  Earnings + requests  ────────────────────── --}}
    <section class="dv-section !pt-8">
        <div class="grid gap-5 lg:grid-cols-[1.4fr_1fr]">
            {{-- Earnings by arrival month --}}
            <div class="dv-panel p-6" x-data="{ hover: null }">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p class="dv-eyebrow">Earnings</p>
                        <h2 class="mt-1 font-dm-serif text-[1.6rem] leading-tight text-forest">By arrival month</h2>
                    </div>
                    <p class="text-[12px] text-muted">Confirmed and completed stays · KSh</p>
                </div>

                <div class="relative mt-6" role="img" aria-label="Earnings by arrival month for the last six months">
                    @if ($months->sum('total') <= 0)
                        <div class="absolute inset-x-0 top-1/3 z-10 mx-auto max-w-[18rem] rounded-[0.9rem] bg-white/90 px-4 py-3 text-center text-[12.5px] text-muted shadow-[0_10px_30px_rgba(20,36,28,0.08)]">
                            Earnings appear here once your first guests arrive.
                        </div>
                    @endif
                    <div class="pointer-events-none absolute inset-x-0 top-0 flex items-center gap-2 text-[10px] text-muted">
                        <span class="whitespace-nowrap">{{ number_format($max / 1000) }}k</span><span class="h-px flex-1 border-t border-dashed border-line"></span>
                    </div>
                    <div class="flex h-56 items-end gap-2 border-b border-line pt-6 sm:gap-4">
                        @foreach ($months as $i => $month)
                            <div
                                class="relative flex h-full flex-1 cursor-default flex-col items-center justify-end"
                                x-on:mouseenter="hover = {{ $i }}"
                                x-on:mouseleave="hover = null"
                                x-on:focus="hover = {{ $i }}"
                                x-on:blur="hover = null"
                                tabindex="0"
                                aria-label="{{ $month['long'] }}: KSh {{ number_format($month['total']) }} from {{ $month['count'] }} {{ Str::plural('booking', $month['count']) }}"
                            >
                                <div
                                    x-show="hover === {{ $i }}"
                                    x-cloak
                                    x-transition.opacity
                                    class="absolute z-10 w-max rounded-[0.6rem] bg-ink px-3 py-2 text-center text-white shadow-lg"
                                    style="bottom: calc({{ max(4, $month['total'] / $max * 100) }}% + 0.6rem)"
                                >
                                    <p class="text-[10px] text-white/70">{{ $month['long'] }}</p>
                                    <p class="text-[13px] font-extrabold">KSh {{ number_format($month['total']) }}</p>
                                    <p class="text-[10px] text-white/70">{{ $month['count'] }} {{ Str::plural('booking', $month['count']) }}</p>
                                </div>
                                <div
                                    @class([
                                        'w-full max-w-[3.25rem] rounded-t-[4px] transition-opacity duration-200',
                                        'bg-clay' => $month['total'] > 0,
                                        'bg-line' => $month['total'] <= 0,
                                    ])
                                    :class="hover !== null && hover !== {{ $i }} && 'opacity-40'"
                                    style="height: {{ $month['total'] > 0 ? max(3, $month['total'] / $max * 100) : 1 }}%"
                                ></div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-2 flex gap-2 sm:gap-4">
                        @foreach ($months as $month)
                            <span @class(['flex-1 text-center text-[11px]', 'font-extrabold text-ink' => $month['current'], 'text-muted' => ! $month['current']])>{{ $month['label'] }}@if ($month['current'])<span class="block text-[9px] font-semibold text-muted">so far</span>@endif</span>
                        @endforeach
                    </div>
                </div>

                <details class="mt-4 text-[12px]">
                    <summary class="cursor-pointer font-bold text-clay">View as table</summary>
                    <table class="mt-3 w-full text-left">
                        <thead class="text-[10px] uppercase tracking-[0.1em] text-muted"><tr><th class="py-1.5">Month</th><th class="py-1.5 text-right">Bookings</th><th class="py-1.5 text-right">Earned (KSh)</th></tr></thead>
                        <tbody>
                            @foreach ($months as $month)
                                <tr class="border-t border-line"><td class="py-1.5">{{ $month['long'] }}</td><td class="py-1.5 text-right">{{ $month['count'] }}</td><td class="py-1.5 text-right font-bold">{{ number_format($month['total']) }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </details>
            </div>

            {{-- Requests --}}
            <div class="dv-panel flex flex-col p-6">
                <div class="flex items-end justify-between gap-3">
                    <div>
                        <p class="dv-eyebrow">Needs your answer</p>
                        <h2 class="mt-1 font-dm-serif text-[1.6rem] leading-tight text-forest">Booking requests</h2>
                    </div>
                    @if ($requests->isNotEmpty())
                        <span class="rounded-full bg-clay px-2.5 py-1 text-[11px] font-extrabold text-white">{{ $kpis['pending'] }}</span>
                    @endif
                </div>

                <div class="mt-5 flex-1 space-y-3">
                    @forelse ($requests as $request)
                        <div class="rounded-[0.9rem] border border-line bg-surface p-4" wire:key="request-{{ $request['id'] }}">
                            <div class="flex items-start gap-3">
                                <span class="grid h-10 w-10 flex-none place-items-center rounded-full bg-forest text-[12px] font-extrabold text-white">{{ $request['initials'] }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-[13.5px] font-extrabold text-ink">{{ $request['guest'] }} <span class="font-medium text-muted">· {{ $request['party'] }} {{ Str::plural('guest', $request['party']) }}</span></p>
                                    <p class="truncate text-[12px] text-muted">{{ $request['listing'] }}</p>
                                    <p class="mt-1 text-[12px] font-bold text-forest">{{ $request['dates'] }} · {{ $request['nights'] }} {{ Str::plural('night', $request['nights']) }} · {{ $request['total'] }}</p>
                                </div>
                            </div>
                            <div class="mt-3 flex gap-2">
                                <button type="button" wire:click="updateBookingStatus({{ $request['id'] }}, 'confirmed')" wire:loading.attr="disabled" class="dv-btn dv-btn-primary !min-h-[2.4rem] flex-1">Accept</button>
                                <button type="button" wire:click="updateBookingStatus({{ $request['id'] }}, 'cancelled')" wire:confirm="Decline {{ $request['guest'] }}’s request for {{ $request['dates'] }}?" wire:loading.attr="disabled" class="dv-btn dv-btn-ghost !min-h-[2.4rem] flex-1">Decline</button>
                            </div>
                        </div>
                    @empty
                        <div class="flex h-full flex-col items-center justify-center rounded-[0.9rem] border border-dashed border-line px-4 py-10 text-center">
                            <span class="grid h-12 w-12 place-items-center rounded-full bg-cream text-forest"><x-line-icon name="check" :size="22" /></span>
                            <p class="mt-3 font-dm-serif text-[1.25rem] text-forest">You’re all caught up</p>
                            <p class="mt-1 text-[12px] text-muted">New requests will appear here.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    {{-- ────────────────────────────  Arrivals  ─────────────────────────── --}}
    <section class="dv-section !pt-0">
        <div class="flex items-end justify-between gap-4">
            <div>
                <p class="dv-eyebrow">Who’s coming</p>
                <h2 class="dv-title">Upcoming arrivals</h2>
            </div>
        </div>

        <div class="-mx-5 mt-6 flex snap-x gap-4 overflow-x-auto px-5 pb-2 no-scrollbar md:mx-0 md:grid md:grid-cols-2 md:overflow-visible md:px-0 xl:grid-cols-3">
            @forelse ($arrivals as $arrival)
                <div class="dv-panel w-[80vw] flex-none snap-start p-5 md:w-auto" wire:key="arrival-{{ $arrival['id'] }}">
                    <div class="flex items-center justify-between gap-3">
                        <span class="rounded-full bg-cream px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-[0.1em] text-clay">{{ $arrival['when'] }}</span>
                        <span class="text-[11px] text-muted">{{ $arrival['code'] }}</span>
                    </div>
                    <div class="mt-4 flex items-center gap-3">
                        <span class="grid h-11 w-11 flex-none place-items-center rounded-full bg-clay text-[13px] font-extrabold text-white">{{ $arrival['initials'] }}</span>
                        <div class="min-w-0">
                            <p class="truncate font-dm-serif text-[1.3rem] leading-tight text-ink">{{ $arrival['guest'] }}</p>
                            <p class="truncate text-[11.5px] text-muted">{{ $arrival['from'] ?: 'Guest' }} · {{ $arrival['party'] }} {{ Str::plural('guest', $arrival['party']) }}</p>
                        </div>
                    </div>
                    <div class="mt-4 grid grid-cols-[1fr_auto] gap-2 border-t border-dashed border-line pt-3 text-[12px]">
                        <span class="truncate text-muted">{{ $arrival['listing'] }}</span>
                        <span class="font-extrabold text-ink">{{ $arrival['total'] }}</span>
                        <span class="font-bold text-forest">{{ $arrival['dates'] }}</span>
                        <span class="text-muted">{{ $arrival['nights'] }} {{ Str::plural('night', $arrival['nights']) }}</span>
                    </div>
                </div>
            @empty
                <div class="col-span-full rounded-[1.2rem] border border-dashed border-line bg-surface px-6 py-12 text-center">
                    <p class="font-dm-serif text-[1.4rem] text-forest">No arrivals booked yet</p>
                    <p class="mt-1 text-[13px] text-muted">Confirmed guests will appear here, soonest first.</p>
                </div>
            @endforelse
        </div>
    </section>

    {{-- ────────────────────────────  Listings  ─────────────────────────── --}}
    <section class="dv-section bg-surface">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="dv-eyebrow">Your portfolio</p>
                <h2 class="dv-title">Listings</h2>
            </div>
            <p class="text-[12px] text-muted">{{ $listings->count() }} {{ Str::plural('listing', $listings->count()) }} · bookings shown are confirmed or completed</p>
        </div>

        <div class="mt-7 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @forelse ($listings as $listing)
                <article class="group overflow-hidden rounded-[1.2rem] border border-line bg-white transition-shadow duration-300 hover:shadow-[0_28px_60px_-22px_rgba(20,36,28,0.4)]" wire:key="{{ $listing['key'] }}">
                    <div class="relative aspect-[4/3] overflow-hidden">
                        <img src="{{ $listing['image'] }}" alt="{{ $listing['name'] }}" class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-[1.05]" loading="lazy">
                        <span class="absolute left-3 top-3 rounded-full px-2.5 py-1 text-[10px] font-extrabold {{ $listingStatus[$listing['status']][1] }}">{{ $listingStatus[$listing['status']][0] }}</span>
                        <span class="absolute right-3 top-3 rounded-full bg-black/40 px-2.5 py-1 text-[10px] font-extrabold text-white backdrop-blur-md">{{ $listing['kind'] }}</span>
                    </div>
                    <div class="p-4">
                        <p class="truncate font-dm-serif text-[1.2rem] leading-tight text-ink">{{ $listing['name'] }}</p>
                        <p class="truncate text-[11.5px] text-muted">{{ $listing['place'] }}</p>
                        @if (filled($listing['note'] ?? null))
                            <p class="mt-1.5 line-clamp-2 rounded-[0.6rem] bg-[#FBE9E2] px-2.5 py-1.5 text-[11px] leading-snug text-clay-dark"><b>{{ $listing['status'] === 'suspended' ? 'Taken down' : 'Changes requested' }}:</b> {{ $listing['note'] }}</p>
                        @endif
                        <dl class="mt-3 grid grid-cols-3 gap-2 border-t border-line pt-3 text-center">
                            <div><dt class="text-[9.5px] font-extrabold uppercase tracking-[0.1em] text-muted">Rating</dt><dd class="mt-0.5 text-[13px] font-extrabold text-ink">{{ $listing['rating'] > 0 ? number_format($listing['rating'], 1) : '—' }}</dd></div>
                            <div><dt class="text-[9.5px] font-extrabold uppercase tracking-[0.1em] text-muted">Bookings</dt><dd class="mt-0.5 text-[13px] font-extrabold text-ink">{{ $listing['bookings'] }}</dd></div>
                            <div><dt class="text-[9.5px] font-extrabold uppercase tracking-[0.1em] text-muted">Per {{ $listing['unit'] }}</dt><dd class="mt-0.5 text-[13px] font-extrabold text-ink">{{ $listing['price'] > 0 ? number_format($listing['price'] / 1000, 1).'k' : '—' }}</dd></div>
                        </dl>
                        <div class="mt-3 flex gap-2">
                            @if ($listing['manage'])
                                <a href="{{ $listing['manage'] }}" class="flex flex-1 items-center justify-center gap-1.5 rounded-[0.7rem] bg-forest py-2 text-[12px] font-extrabold text-white transition-colors hover:bg-forest-light"><x-line-icon name="sliders" :size="14" /> {{ $listing['in_setup'] ? 'Continue setup' : 'Manage' }}</a>
                            @endif
                            @if ($listing['url'])
                                <a href="{{ $listing['url'] }}" class="flex flex-1 items-center justify-center gap-1.5 rounded-[0.7rem] bg-cream py-2 text-[12px] font-extrabold text-forest transition-colors hover:bg-sand">{{ $listing['manage'] ? 'View' : 'View as a guest' }} <x-line-icon name="arrow-up-right" :size="14" /></a>
                            @else
                                <p class="flex-1 rounded-[0.7rem] border border-dashed border-line py-2 text-center text-[11.5px] text-muted">Not visible to guests yet</p>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="col-span-full flex flex-col items-center rounded-[1.4rem] border border-dashed border-line bg-white px-8 py-16 text-center">
                    <span class="grid h-14 w-14 place-items-center rounded-full bg-cream text-clay"><x-line-icon name="bed" :size="24" /></span>
                    <p class="mt-4 font-dm-serif text-[1.7rem] text-forest">Your first listing starts here</p>
                    <p class="mt-1.5 max-w-md text-[13px] leading-relaxed text-muted">Add a hotel, villa, B&amp;B or an experience. Once our team has checked it, it goes live and guests can book.</p>
                    <div class="mt-6 flex flex-wrap justify-center gap-2.5">
                        <a href="{{ route('provider.hotel-create') }}" class="dv-btn dv-btn-primary">Add a hotel</a>
                        <a href="{{ route('provider.villa-create') }}" class="dv-btn dv-btn-ghost">Add a villa</a>
                        <a href="{{ route('provider.bedandbreakfast-create') }}" class="dv-btn dv-btn-ghost">Add a B&amp;B</a>
                        <a href="{{ route('provider.activity-create') }}" class="dv-btn dv-btn-ghost">Add an experience</a>
                    </div>
                </div>
            @endforelse
        </div>
    </section>

    {{-- ─────────────────────  Offers + recent bookings  ────────────────── --}}
    <section class="dv-section">
        <div class="grid gap-5 lg:grid-cols-[1fr_1.4fr]">
            <div class="dv-panel p-6">
                <div class="flex items-end justify-between gap-3">
                    <div>
                        <p class="dv-eyebrow">Promotions</p>
                        <h2 class="mt-1 font-dm-serif text-[1.6rem] leading-tight text-forest">Offers</h2>
                    </div>
                    <button type="button" wire:click="openOfferModal" @disabled(empty($listingOptions)) class="dv-btn dv-btn-ghost !min-h-[2.4rem]"><x-line-icon name="star" :size="14" /> New offer</button>
                </div>

                <div class="mt-5 space-y-2.5">
                    @forelse ($offers as $offer)
                        <div class="flex items-center gap-3 rounded-[0.9rem] border border-line p-3.5" wire:key="offer-{{ $offer->id }}">
                            <span class="grid h-11 w-11 flex-none place-items-center rounded-[0.7rem] bg-[#F6D6C9] font-dm-serif text-[1rem] text-clay-dark">{{ (int) $offer->discount_percent }}%</span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[13px] font-extrabold text-ink">{{ $offer->title }}</p>
                                <p class="truncate text-[11px] text-muted">
                                    {{ $listingOptions[$offer->offerable_type.':'.$offer->offerable_id]['label'] ?? 'Listing' }}
                                    @if ($offer->end_date) · until {{ \Carbon\Carbon::parse($offer->end_date)->format('j M') }}@endif
                                    · {{ $offer->active ? 'Active' : 'Paused' }}
                                </p>
                            </div>
                            <button type="button" wire:click="openOfferModal({{ $offer->id }})" class="grid h-8 w-8 place-items-center rounded-full border border-line text-muted transition-colors hover:border-forest hover:text-forest" aria-label="Edit {{ $offer->title }}"><x-line-icon name="sliders" :size="14" /></button>
                            <button type="button" wire:click="deleteOffer({{ $offer->id }})" wire:confirm="Delete the offer “{{ $offer->title }}”?" class="grid h-8 w-8 place-items-center rounded-full border border-line text-muted transition-colors hover:border-clay hover:text-clay" aria-label="Delete {{ $offer->title }}"><x-line-icon name="close" :size="14" /></button>
                        </div>
                    @empty
                        <p class="rounded-[0.9rem] border border-dashed border-line px-4 py-8 text-center text-[12.5px] text-muted">No offers yet. A limited-time discount is a good way to fill quiet weeks.</p>
                    @endforelse
                </div>
            </div>

            <div class="dv-panel overflow-hidden">
                <div class="p-6 pb-3">
                    <p class="dv-eyebrow">Activity</p>
                    <h2 class="mt-1 font-dm-serif text-[1.6rem] leading-tight text-forest">Recent bookings</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[34rem] text-left text-[12.5px]">
                        <thead class="border-y border-line bg-cream text-[10px] font-extrabold uppercase tracking-[0.1em] text-muted">
                            <tr><th class="px-6 py-2.5">Guest</th><th class="px-3 py-2.5">Stay</th><th class="px-3 py-2.5">Dates</th><th class="px-3 py-2.5 text-right">Total</th><th class="px-6 py-2.5 text-right">Status</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($recent as $row)
                                <tr class="border-b border-line last:border-0" wire:key="recent-{{ $row['id'] }}">
                                    <td class="px-6 py-3"><span class="font-bold text-ink">{{ $row['guest'] }}</span><span class="block text-[11px] text-muted">{{ $row['booked'] }}</span></td>
                                    <td class="max-w-[12rem] truncate px-3 py-3 text-muted">{{ $row['listing'] }}</td>
                                    <td class="whitespace-nowrap px-3 py-3 text-ink">{{ $row['dates'] }}</td>
                                    <td class="whitespace-nowrap px-3 py-3 text-right font-bold text-ink">{{ $row['total'] }}</td>
                                    <td class="px-6 py-3 text-right"><span class="rounded-full px-2 py-0.5 text-[10px] font-extrabold {{ $statusStyles[$row['status']] ?? 'bg-line text-muted' }}">{{ $row['status_label'] }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-6 py-10 text-center text-muted">Bookings will appear here as guests reserve.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    {{-- ──────────────────────────  Offer modal  ────────────────────────── --}}
    <div
        x-data="{ show: @entangle('showOfferModal') }"
        x-show="show"
        x-cloak
        x-on:keydown.escape.window="show = false"
        class="fixed inset-0 z-[100] overflow-y-auto"
        role="dialog"
        aria-modal="true"
        aria-labelledby="offer-title"
    >
        <div class="fixed inset-0 bg-[#0C1511]/70 backdrop-blur-sm" x-on:click="show = false" x-show="show" x-transition.opacity></div>
        <div class="relative flex min-h-full items-end justify-center sm:items-center sm:p-6">
            <form wire:submit="saveOffer" x-show="show" x-transition class="relative w-full max-w-[38rem] rounded-t-[1.6rem] bg-surface p-6 shadow-2xl sm:rounded-[1.6rem] sm:p-8">
                <button type="button" x-on:click="show = false" class="absolute right-4 top-4 grid h-9 w-9 place-items-center rounded-full border border-line text-muted hover:border-forest hover:text-forest" aria-label="Close"><x-line-icon name="close" :size="16" /></button>

                <p class="dv-eyebrow">Promotions</p>
                <h2 id="offer-title" class="mt-1 font-dm-serif text-[1.9rem] leading-tight text-forest">{{ $offerForm->offer ? 'Edit offer' : 'New offer' }}</h2>

                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <label class="block sm:col-span-2">
                        <span class="auth-label">For which listing</span>
                        <select wire:model="offerListing" @class(['auth-input', 'auth-input-error' => $errors->has('offerListing')])>
                            <option value="">Choose a listing…</option>
                            @foreach ($listingOptions as $value => $option)
                                <option value="{{ $value }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                        @error('offerListing') <span class="auth-error">{{ $message }}</span> @enderror
                    </label>
                    <label class="block sm:col-span-2">
                        <span class="auth-label">Title</span>
                        <input type="text" wire:model="offerForm.title" placeholder="Long-weekend escape" @class(['auth-input', 'auth-input-error' => $errors->has('offerForm.title')])>
                        @error('offerForm.title') <span class="auth-error">{{ $message }}</span> @enderror
                    </label>
                    <label class="block sm:col-span-2">
                        <span class="auth-label">Description</span>
                        <textarea wire:model="offerForm.description" rows="3" class="auth-input !h-auto py-3"></textarea>
                        @error('offerForm.description') <span class="auth-error">{{ $message }}</span> @enderror
                    </label>
                    <label class="block">
                        <span class="auth-label">Price (KSh)</span>
                        <input type="number" min="0" step="100" wire:model="offerForm.price" @class(['auth-input', 'auth-input-error' => $errors->has('offerForm.price')])>
                        @error('offerForm.price') <span class="auth-error">{{ $message }}</span> @enderror
                    </label>
                    <label class="block">
                        <span class="auth-label">Discount %</span>
                        <input type="number" min="0" max="100" wire:model="offerForm.discount_percent" class="auth-input">
                        @error('offerForm.discount_percent') <span class="auth-error">{{ $message }}</span> @enderror
                    </label>
                    <label class="block">
                        <span class="auth-label">Starts</span>
                        <input type="date" wire:model="offerForm.start_date" class="auth-input">
                        @error('offerForm.start_date') <span class="auth-error">{{ $message }}</span> @enderror
                    </label>
                    <label class="block">
                        <span class="auth-label">Ends</span>
                        <input type="date" wire:model="offerForm.end_date" class="auth-input">
                        @error('offerForm.end_date') <span class="auth-error">{{ $message }}</span> @enderror
                    </label>
                    <label class="block sm:col-span-2">
                        <span class="auth-label">Destination</span>
                        <select wire:model="offerForm.destination_id" class="auth-input">
                            <option value="">Same as the listing</option>
                            @foreach ($destinations as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block sm:col-span-2">
                        <span class="auth-label">Cover image</span>
                        <input type="file" accept="image/*" wire:model="offerForm.main_image" class="block w-full text-[12px] text-muted file:mr-3 file:rounded-full file:border-0 file:bg-cream file:px-4 file:py-2 file:text-[12px] file:font-bold file:text-forest">
                        @error('offerForm.main_image') <span class="auth-error">{{ $message }}</span> @enderror
                    </label>
                    <div class="flex flex-wrap gap-5 sm:col-span-2">
                        <label class="flex items-center gap-2 text-[13px] font-semibold text-ink"><input type="checkbox" wire:model="offerForm.active" class="auth-check"> Active</label>
                        <label class="flex items-center gap-2 text-[13px] font-semibold text-ink"><input type="checkbox" wire:model="offerForm.is_featured" class="auth-check"> Feature it</label>
                    </div>
                </div>

                <div class="mt-7 flex flex-wrap justify-end gap-2.5">
                    <button type="button" x-on:click="show = false" class="dv-btn dv-btn-ghost">Cancel</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="saveOffer,offerForm.main_image" class="dv-btn dv-btn-primary">
                        <span wire:loading.remove wire:target="saveOffer">Save offer</span>
                        <span wire:loading wire:target="saveOffer">Saving…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
