<div>
    @php
        $max = max(1, collect($months)->max('total'));
        $symbol = \App\Support\Currencies::symbol($kpis['currency']);
        $statusStyles = [
            'pending' => 'bg-[#F6D6C9] text-clay-dark',
            'confirmed' => 'bg-forest/10 text-forest',
            'checked_in' => 'bg-forest text-white',
            'checked_out' => 'bg-line text-muted',
            'completed' => 'bg-line text-muted',
            'cancelled' => 'bg-line/60 text-muted line-through',
        ];
        $packageStatus = [
            'published' => ['Live', 'bg-forest text-white'],
            'pending_review' => ['In review', 'bg-[#F6EEDD] text-[#8A6A3A]'],
            'rejected' => ['Changes requested', 'bg-[#F6D6C9] text-clay-dark'],
            'suspended' => ['Suspended', 'bg-clay text-white'],
            'draft' => ['Draft', 'bg-white/90 text-ink'],
        ];
        $verified = $agent->isVerified();
    @endphp

    {{-- ────────────────────────────  Header  ───────────────────────────── --}}
    <section class="dv-section dv-foryou-head pb-8">
        <div class="relative flex flex-wrap items-end justify-between gap-8">
            <div class="max-w-[46rem]">
                <p class="dv-eyebrow dv-magic-in" style="--i: 0">Agent studio</p>
                <h1 class="dv-magic-in mt-3 font-dm-serif text-[clamp(2.4rem,5vw,4.4rem)] leading-[0.98] tracking-[-0.02em] text-forest" style="--i: 1">
                    {{ $greeting }},<br><span class="italic text-clay">{{ $agent->agency_name ?: Auth::user()->name }}.</span>
                </h1>
                <p class="dv-magic-in mt-4 text-[14px] leading-relaxed text-muted" style="--i: 2">
                    {{ $kpis['live'] }} of {{ $kpis['packages'] }} {{ Str::plural('journey', $kpis['packages']) }} live
                    @if ($kpis['in_review']) · <b class="text-ink">{{ $kpis['in_review'] }} in review</b>@endif
                    · {{ $kpis['upcoming'] }} upcoming {{ Str::plural('departure', $kpis['upcoming']) }}
                </p>
            </div>

            <div class="dv-magic-in flex flex-wrap gap-2.5" style="--i: 3">
                <a href="{{ route('agent.packages.create', ['new' => 1]) }}" class="dv-btn dv-btn-primary"><x-line-icon name="compass" :size="16" /> New package</a>
                <a href="{{ route('stays.index') }}" class="dv-btn dv-btn-ghost"><x-line-icon name="search" :size="15" /> Browse listings</a>
            </div>
        </div>
    </section>

    @if (session('success'))
        <div class="px-5 md:px-[max(5vw,calc((100vw-1350px)/2))]">
            <div class="auth-alert auth-alert-ok mb-5 flex items-center gap-2"><x-line-icon name="check" :size="16" /> {{ session('success') }}</div>
        </div>
    @endif

    @unless ($verified)
        <div class="px-5 md:px-[max(5vw,calc((100vw-1350px)/2))]">
            <div class="mb-5 flex flex-wrap items-start gap-3 rounded-[1rem] border border-[#E9D3A6] bg-[#F6EEDD] px-4 py-3.5 text-[13px] text-[#6B5230]">
                <span class="grid h-8 w-8 flex-none place-items-center rounded-full bg-white text-clay"><x-line-icon name="clock" :size="15" /></span>
                <p class="min-w-0 flex-1">
                    <b class="text-ink">Your agency is being verified.</b>
                    Build as many journeys as you like in the meantime. You’ll be able to <b>submit them for review</b> as soon as our team has verified {{ $agent->agency_name ?: 'your agency' }}, usually within a working day.
                </p>
            </div>
        </div>
    @endunless

    {{-- ─────────────────────────────  KPIs  ────────────────────────────── --}}
    <section class="px-5 md:px-[max(5vw,calc((100vw-1350px)/2))]">
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="dv-panel p-5">
                <p class="text-[11px] font-extrabold uppercase tracking-[0.12em] text-muted">Live journeys</p>
                <p class="mt-2 whitespace-nowrap font-dm-serif text-[clamp(1.3rem,3vw,2.3rem)] leading-none text-forest">{{ $kpis['live'] }}<span class="text-[1.1rem] text-muted"> / {{ $kpis['packages'] }}</span></p>
                <p class="mt-2 text-[12px] text-muted">{{ $kpis['packages'] - $kpis['live'] }} in draft or review</p>
            </div>
            <div class="dv-panel p-5">
                <p class="text-[11px] font-extrabold uppercase tracking-[0.12em] text-muted">Bookings</p>
                <p class="mt-2 whitespace-nowrap font-dm-serif text-[clamp(1.3rem,3vw,2.3rem)] leading-none text-forest">{{ $kpis['bookings'] }}</p>
                <p class="mt-2 text-[12px] text-muted">{{ $kpis['bookings_30'] }} in the last 30 days</p>
            </div>
            <div class="dv-panel p-5">
                <p class="text-[11px] font-extrabold uppercase tracking-[0.12em] text-muted">Commission earned</p>
                <p class="mt-2 whitespace-nowrap font-dm-serif text-[clamp(1.3rem,3vw,2.3rem)] leading-none text-forest">{{ $symbol }} {{ number_format($kpis['commission']) }}</p>
                <p class="mt-2 text-[12px] text-muted">
                    @forelse ($kpis['commission_other'] as $code => $amount)
                        + {{ \App\Support\Currencies::format($amount, $code) }}@if (! $loop->last), @endif
                    @empty
                        Your margin on confirmed bookings
                    @endforelse
                </p>
            </div>
            <div class="dv-panel p-5">
                <p class="text-[11px] font-extrabold uppercase tracking-[0.12em] text-muted">Upcoming departures</p>
                <p class="mt-2 whitespace-nowrap font-dm-serif text-[clamp(1.3rem,3vw,2.3rem)] leading-none text-forest">{{ $kpis['upcoming'] }}</p>
                <p class="mt-2 text-[12px] text-muted">{{ $kpis['travellers'] }} {{ Str::plural('traveller', $kpis['travellers']) }} on the way</p>
            </div>
        </div>
    </section>

    {{-- ──────────────────────  Commission + departures  ────────────────── --}}
    <section class="dv-section !pt-8">
        <div class="grid gap-5 lg:grid-cols-[1.4fr_1fr]">
            <div class="dv-panel p-6" x-data="{ hover: null }">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p class="dv-eyebrow">Commission</p>
                        <h2 class="mt-1 font-dm-serif text-[1.6rem] leading-tight text-forest">By departure month</h2>
                    </div>
                    <p class="text-[12px] text-muted">Confirmed bookings · {{ $kpis['currency'] }}</p>
                </div>

                <div class="relative mt-6" role="img" aria-label="Commission by departure month">
                    @if (collect($months)->sum('total') <= 0)
                        <div class="absolute inset-x-0 top-1/3 z-10 mx-auto max-w-[18rem] rounded-[0.9rem] bg-white/90 px-4 py-3 text-center text-[12.5px] text-muted shadow-[0_10px_30px_rgba(20,36,28,0.08)]">
                            Your commission appears here once guests book your journeys.
                        </div>
                    @endif
                    <div class="flex h-56 items-end gap-2 border-b border-line pt-6 sm:gap-4">
                        @foreach ($months as $i => $month)
                            <div class="relative flex h-full flex-1 cursor-default flex-col items-center justify-end" x-on:mouseenter="hover = {{ $i }}" x-on:mouseleave="hover = null" x-on:focus="hover = {{ $i }}" x-on:blur="hover = null" tabindex="0" aria-label="{{ $month['long'] }}: {{ $symbol }} {{ number_format($month['total']) }} from {{ $month['count'] }} {{ Str::plural('booking', $month['count']) }}">
                                <div x-show="hover === {{ $i }}" x-cloak x-transition.opacity class="absolute z-10 w-max rounded-[0.6rem] bg-ink px-3 py-2 text-center text-white shadow-lg" style="bottom: calc({{ max(4, $month['total'] / $max * 100) }}% + 0.6rem)">
                                    <p class="text-[10px] text-white/70">{{ $month['long'] }}</p>
                                    <p class="text-[13px] font-extrabold">{{ $symbol }} {{ number_format($month['total']) }}</p>
                                    <p class="text-[10px] text-white/70">{{ $month['count'] }} {{ Str::plural('departure', $month['count']) }}</p>
                                </div>
                                <div @class([
                                        'w-full max-w-[3.25rem] rounded-t-[4px] transition-opacity duration-200',
                                        'bg-clay' => $month['total'] > 0 && ! $month['future'],
                                        'bg-clay/45' => $month['total'] > 0 && $month['future'],
                                        'bg-line' => $month['total'] <= 0,
                                    ])
                                    :class="hover !== null && hover !== {{ $i }} && 'opacity-40'"
                                    style="height: {{ $month['total'] > 0 ? max(3, $month['total'] / $max * 100) : 1 }}%"></div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-2 flex gap-2 sm:gap-4">
                        @foreach ($months as $month)
                            <span @class(['flex-1 text-center text-[11px]', 'font-extrabold text-ink' => $month['current'], 'text-muted' => ! $month['current']])>{{ $month['label'] }}@if ($month['future'])<span class="block text-[9px] font-semibold text-muted">booked</span>@endif</span>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="dv-panel flex flex-col p-6">
                <div>
                    <p class="dv-eyebrow">Who’s travelling</p>
                    <h2 class="mt-1 font-dm-serif text-[1.6rem] leading-tight text-forest">Next departures</h2>
                </div>
                <div class="mt-5 flex-1 space-y-3">
                    @forelse ($departures as $row)
                        <div class="rounded-[0.9rem] border border-line bg-surface p-4" wire:key="dep-{{ $row['id'] }}">
                            <div class="flex items-start gap-3">
                                <span class="grid h-10 w-10 flex-none place-items-center rounded-full bg-forest text-[12px] font-extrabold text-white">{{ $row['initials'] }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-[13.5px] font-extrabold text-ink">{{ $row['guest'] }} <span class="font-medium text-muted">· {{ $row['party'] }} {{ Str::plural('guest', $row['party']) }}</span></p>
                                    <p class="truncate text-[12px] text-muted">{{ $row['package'] }}</p>
                                    <p class="mt-1 text-[12px] font-bold text-forest">{{ $row['dates'] }} · {{ $row['when'] }} · {{ $row['parts'] }} {{ Str::plural('booking', $row['parts']) }} with hosts</p>
                                </div>
                                <span class="whitespace-nowrap text-[12px] font-extrabold text-clay">+{{ $row['commission'] }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="flex h-full flex-col items-center justify-center rounded-[0.9rem] border border-dashed border-line px-4 py-10 text-center">
                            <span class="grid h-12 w-12 place-items-center rounded-full bg-cream text-forest"><x-line-icon name="compass" :size="22" /></span>
                            <p class="mt-3 font-dm-serif text-[1.25rem] text-forest">No departures yet</p>
                            <p class="mt-1 text-[12px] text-muted">Guests who book your journeys appear here, soonest first.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    {{-- ────────────────────────────  Packages  ─────────────────────────── --}}
    <section class="dv-section bg-surface" id="packages">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="dv-eyebrow">Your journeys</p>
                <h2 class="dv-title">Packages</h2>
            </div>
            <a href="{{ route('agent.packages.create', ['new' => 1]) }}" class="dv-btn dv-btn-ghost"><x-line-icon name="compass" :size="15" /> New package</a>
        </div>

        <div class="mt-7 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @forelse ($packages as $package)
                <article class="group overflow-hidden rounded-[1.2rem] border border-line bg-white transition-shadow duration-300 hover:shadow-[0_28px_60px_-22px_rgba(20,36,28,0.4)]" wire:key="pkg-{{ $package['id'] }}">
                    <div class="relative aspect-[4/3] overflow-hidden">
                        <img src="{{ $package['image'] }}" alt="{{ $package['name'] }}" class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-[1.05]" loading="lazy">
                        <span class="absolute left-3 top-3 rounded-full px-2.5 py-1 text-[10px] font-extrabold {{ ($packageStatus[$package['status']] ?? $packageStatus['draft'])[1] }}">{{ ($packageStatus[$package['status']] ?? $packageStatus['draft'])[0] }}</span>
                        <span class="absolute right-3 top-3 inline-flex items-center gap-1 rounded-full bg-black/40 px-2.5 py-1 text-[10px] font-extrabold text-white backdrop-blur-md">
                            @if ($package['film'])<x-line-icon name="play" :size="10" filled />@endif {{ $package['days'] }} {{ Str::plural('day', $package['days']) }}
                        </span>
                    </div>
                    <div class="p-4">
                        <p class="truncate font-dm-serif text-[1.2rem] leading-tight text-ink">{{ $package['name'] }}</p>
                        <p class="truncate text-[11.5px] text-muted">{{ $package['place'] }}</p>
                        @if ($package['reason'])
                            <p class="mt-2 line-clamp-2 rounded-[0.6rem] bg-[#FBE7DF] px-2.5 py-1.5 text-[11px] text-clay-dark"><b>Our team:</b> {{ $package['reason'] }}</p>
                        @endif
                        <dl class="mt-3 grid grid-cols-3 gap-2 border-t border-line pt-3 text-center">
                            <div><dt class="text-[9.5px] font-extrabold uppercase tracking-[0.1em] text-muted">Parts</dt><dd class="mt-0.5 text-[13px] font-extrabold text-ink">{{ $package['parts'] }}</dd></div>
                            <div><dt class="text-[9.5px] font-extrabold uppercase tracking-[0.1em] text-muted">Bookings</dt><dd class="mt-0.5 text-[13px] font-extrabold text-ink">{{ $package['bookings'] }}</dd></div>
                            <div><dt class="text-[9.5px] font-extrabold uppercase tracking-[0.1em] text-muted">From pp</dt><dd class="mt-0.5 truncate text-[13px] font-extrabold text-ink">{{ $package['from'] ?? '—' }}</dd></div>
                        </dl>
                        <div class="mt-3 flex gap-2">
                            <a href="{{ $package['manage'] }}" class="flex flex-1 items-center justify-center gap-1.5 rounded-[0.7rem] bg-forest py-2 text-[12px] font-extrabold text-white transition-colors hover:bg-forest-light"><x-line-icon name="sliders" :size="14" /> {{ $package['in_setup'] ? 'Continue · step '.$package['step'] : 'Manage' }}</a>
                            @if ($package['url'])
                                <a href="{{ $package['url'] }}" class="flex flex-1 items-center justify-center gap-1.5 rounded-[0.7rem] bg-cream py-2 text-[12px] font-extrabold text-forest transition-colors hover:bg-sand">View <x-line-icon name="arrow-up-right" :size="14" /></a>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="col-span-full flex flex-col items-center rounded-[1.4rem] border border-dashed border-line bg-white px-8 py-16 text-center">
                    <span class="grid h-14 w-14 place-items-center rounded-full bg-cream text-clay"><x-line-icon name="compass" :size="24" /></span>
                    <p class="mt-4 font-dm-serif text-[1.7rem] text-forest">Your first journey starts here</p>
                    <p class="mt-1.5 max-w-md text-[13px] leading-relaxed text-muted">Pick stays and experiences that are already live on HolidayHub, lay them out day by day, add your margin and a film. Guests book it all in one go.</p>
                    <a href="{{ route('agent.packages.create', ['new' => 1]) }}" class="dv-btn dv-btn-primary mt-6">Build a package</a>
                </div>
            @endforelse
        </div>
    </section>

    {{-- ─────────────────────────  Recent bookings  ─────────────────────── --}}
    <section class="dv-section">
        <div class="dv-panel overflow-hidden">
            <div class="p-6 pb-3">
                <p class="dv-eyebrow">Activity</p>
                <h2 class="mt-1 font-dm-serif text-[1.6rem] leading-tight text-forest">Recent package bookings</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[40rem] text-left text-[12.5px]">
                    <thead class="border-y border-line bg-cream text-[10px] font-extrabold uppercase tracking-[0.1em] text-muted">
                        <tr><th class="px-6 py-2.5">Guest</th><th class="px-3 py-2.5">Journey</th><th class="px-3 py-2.5">Dates</th><th class="px-3 py-2.5 text-right">Total</th><th class="px-3 py-2.5 text-right">Your commission</th><th class="px-6 py-2.5 text-right">Status</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($recent as $row)
                            <tr class="border-b border-line last:border-0" wire:key="recent-{{ $row['id'] }}">
                                <td class="px-6 py-3"><span class="font-bold text-ink">{{ $row['guest'] }}</span><span class="block text-[11px] text-muted">{{ $row['booked'] }} · #{{ $row['code'] }}</span></td>
                                <td class="max-w-[14rem] truncate px-3 py-3 text-muted">{{ $row['package'] }}<span class="block text-[11px]">{{ $row['party'] }} {{ Str::plural('guest', $row['party']) }} · {{ $row['parts'] }} host {{ Str::plural('booking', $row['parts']) }}</span></td>
                                <td class="whitespace-nowrap px-3 py-3 text-ink">{{ $row['dates'] }}</td>
                                <td class="whitespace-nowrap px-3 py-3 text-right font-bold text-ink">{{ $row['total'] }}</td>
                                <td class="whitespace-nowrap px-3 py-3 text-right font-bold text-clay">{{ $row['commission'] }}</td>
                                <td class="px-6 py-3 text-right"><span class="rounded-full px-2 py-0.5 text-[10px] font-extrabold {{ $statusStyles[$row['status']] ?? 'bg-line text-muted' }}">{{ $row['status_label'] }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-10 text-center text-muted">Bookings appear here as guests book your journeys.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>
