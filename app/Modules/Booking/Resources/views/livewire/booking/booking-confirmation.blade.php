@php
    use App\Modules\Booking\Presentation\Support\TripPresenter;

    $trip = TripPresenter::make($booking, 2000);
    $thumb = TripPresenter::image($booking->bookable, (string) $booking->bookable_type, 600);
    $firstName = $booking->guest?->first_name ?: Str::before(trim((string) auth()->user()?->name), ' ');
    $where = $trip['city'] ?: ($booking->bookable->name ?? 'Your escape');
@endphp

<div>
    <section class="relative isolate flex min-h-[calc(100dvh-76px)] items-center justify-center overflow-hidden bg-forest px-4 text-white md:min-h-[calc(100dvh-88px)]">
        <img src="{{ $trip['image'] }}" alt="{{ $booking->bookable->name ?? 'Your booking' }}" class="absolute inset-0 -z-20 h-full w-full object-cover">
        <div class="absolute inset-0 -z-10 bg-[linear-gradient(90deg,rgba(8,27,21,0.92),rgba(8,27,21,0.62)),linear-gradient(0deg,rgba(8,27,21,0.55),transparent_45%)]" aria-hidden="true"></div>

        <div class="flex w-full max-w-[36rem] flex-col items-center py-14 text-center md:py-20">
            <span class="dv-magic-in grid h-16 w-16 place-items-center rounded-full bg-clay shadow-[0_0_0_9px_rgba(216,95,60,0.18)]" style="--i: 0">
                <x-line-icon name="check" :size="32" :stroke="2.6" />
            </span>

            <p class="dv-eyebrow-light dv-magic-in mt-7" style="--i: 1">Your escape is confirmed</p>
            <h1 class="dv-magic-in mt-3 font-dm-serif text-[clamp(2.7rem,7vw,5rem)] leading-[0.95] tracking-[-0.03em]" style="--i: 2">
                {{ $where }} is waiting{{ $firstName ? ',' : '.' }}
                @if ($firstName)
                    <br><span class="italic text-[#F6C6B8]">{{ $firstName }}.</span>
                @endif
            </h1>
            <p class="dv-magic-in mt-5 max-w-[29rem] text-[14px] leading-relaxed text-white/75" style="--i: 3">
                Your trip has been successfully booked{{ $booking->bookable ? ' — '.$booking->bookable->name.' is all set' : '' }}. Now the daydreaming can officially begin.
            </p>

            {{-- Ticket --}}
            <div class="dv-magic-in mt-9 w-full overflow-hidden rounded-[1.1rem] bg-white/95 text-left text-ink shadow-[0_30px_80px_rgba(0,0,0,0.3)] backdrop-blur" style="--i: 4" data-no-reveal>
                <div class="grid grid-cols-[5.5rem_1fr] items-center gap-4 p-3.5 sm:grid-cols-[6.5rem_1fr]">
                    <img src="{{ $thumb }}" alt="" class="h-[5.2rem] w-full rounded-[0.7rem] object-cover">
                    <div class="min-w-0">
                        <p class="text-[10px] font-extrabold uppercase tracking-[0.1em] text-clay">{{ $trip['dates'] }}</p>
                        <p class="mt-1 truncate font-dm-serif text-[1.3rem] leading-tight">{{ $booking->bookable->name ?? 'N/A' }}</p>
                        <p class="mt-0.5 truncate text-[12px] text-muted">{{ $trip['kind'] }}@if ($trip['place']) · {{ $trip['place'] }}@endif</p>
                    </div>
                </div>

                <dl class="relative grid grid-cols-2 gap-y-4 border-t border-dashed border-line px-5 py-4 sm:grid-cols-3">
                    <span class="absolute -left-2.5 -top-2.5 h-5 w-5 rounded-full bg-forest/80" aria-hidden="true"></span>
                    <span class="absolute -right-2.5 -top-2.5 h-5 w-5 rounded-full bg-forest/80" aria-hidden="true"></span>
                    <div>
                        <dt class="text-[9px] font-extrabold uppercase tracking-[0.12em] text-clay">Check-in</dt>
                        <dd class="mt-1 text-[13px] font-extrabold">{{ $booking->check_in_date->format('M d, Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-[9px] font-extrabold uppercase tracking-[0.12em] text-clay">Check-out</dt>
                        <dd class="mt-1 text-[13px] font-extrabold">{{ $booking->check_out_date->format('M d, Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-[9px] font-extrabold uppercase tracking-[0.12em] text-clay">Guests</dt>
                        <dd class="mt-1 text-[13px] font-extrabold">{{ $trip['guests_label'] }}</dd>
                    </div>
                    <div class="sm:col-span-1">
                        <dt class="text-[9px] font-extrabold uppercase tracking-[0.12em] text-clay">Booking Ref</dt>
                        <dd class="mt-1 truncate font-mono text-[13px] font-extrabold">#{{ $booking->confirmation_code }}</dd>
                    </div>
                    <div class="col-span-2 border-t border-line pt-3 sm:col-span-2 sm:border-0 sm:pt-0">
                        <dt class="text-[9px] font-extrabold uppercase tracking-[0.12em] text-clay">Total Amount</dt>
                        <dd class="mt-1 font-dm-serif text-[1.35rem] leading-none text-forest">{{ $booking->currency }} {{ number_format($booking->total_amount, 2) }}</dd>
                    </div>
                </dl>
            </div>

            <div class="dv-magic-in mt-7 flex w-full flex-col gap-3 sm:w-auto sm:flex-row" style="--i: 5">
                <button
                    type="button"
                    wire:click="redirectToDashboard"
                    class="dv-btn bg-cream px-7 text-forest hover:bg-white"
                >
                    OK, take me home <x-line-icon name="arrow" :size="16" />
                </button>
                <a href="{{ route('bookings-show', $booking) }}" class="dv-btn border border-white/40 px-7 text-white hover:border-white hover:bg-white/10">
                    View booking details
                </a>
            </div>
        </div>
    </section>
</div>
