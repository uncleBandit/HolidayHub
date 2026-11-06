@php
    use App\Modules\Booking\Presentation\Support\TripPresenter;

    $trips = $bookings->getCollection()->map(fn ($booking) => ['booking' => $booking] + TripPresenter::make($booking));
    $groups = collect([
        'upcoming' => ['label' => 'Upcoming', 'line' => 'Packed, planned and counting down.'],
        'past' => ['label' => 'Past trips', 'line' => 'Places you have already fallen for.'],
        'cancelled' => ['label' => 'Cancelled', 'line' => 'Plans that changed along the way.'],
    ])->map(fn ($group, $key) => $group + ['trips' => $trips->where('bucket', $key)])
      ->filter(fn ($group) => $group['trips']->isNotEmpty());

    $statuses = ['' => 'All trips', 'pending' => 'Pending', 'confirmed' => 'Confirmed', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
    $sorts = ['check_in_date' => 'Trip date', 'status' => 'Status', 'created_at' => 'Booked on'];
@endphp

<div>
    {{-- ────────────────────────────  Header  ───────────────────────────── --}}
    <section class="dv-section dv-foryou-head pb-8 md:pb-10">
        <div class="relative grid items-end gap-8 lg:grid-cols-[1.2fr_0.8fr]">
            <div>
                <p class="dv-eyebrow dv-magic-in" style="--i: 0">My trips</p>
                <h1 class="dv-magic-in mt-3 font-dm-serif text-[clamp(2.5rem,6vw,5rem)] leading-[0.95] tracking-[-0.03em] text-forest" style="--i: 1">
                    Every escape,<br><span class="italic text-clay">in one place.</span>
                </h1>
                <p class="dv-magic-in mt-5 max-w-[34rem] text-[14px] leading-relaxed text-muted" style="--i: 2">
                    Manage your upcoming adventures and look back on past trips — tickets, dates and totals, all together.
                </p>
            </div>

            <div class="dv-magic-in w-full lg:max-w-[26rem] lg:justify-self-end" style="--i: 3">
                <label for="search" class="sr-only">Search bookings</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-muted">
                        <x-line-icon name="search" :size="18" />
                    </span>
                    <input
                        wire:model.live.debounce.300ms="search"
                        id="search"
                        type="text"
                        placeholder="Search by name or code…"
                        class="auth-input !h-[3.4rem] !rounded-full !bg-white pl-12 pr-5 shadow-[0_14px_40px_rgba(20,36,28,0.08)]"
                    >
                </div>
            </div>
        </div>
    </section>

    {{-- ─────────────────────────  Sticky filters  ──────────────────────── --}}
    <div class="dv-foryou-filters">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="dv-category-row" role="tablist" aria-label="Filter by status" data-no-reveal>
                @foreach ($statuses as $value => $label)
                    <button
                        type="button"
                        role="tab"
                        wire:click="$set('status', '{{ $value }}')"
                        @class(['dv-category py-3', 'dv-category-active' => $status === $value])
                        aria-selected="{{ $status === $value ? 'true' : 'false' }}"
                    >{{ $label }}</button>
                @endforeach
            </div>

            <div class="-mx-1 flex items-center gap-1 overflow-x-auto px-1 no-scrollbar" aria-label="Sort trips" data-no-reveal>
                <span class="mr-1 shrink-0 text-[11px] font-semibold text-muted">Sort</span>
                @foreach ($sorts as $field => $label)
                    @php $active = $sortField === $field; @endphp
                    <button
                        type="button"
                        wire:click="sortBy('{{ $field }}')"
                        @class([
                            'inline-flex shrink-0 items-center gap-1 rounded-full px-3 py-2 text-[11px] font-extrabold transition-colors',
                            'bg-forest text-white' => $active,
                            'text-ink hover:bg-sand' => ! $active,
                        ])
                        aria-pressed="{{ $active ? 'true' : 'false' }}"
                    >
                        {{ $label }}
                        @if ($active)
                            <span @class(['inline-flex transition-transform', 'rotate-180' => $sortDirection === 'asc'])>
                                <x-line-icon name="chevron-down" :size="13" :stroke="2.4" />
                            </span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ───────────────────────────  Trips  ─────────────────────────────── --}}
    <section class="dv-section pt-8 md:pt-10" wire:loading.class="opacity-60" wire:target="search, status, sortBy, gotoPage, nextPage, previousPage">
        @if ($bookings->isEmpty())
            <div class="dv-panel dv-panel-shadow mx-auto max-w-[40rem] px-6 py-14 text-center sm:px-12">
                <span class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-cream text-clay shadow-[0_0_0_9px_rgba(216,95,60,0.08)]">
                    <x-line-icon name="ticket" :size="28" />
                </span>
                @if (filled($search) || filled($status))
                    <p class="mt-6 font-dm-serif text-[1.9rem] leading-tight text-forest">No bookings found</p>
                    <p class="mx-auto mt-2 max-w-sm text-[13px] leading-relaxed text-muted">Try adjusting your search or filters.</p>
                    <button type="button" class="dv-btn dv-btn-ghost mt-7" wire:click="$set('search', ''); $set('status', '')">
                        Clear search and filters
                    </button>
                @else
                    <p class="mt-6 font-dm-serif text-[1.9rem] leading-tight text-forest">No trips booked yet</p>
                    <p class="mx-auto mt-2 max-w-sm text-[13px] leading-relaxed text-muted">
                        When you book a stay or an experience, its ticket lands here — dates, guests and confirmation code included.
                    </p>
                    <div class="mt-7 flex flex-col justify-center gap-3 sm:flex-row">
                        <a href="{{ route('stays.index') }}" class="dv-btn dv-btn-primary">Find a stay <x-line-icon name="arrow" :size="16" /></a>
                        <a href="{{ route('experiences.index') }}" class="dv-btn dv-btn-ghost">Browse experiences</a>
                    </div>
                @endif
            </div>
        @else
            <div class="space-y-14">
                @foreach ($groups as $key => $group)
                    <div wire:key="trip-group-{{ $key }}">
                        <div class="mb-6 flex flex-wrap items-end justify-between gap-x-6 gap-y-1">
                            <div>
                                <h2 class="font-dm-serif text-[clamp(1.6rem,2.6vw,2.3rem)] leading-tight text-forest">{{ $group['label'] }}</h2>
                                <p class="mt-1 text-[13px] text-muted">{{ $group['line'] }}</p>
                            </div>
                            <span class="text-[11px] font-extrabold uppercase tracking-[0.12em] text-muted">
                                {{ $group['trips']->count() }} {{ Str::plural('trip', $group['trips']->count()) }}
                            </span>
                        </div>

                        <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach ($group['trips'] as $trip)
                                @php $booking = $trip['booking']; @endphp
                                <article wire:key="trip-{{ $booking->id }}" @class([
                                    'group relative flex flex-col overflow-hidden rounded-[1.2rem] border border-line bg-white shadow-[0_18px_50px_rgba(20,36,28,0.08)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_26px_70px_rgba(20,36,28,0.14)]',
                                    'opacity-80 hover:opacity-100' => $trip['bucket'] === 'cancelled',
                                ])>
                                    <a href="{{ route('bookings-show', $booking) }}" class="flex flex-1 flex-col focus:outline-none focus-visible:ring-2 focus-visible:ring-forest">
                                        <div class="relative h-52 overflow-hidden bg-sand">
                                            <img
                                                src="{{ $trip['image'] }}"
                                                alt="{{ $trip['name'] }}"
                                                loading="lazy"
                                                @class([
                                                    'h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-[1.04]',
                                                    'grayscale-[60%]' => $trip['bucket'] === 'cancelled',
                                                ])
                                            >
                                            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-[rgba(10,20,16,0.55)] via-transparent to-transparent"></div>

                                            <span class="absolute left-3.5 top-3.5 inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-extrabold {{ $trip['status_class'] }}">
                                                {{ $trip['status_label'] }}
                                            </span>

                                            @if ($trip['bucket'] === 'upcoming' && $trip['countdown'])
                                                <span class="absolute right-3.5 top-3.5 inline-flex items-center gap-1.5 rounded-full border border-white/25 bg-black/35 px-2.5 py-1 text-[10px] font-extrabold text-white backdrop-blur-md">
                                                    <x-line-icon name="clock" :size="12" />
                                                    {{ $trip['countdown'] === 1 ? 'Tomorrow' : 'In '.$trip['countdown'].' days' }}
                                                </span>
                                            @endif

                                            <p class="absolute bottom-3.5 left-4 right-4 truncate text-[10px] font-extrabold uppercase tracking-[0.12em] text-white/90">
                                                {{ $trip['kind'] }}@if ($trip['place']) · {{ $trip['place'] }}@endif
                                            </p>
                                        </div>

                                        <div class="flex-1 p-5">
                                            <p class="font-dm-serif text-[1.4rem] leading-tight text-ink">{{ $trip['name'] }}</p>

                                            <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 text-[12px]">
                                                <div class="col-span-2 flex items-start gap-2">
                                                    <span class="mt-0.5 text-clay"><x-line-icon name="calendar" :size="15" /></span>
                                                    <div>
                                                        <dt class="sr-only">Dates</dt>
                                                        <dd class="font-bold text-ink">
                                                            {{ \Carbon\Carbon::parse($booking->check_in_date)->format('M d, Y') }} –
                                                            {{ \Carbon\Carbon::parse($booking->check_out_date)->format('M d, Y') }}
                                                        </dd>
                                                        <dd class="text-[11px] text-muted">
                                                            {{ \Carbon\Carbon::parse($booking->check_in_date)->diffInDays(\Carbon\Carbon::parse($booking->check_out_date)) }} nights
                                                        </dd>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-clay"><x-line-icon name="users" :size="15" /></span>
                                                    <dt class="sr-only">Guests</dt>
                                                    <dd class="font-semibold text-ink">{{ $trip['guests_label'] }}</dd>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-clay"><x-line-icon name="bookmark" :size="15" /></span>
                                                    <dt class="sr-only">Booked on</dt>
                                                    <dd class="truncate text-muted">Booked {{ $booking->created_at->format('M d, Y') }}</dd>
                                                </div>
                                            </dl>
                                        </div>

                                        <div class="relative flex items-center justify-between gap-3 border-t border-dashed border-line bg-cream px-5 py-3.5">
                                            {{-- Ticket notches --}}
                                            <span class="absolute -left-2.5 -top-2.5 h-5 w-5 rounded-full border border-line bg-cream" aria-hidden="true"></span>
                                            <span class="absolute -right-2.5 -top-2.5 h-5 w-5 rounded-full border border-line bg-cream" aria-hidden="true"></span>

                                            <div class="min-w-0">
                                                <p class="text-[9px] font-extrabold uppercase tracking-[0.12em] text-clay">Ref</p>
                                                <p class="truncate font-mono text-[12px] font-bold text-forest">#{{ $booking->confirmation_code }}</p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-[9px] font-extrabold uppercase tracking-[0.12em] text-clay">Total</p>
                                                <p class="text-[13px] font-extrabold text-forest">{{ $trip['total_label'] }}</p>
                                            </div>
                                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-forest text-white transition-transform duration-200 group-hover:translate-x-1">
                                                <x-line-icon name="arrow" :size="16" />
                                                <span class="sr-only">View details</span>
                                            </span>
                                        </div>
                                    </a>
                                </article>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($bookings->hasPages())
                <div class="mt-12 rounded-[1rem] border border-line bg-white px-4 py-3">
                    {{ $bookings->links() }}
                </div>
            @endif
        @endif
    </section>
</div>
