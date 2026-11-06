@php
    use App\Modules\Booking\Presentation\Support\TripPresenter;

    $trip = TripPresenter::make($booking, 1800);
    $thumb = TripPresenter::image($booking->bookable, (string) $booking->bookable_type, 600);
    $checkIn = \Carbon\Carbon::parse($booking->check_in_date);
    $checkOut = \Carbon\Carbon::parse($booking->check_out_date);
    $money = fn ($value) => $trip['currency'].' '.number_format((float) $value, 2);

    $subtotal = $trip['per_night'] > 0 && $trip['nights'] > 0 ? $trip['per_night'] * $trip['nights'] : null;
    $basePrice = $booking->getAttribute('base_price');
    $taxAmount = $booking->getAttribute('tax_amount');
    $discount = (float) $booking->getAttribute('discount_amount');
    $extras = $subtotal !== null && $basePrice === null && $taxAmount === null ? round($trip['total'] - $subtotal + $discount, 2) : null;
@endphp

<div>
    {{-- ─────────────────────────────  Hero  ───────────────────────────── --}}
    <section class="dv-hero !min-h-[24rem] md:!min-h-[30rem]">
        <img src="{{ $trip['image'] }}" alt="{{ $booking->bookable->name ?? 'Bookable' }} image">
        <div class="dv-hero-shade" aria-hidden="true"></div>

        <div class="dv-hero-copy !max-w-[52rem] self-end !pb-12 md:!pb-16">
            <div class="dv-magic-in flex flex-wrap items-center gap-3" style="--i: 0">
                <a href="{{ route('bookings') }}" class="inline-flex items-center gap-1.5 rounded-full border border-white/25 bg-black/30 px-3 py-1.5 text-[11px] font-bold text-white backdrop-blur-md transition-colors hover:bg-black/45">
                    <span class="rotate-180"><x-line-icon name="arrow" :size="14" /></span> My trips
                </a>
                <span class="inline-flex items-center rounded-full px-3 py-1.5 text-[11px] font-extrabold {{ $trip['status_class'] }}">
                    {{ ucfirst($booking->status) }}
                </span>
            </div>

            <p class="dv-eyebrow-light dv-magic-in mt-6" style="--i: 1">Booking details · {{ ucfirst($booking->bookable->bookable_type ?? $trip['kind']) }}</p>
            <h1 class="dv-magic-in mt-3 font-dm-serif text-[clamp(2.3rem,5.4vw,4.6rem)] leading-[0.98] tracking-[-0.03em]" style="--i: 2">
                {{ $booking->bookable->name ?? 'N/A' }}
            </h1>
            <p class="dv-magic-in mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-[13px] text-white/80" style="--i: 3">
                @if ($trip['place'])
                    <span class="inline-flex items-center gap-1.5"><x-line-icon name="map" :size="15" /> {{ $trip['place'] }}</span>
                @endif
                <span class="inline-flex items-center gap-1.5">
                    <x-line-icon name="ticket" :size="15" /> Reference: <span class="font-mono font-bold text-white">{{ $booking->confirmation_code }}</span>
                </span>
            </p>
        </div>
    </section>

    {{-- ───────────────────────────  Details  ──────────────────────────── --}}
    <section class="dv-section pt-10 md:pt-14">
        <div class="mx-auto grid max-w-[1100px] gap-10 lg:grid-cols-[1.3fr_0.8fr] lg:gap-16">
            <div class="min-w-0">
                @if (session()->has('message'))
                    <div class="mb-6 flex items-start gap-3 rounded-[1rem] border border-forest/20 bg-[#E3EFE7] px-4 py-3 text-[13px] font-semibold text-forest" role="alert">
                        <x-line-icon name="check" :size="18" :stroke="2.4" />
                        <span>{{ session('message') }}</span>
                    </div>
                @endif

                {{-- Ticket --}}
                <div class="overflow-hidden rounded-[1.1rem] border border-line bg-white shadow-[0_20px_60px_rgba(20,36,28,0.1)]">
                    <div class="grid grid-cols-[5.5rem_1fr] items-center gap-4 p-4 sm:grid-cols-[7rem_1fr]">
                        <img src="{{ $thumb }}" alt="" class="h-[5.2rem] w-full rounded-[0.7rem] object-cover sm:h-[6rem]">
                        <div class="min-w-0">
                            <p class="text-[10px] font-extrabold uppercase tracking-[0.1em] text-clay">{{ $trip['dates'] }}</p>
                            <p class="mt-1 truncate font-dm-serif text-[1.35rem] leading-tight text-ink">{{ $booking->bookable->name ?? 'N/A' }}</p>
                            <p class="mt-0.5 truncate text-[12px] text-muted">{{ $trip['kind'] }}@if ($trip['place']) · {{ $trip['place'] }}@endif</p>
                        </div>
                    </div>
                    <div class="relative grid grid-cols-2 gap-y-4 border-t border-dashed border-line bg-cream px-5 py-4 sm:grid-cols-4">
                        <span class="absolute -left-2.5 -top-2.5 h-5 w-5 rounded-full border border-line bg-cream" aria-hidden="true"></span>
                        <span class="absolute -right-2.5 -top-2.5 h-5 w-5 rounded-full border border-line bg-cream" aria-hidden="true"></span>
                        <div>
                            <p class="text-[9px] font-extrabold uppercase tracking-[0.12em] text-clay">Check-in</p>
                            <p class="mt-1 text-[13px] font-extrabold text-ink">{{ $checkIn->format('M d, Y') }}</p>
                        </div>
                        <div>
                            <p class="text-[9px] font-extrabold uppercase tracking-[0.12em] text-clay">Check-out</p>
                            <p class="mt-1 text-[13px] font-extrabold text-ink">{{ $checkOut->format('M d, Y') }}</p>
                        </div>
                        <div>
                            <p class="text-[9px] font-extrabold uppercase tracking-[0.12em] text-clay">Guests</p>
                            <p class="mt-1 text-[13px] font-extrabold text-ink">{{ $trip['guests_label'] }}</p>
                        </div>
                        <div>
                            <p class="text-[9px] font-extrabold uppercase tracking-[0.12em] text-clay">Booking ID</p>
                            <p class="mt-1 truncate font-mono text-[13px] font-extrabold text-ink">{{ $booking->confirmation_code }}</p>
                        </div>
                    </div>
                </div>

                {{-- 1. Dates --}}
                <div class="mt-10 border-t border-line py-7">
                    <div class="flex items-center gap-3">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-forest text-[11px] font-extrabold text-white">1</span>
                        <div>
                            <h2 class="font-dm-serif text-[1.45rem] leading-tight text-ink">Your dates</h2>
                            <p class="text-[11px] text-muted">{{ $trip['nights'] }} {{ Str::plural('night', $trip['nights']) }}@if ($trip['countdown']) · starts {{ $trip['countdown'] === 1 ? 'tomorrow' : 'in '.$trip['countdown'].' days' }}@endif</p>
                        </div>
                    </div>
                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-[0.9rem] border border-line bg-surface p-4">
                            <p class="flex items-center gap-1.5 text-[10px] font-extrabold uppercase tracking-[0.1em] text-forest"><x-line-icon name="calendar" :size="14" /> Check-in</p>
                            <p class="mt-2 font-dm-serif text-[1.25rem] text-ink">{{ $checkIn->format('D, M d, Y') }}</p>
                            @if ($trip['check_in_time'])<p class="mt-0.5 text-[12px] text-muted">From {{ $trip['check_in_time'] }}</p>@endif
                        </div>
                        <div class="rounded-[0.9rem] border border-line bg-surface p-4">
                            <p class="flex items-center gap-1.5 text-[10px] font-extrabold uppercase tracking-[0.1em] text-forest"><x-line-icon name="calendar" :size="14" /> Check-out</p>
                            <p class="mt-2 font-dm-serif text-[1.25rem] text-ink">{{ $checkOut->format('D, M d, Y') }}</p>
                            @if ($trip['check_out_time'])<p class="mt-0.5 text-[12px] text-muted">By {{ $trip['check_out_time'] }}</p>@endif
                        </div>
                    </div>
                </div>

                {{-- 2. Travellers --}}
                <div class="border-t border-line py-7">
                    <div class="flex items-center gap-3">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-forest text-[11px] font-extrabold text-white">2</span>
                        <div>
                            <h2 class="font-dm-serif text-[1.45rem] leading-tight text-ink">Travelers</h2>
                            <p class="text-[11px] text-muted">Who is coming along</p>
                        </div>
                    </div>
                    <div class="mt-5 flex flex-wrap gap-3">
                        <span class="inline-flex items-center gap-2 rounded-full border border-line bg-white px-4 py-2.5 text-[13px] font-bold text-ink">
                            <span class="text-clay"><x-line-icon name="user" :size="16" /></span>
                            {{ $booking->guests_adults ?? 'N/A' }} Adults
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-full border border-line bg-white px-4 py-2.5 text-[13px] font-bold text-ink">
                            <span class="text-clay"><x-line-icon name="users" :size="16" /></span>
                            {{ $booking->guests_children ?? 'N/A' }} Children
                        </span>
                    </div>

                    {{-- Offer Details (if applicable) --}}
                    @if ($booking->offer)
                        <div class="mt-5 flex items-start gap-3 rounded-[0.9rem] border border-dashed border-clay/40 bg-[#FBEDE7] p-4">
                            <span class="mt-0.5 text-clay"><x-line-icon name="ticket" :size="20" /></span>
                            <div>
                                <p class="text-[13px] font-extrabold text-clay-dark">{{ $booking->offer->name ?? 'Special Offer' }}</p>
                                <p class="mt-1 text-[12px] leading-relaxed text-[#8C4A33]">{{ $booking->offer->description ?? 'You received a special discount on this booking!' }}</p>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- 3. Good to know --}}
                <div class="border-t border-line py-7">
                    <div class="flex items-center gap-3">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-forest text-[11px] font-extrabold text-white">3</span>
                        <div>
                            <h2 class="font-dm-serif text-[1.45rem] leading-tight text-ink">Good to know</h2>
                            <p class="text-[11px] text-muted">Cancellation and arrival</p>
                        </div>
                    </div>
                    <div class="mt-5 flex items-start gap-3 rounded-[0.9rem] bg-sand/60 p-4 text-[13px] leading-relaxed text-ink">
                        <span class="mt-0.5 text-forest"><x-line-icon name="leaf" :size="18" /></span>
                        <div>
                            <p class="font-semibold">{{ $trip['policy'] ?: 'Cancellation terms are set by the provider of this booking.' }}</p>
                            @if ($trip['check_in_time'] || $trip['check_out_time'])
                                <p class="mt-1 text-[12px] text-muted">
                                    @if ($trip['check_in_time'])Check-in from {{ $trip['check_in_time'] }}.@endif
                                    @if ($trip['check_out_time']) Check-out by {{ $trip['check_out_time'] }}.@endif
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- ─────────────────────────  Summary  ───────────────────────── --}}
            <aside class="lg:sticky lg:top-[110px] lg:self-start">
                <div class="dv-panel dv-panel-shadow !bg-surface p-5">
                    <div class="grid grid-cols-[5.5rem_1fr] items-center gap-3">
                        <img src="{{ $thumb }}" alt="" class="h-[5.5rem] w-full rounded-[0.7rem] object-cover">
                        <div class="min-w-0">
                            <p class="text-[10px] font-semibold text-muted">{{ ucfirst($booking->bookable->bookable_type ?? $trip['kind']) }}</p>
                            <p class="mt-0.5 font-dm-serif text-[1.15rem] leading-tight text-ink">{{ $booking->bookable->name ?? 'N/A' }}</p>
                            @if ($trip['place'])
                                <p class="mt-1 flex items-center gap-1 text-[11px] text-muted"><span class="text-clay"><x-line-icon name="map" :size="12" /></span> {{ $trip['place'] }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="mt-5 space-y-3 border-y border-line py-4 text-[12px]">
                        @if ($subtotal !== null)
                            <p class="flex justify-between gap-3 text-muted">
                                <span>{{ $money($trip['per_night']) }} × {{ $trip['nights'] }} {{ Str::plural('night', $trip['nights']) }}</span>
                                <span class="font-semibold text-ink">{{ $money($subtotal) }}</span>
                            </p>
                        @endif
                        @if ($basePrice !== null)
                            <p class="flex justify-between gap-3 text-muted"><span>Subtotal</span><span class="font-semibold text-ink">{{ $money($basePrice) }}</span></p>
                        @endif
                        @if ($taxAmount !== null)
                            <p class="flex justify-between gap-3 text-muted"><span>Taxes &amp; Fees</span><span class="font-semibold text-ink">{{ $money($taxAmount) }}</span></p>
                        @elseif ($extras !== null && $extras > 0)
                            <p class="flex justify-between gap-3 text-muted"><span>Taxes, fees &amp; extras</span><span class="font-semibold text-ink">{{ $money($extras) }}</span></p>
                        @endif
                        @if ($discount > 0)
                            <p class="flex justify-between gap-3 font-semibold text-forest"><span>Discount</span><span>− {{ $money($discount) }}</span></p>
                        @endif
                        @if ($subtotal === null && $basePrice === null && $taxAmount === null && $discount <= 0)
                            <p class="text-muted">Price breakdown isn’t available for this booking.</p>
                        @endif
                    </div>

                    <div class="flex items-end justify-between gap-3 pt-4">
                        <div>
                            <p class="text-[10px] font-extrabold uppercase tracking-[0.12em] text-clay">Booking Total</p>
                            @if ($booking->payment_status)
                                <p class="mt-1 text-[11px] text-muted">Payment {{ str_replace('_', ' ', $booking->payment_status) }}</p>
                            @endif
                        </div>
                        <p class="font-dm-serif text-[1.9rem] leading-none text-forest">{{ $money($trip['total']) }}</p>
                    </div>

                    <div class="mt-6 space-y-3">
                        {{-- Rebook Button --}}
                        <button type="button" wire:click="rebook" wire:loading.attr="disabled" class="dv-btn dv-btn-primary w-full">
                            Rebook This Trip <x-line-icon name="arrow" :size="16" />
                        </button>

                        {{-- Other Actions --}}
                        <button type="button" class="dv-btn dv-btn-ghost w-full">
                            Download Invoice
                        </button>

                        @if ($trip['url'])
                            <a href="{{ $trip['url'] }}" class="flex items-center justify-center gap-1.5 pt-1 text-[12px] font-bold text-forest underline-offset-4 hover:underline">
                                View the {{ strtolower($trip['kind']) === 'b&b' ? 'B&B' : strtolower($trip['kind']) }} <x-line-icon name="arrow-up-right" :size="14" />
                            </a>
                        @endif
                    </div>

                    {{-- Cancellation: the listing's own policy and what cancelling now would refund --}}
                    @php
                        $cancellation = $this->cancellation;
                        $terms = $cancellation->context;
                    @endphp
                    <div class="mt-6 rounded-[0.9rem] border border-line bg-cream/60 px-4 py-3.5 text-[12px] leading-relaxed text-ink">
                        <p class="text-[10.5px] font-extrabold uppercase tracking-[0.14em] text-muted">Cancellation policy</p>
                        @if ($booking->status === 'cancelled')
                            <p class="mt-1.5">Cancelled{{ $booking->cancelled_at ? ' on '.$booking->cancelled_at->format('j M Y') : '' }}.
                                @if (in_array($booking->payment_status, ['refunded', 'partially_refunded'], true))
                                    {{ $booking->payment_status === 'refunded' ? 'Your payment was refunded.' : 'Part of your payment was refunded.' }}
                                @endif
                            </p>
                        @elseif ($terms['text'] ?? null)
                            <p class="mt-1.5">{{ $terms['text'] }}</p>
                        @endif
                    </div>

                    @if ($booking->package_booking_id && $booking->packageBooking)
                        <a href="{{ route('bookings-show', $booking->packageBooking) }}" class="mt-3 flex items-center justify-center gap-1.5 text-[12px] font-bold text-forest underline-offset-4 hover:underline">
                            Part of a journey: manage it from the journey booking <x-line-icon name="arrow" :size="14" />
                        </a>
                    @elseif ($cancellation->allowed)
                        <div x-data="{ open: false }" x-on:booking-cancelled.window="open = false" class="mt-3">
                            <button type="button" x-on:click="open = true" class="w-full rounded-full py-2.5 text-[12.5px] font-bold text-clay-dark transition-colors hover:bg-[#FBE9E2]">
                                Cancel this booking
                            </button>

                            <template x-teleport="body">
                                <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-[120] flex items-center justify-center bg-[#0C1511]/60 p-4 backdrop-blur-sm" x-on:keydown.escape.window="open = false" role="dialog" aria-modal="true" aria-labelledby="cancel-title">
                                    <div x-on:click.outside="open = false" class="w-full max-w-[28rem] rounded-[1.3rem] bg-white p-6 text-ink shadow-[0_30px_80px_rgba(8,22,17,0.35)] sm:p-7">
                                        <p class="text-[10.5px] font-extrabold uppercase tracking-[0.16em] text-clay">Cancel booking</p>
                                        <h2 id="cancel-title" class="mt-1.5 font-dm-serif text-[1.7rem] leading-tight text-forest">Cancel {{ $booking->bookable->name ?? 'this booking' }}?</h2>

                                        <div @class([
                                            'mt-4 rounded-[0.9rem] px-4 py-3 text-[13px] font-semibold',
                                            'bg-[#E3EFE7] text-forest' => ($terms['percent'] ?? 0) >= 100 || ! ($terms['paid'] ?? false),
                                            'bg-[#F6EEDD] text-[#6B5230]' => ($terms['paid'] ?? false) && ($terms['percent'] ?? 0) > 0 && ($terms['percent'] ?? 0) < 100,
                                            'bg-[#FBE9E2] text-clay-dark' => ($terms['paid'] ?? false) && ($terms['percent'] ?? 0) === 0,
                                        ])>{{ $cancellation->message }}</div>
                                        <p class="mt-3 text-[12px] leading-relaxed text-muted">{{ $terms['text'] ?? '' }}</p>

                                        <label for="cancel-reason" class="mt-4 block text-[12px] font-bold text-ink">Anything the host should know? <span class="font-normal text-muted">(optional)</span></label>
                                        <textarea id="cancel-reason" wire:model="cancelReason" rows="3" maxlength="500" class="auth-input mt-1.5 w-full !rounded-[0.8rem] text-[13px]" placeholder="e.g. our flights changed"></textarea>

                                        @error('cancel')
                                            <p class="mt-3 rounded-[0.8rem] bg-[#FBE9E2] px-3 py-2 text-[12px] font-semibold text-clay-dark">{{ $message }}</p>
                                        @enderror

                                        <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                            <button type="button" x-on:click="open = false" class="dv-btn dv-btn-ghost">Keep my booking</button>
                                            <button type="button" wire:click="cancel" wire:loading.attr="disabled" class="dv-btn bg-clay text-white hover:bg-clay-dark">
                                                <span wire:loading.remove wire:target="cancel">Yes, cancel it</span>
                                                <span wire:loading wire:target="cancel">Cancelling…</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    @elseif ($booking->status !== 'cancelled')
                        <p class="mt-3 text-center text-[11.5px] leading-relaxed text-muted">{{ $cancellation->message }}</p>
                    @endif
                </div>
            </aside>
        </div>
    </section>
</div>
