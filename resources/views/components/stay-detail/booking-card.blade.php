{{-- Sticky booking card (desktop aside) plus the mobile sticky book bar.

     Dates come from the availability calendar (calendar.calendar-component),
     which dispatches `staySelected` once a check-in and check-out are picked.
     "Reserve" calls that calendar's own book() action, which validates the stay
     and opens the shared booking-form modal — the same flow as before.

     `target` is the id of the section holding the calendar (or the room list
     when a hotel room still has to be chosen); `ready` says the calendar is
     actually on the page. --}}
@props([
    'price' => 0,
    'currency' => 'KSh',
    'from' => false,
    'rating' => 0,
    'reviews' => 0,
    'target' => 'availability',
    'ready' => true,
    'pendingLabel' => 'Check availability',
    'guestsLabel' => null,
    'note' => null,
    'cancellation' => null,
])

@php
    $price = (float) $price;
    $rating = (float) $rating;
@endphp

<div
    x-data="{
        checkin: null,
        checkout: null,
        total: 0,
        nightly: @js($price),
        ready: @js((bool) $ready),
        target: @js($target),
        get nights() {
            if (! this.checkin || ! this.checkout) return 0;
            return Math.max(0, Math.round((new Date(this.checkout + 'T00:00:00') - new Date(this.checkin + 'T00:00:00')) / 86400000));
        },
        fmt(date) {
            return date ? new Date(date + 'T00:00:00').toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' }) : 'Add date';
        },
        money(value) { return @js($currency) + ' ' + Math.round(value).toLocaleString('en-US') },
        get stayTotal() { return this.total > 0 ? this.total : this.nightly * this.nights },
        jump() { document.getElementById(this.target)?.scrollIntoView({ behavior: 'smooth', block: 'start' }) },
        reserve() {
            const calendar = document.querySelector('#' + this.target + ' [wire\\:id]');
            if (! this.ready || ! this.nights || ! calendar) { this.jump(); return }
            window.Livewire.find(calendar.getAttribute('wire:id')).call('book');
            this.jump();
        },
    }"
    x-on:stay-selected.camel.window="checkin = $event.detail.checkin; checkout = $event.detail.checkout; total = $event.detail.total ?? 0"
    data-no-reveal
    {{ $attributes->class(['lg:sticky lg:top-[9.5rem]']) }}
>
    <aside class="dv-panel-shadow rounded-[1.2rem] border border-line bg-white p-6" aria-label="Book this stay">
        <div class="flex items-end justify-between gap-3">
            <p class="font-dm-serif text-[1.65rem] leading-none text-forest">
                @if ($from)<small class="mr-1 font-sans text-[11px] font-semibold text-muted">From</small>@endif
                @if ($price > 0)
                    {{ $currency }} {{ number_format($price) }}
                @else
                    Price on request
                @endif
                <small class="font-sans text-[11px] font-semibold text-muted">/ night</small>
            </p>
            @if ($rating > 0)
                <span class="flex shrink-0 items-center gap-1 text-[11px] text-muted">
                    <x-line-icon name="star" :size="13" filled class="text-clay" />
                    {{ number_format($rating, 1) }} · {{ number_format((int) $reviews) }} reviews
                </span>
            @endif
        </div>

        @if ($note)
            <p class="mt-3 rounded-[0.7rem] bg-sand/60 px-3 py-2 text-[12px] font-semibold text-forest">{{ $note }}</p>
        @endif

        <div class="mt-5 grid grid-cols-2 overflow-hidden rounded-[0.8rem] border border-line">
            <button type="button" x-on:click="jump()" class="flex flex-col items-start border-r border-line p-3 text-left transition-colors hover:bg-cream">
                <span class="text-[9.5px] font-extrabold uppercase tracking-[0.1em] text-ink">Check in</span>
                <b class="mt-1 text-[12.5px]" :class="checkin ? 'text-ink' : 'font-semibold text-muted'" x-text="fmt(checkin)">Add date</b>
            </button>
            <button type="button" x-on:click="jump()" class="flex flex-col items-start p-3 text-left transition-colors hover:bg-cream">
                <span class="text-[9.5px] font-extrabold uppercase tracking-[0.1em] text-ink">Check out</span>
                <b class="mt-1 text-[12.5px]" :class="checkout ? 'text-ink' : 'font-semibold text-muted'" x-text="fmt(checkout)">Add date</b>
            </button>
            <div class="col-span-2 border-t border-line">
                @isset($guests)
                    {{ $guests }}
                @else
                    <div class="flex flex-col p-3">
                        <span class="text-[9.5px] font-extrabold uppercase tracking-[0.1em] text-ink">Guests</span>
                        <b class="mt-1 text-[12.5px] text-ink">{{ $guestsLabel ?? 'Add guests at checkout' }}</b>
                    </div>
                @endisset
            </div>
        </div>

        <button type="button" x-on:click="reserve()" class="dv-btn dv-btn-primary mt-4 w-full !min-h-[3.2rem] !text-[13px]">
            <span x-text="ready && nights ? 'Reserve your stay' : @js($pendingLabel)">{{ $pendingLabel }}</span>
            <x-line-icon name="arrow" :size="17" />
        </button>
        <p class="py-3 text-center text-[11px] text-muted">You won't be charged yet</p>

        <div class="flex flex-col gap-2.5 text-[12.5px] text-muted">
            <template x-if="nights > 0 && stayTotal > 0">
                <div class="flex flex-col gap-2.5">
                    <p class="flex justify-between gap-3"><span x-text="money(stayTotal / nights) + ' × ' + nights + (nights === 1 ? ' night' : ' nights')"></span><b class="text-ink" x-text="money(stayTotal)"></b></p>
                    <p class="flex justify-between gap-3"><span>Service fee</span><b class="text-ink">Included</b></p>
                    <p class="mt-1 flex justify-between gap-3 border-t border-line pt-3 text-[13px] font-extrabold text-ink"><span>Total</span><span x-text="money(stayTotal)"></span></p>
                    <p class="text-[11px] leading-relaxed">Average nightly rate for your dates; weekends and seasons are priced individually.</p>
                </div>
            </template>
            <template x-if="! (nights > 0 && stayTotal > 0)">
                <p class="rounded-[0.7rem] border border-dashed border-line px-3 py-2.5 text-center text-[11.5px] leading-relaxed">Pick your dates in the calendar to see the price for your stay.</p>
            </template>
        </div>

        <div class="mt-4 flex items-center justify-center gap-2 rounded-[0.7rem] bg-cream px-3 py-2.5 text-center text-[11px] font-semibold text-forest">
            <x-line-icon name="check" :size="15" />
            <span>Secure booking{{ $cancellation ? ' · '.\Illuminate\Support\Str::before($cancellation, ';') : '' }}</span>
        </div>
    </aside>

    {{-- Mobile / tablet sticky book bar --}}
    <div class="fixed inset-x-0 bottom-[4.8rem] z-40 border-t border-line bg-surface/95 px-4 py-3 backdrop-blur-xl md:bottom-0 lg:hidden">
        <div class="mx-auto flex max-w-3xl items-center justify-between gap-4">
            <div class="min-w-0">
                <span class="text-[11px] text-muted" x-text="nights ? fmt(checkin) + ' – ' + fmt(checkout) : @js($from ? 'From' : 'Per night')">{{ $from ? 'From' : 'Per night' }}</span>
                <p class="truncate font-dm-serif text-[1.25rem] leading-tight text-forest">
                    @if ($price > 0)
                        {{ $currency }} {{ number_format($price) }} <small class="font-sans text-[11px] font-semibold text-muted">/ night</small>
                    @else
                        Price on request
                    @endif
                </p>
            </div>
            <button type="button" x-on:click="reserve()" class="dv-btn dv-btn-primary shrink-0">
                <span x-text="ready && nights ? 'Reserve' : @js($pendingLabel)">{{ $pendingLabel }}</span>
            </button>
        </div>
    </div>
</div>
