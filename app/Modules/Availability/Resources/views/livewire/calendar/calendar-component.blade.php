@php
    use Carbon\Carbon;

    $month = $this->currentMonth ?? now()->startOfMonth();
    $symbol = \App\Support\Currencies::symbol($this->bookable && method_exists($this->bookable, 'getCurrency') ? $this->bookable->getCurrency() : 'KES');
    $atStart = $month->lte(now()->startOfMonth());
    $compact = fn (float $price) => $price >= 1000
        ? rtrim(rtrim(number_format($price / 1000, 1), '0'), '.').'k'
        : number_format($price);
@endphp
<div>
    <div class="rounded-[1.2rem] border border-line bg-white p-4 shadow-[0_20px_60px_rgba(20,36,28,0.08)] sm:p-6">
        {{-- Header and navigation --}}
        <div class="mb-5 flex items-center justify-between gap-3">
            <button
                type="button"
                wire:click="previousMonth"
                @disabled($atStart)
                class="grid h-10 w-10 place-items-center rounded-full border border-line text-forest transition-colors hover:border-forest disabled:cursor-not-allowed disabled:opacity-30"
                aria-label="Previous month"
            >
                <span class="rotate-180"><x-line-icon name="chevron" :size="16" /></span>
            </button>

            <div class="text-center">
                <p class="font-dm-serif text-[1.45rem] leading-none text-forest">{{ $month->format('F Y') }}</p>
                <p class="mt-1 text-[11px] text-muted">Nightly rates in {{ $symbol }} · {{ $minStay }}–{{ $maxStay }} night stays</p>
            </div>

            <button
                type="button"
                wire:click="nextMonth"
                class="grid h-10 w-10 place-items-center rounded-full border border-line text-forest transition-colors hover:border-forest"
                aria-label="Next month"
            >
                <x-line-icon name="chevron" :size="16" />
            </button>
        </div>

        {{-- Weekdays --}}
        <div class="mb-1.5 grid grid-cols-7 text-center text-[10px] font-extrabold uppercase tracking-[0.12em] text-muted">
            @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $weekday)
                <span class="py-2">{{ $weekday }}</span>
            @endforeach
        </div>

        {{-- Days --}}
        <div class="grid grid-cols-7 gap-1 sm:gap-1.5" wire:loading.class="opacity-60" wire:target="previousMonth,nextMonth,selectDate">
            @foreach ($calendarDays as $day)
                @php
                    $isCheckin = $checkinDate && $day['date_string'] === $checkinDate->toDateString();
                    $isCheckout = $checkoutDate && $day['date_string'] === $checkoutDate->toDateString();
                    $inRange = $checkinDate && $checkoutDate && Carbon::parse($day['date_string'])->between($checkinDate, $checkoutDate);
                    $outside = ! Carbon::parse($day['date_string'])->isSameMonth($month);
                @endphp

                <button
                    type="button"
                    @if ($day['is_bookable']) wire:click="selectDate('{{ $day['date_string'] }}')" @else disabled @endif
                    @class([
                        'relative flex h-14 flex-col items-center justify-center rounded-[0.7rem] text-center transition-all duration-200 sm:h-16',
                        'invisible' => $outside,
                        'cursor-not-allowed text-muted/40' => ! $outside && ! $day['is_bookable'] && ! ($isCheckin || $isCheckout),
                        'bg-clay text-white shadow-[0_10px_24px_-10px_rgba(216,95,60,0.8)]' => $isCheckin || $isCheckout,
                        'bg-[#FBE7DF] text-clay-dark' => $inRange && ! ($isCheckin || $isCheckout),
                        'bg-cream text-ink hover:-translate-y-0.5 hover:bg-sand' => $day['is_bookable'] && ! $inRange && ! ($isCheckin || $isCheckout),
                    ])
                    @if ($day['is_bookable']) aria-label="{{ Carbon::parse($day['date_string'])->format('l j F') }}, {{ $symbol }} {{ number_format($day['price']) }} a night" @endif
                >
                    <span @class(['text-[14px] font-extrabold sm:text-[15px]', 'line-through decoration-1' => ! $outside && ! $day['is_bookable'] && ! $day['is_available'] && ! Carbon::parse($day['date_string'])->isPast()])>{{ $day['day_number'] }}</span>
                    @if ($day['is_bookable'] && $day['price'] > 0)
                        <span @class(['mt-0.5 text-[9.5px] font-bold sm:text-[10px]', 'text-white/85' => $isCheckin || $isCheckout, 'text-muted' => ! ($isCheckin || $isCheckout)])>{{ $compact($day['price']) }}</span>
                    @endif
                    @if ($day['date_string'] === now()->toDateString())
                        <span class="absolute right-1.5 top-1.5 h-1.5 w-1.5 rounded-full bg-clay"></span>
                    @endif
                </button>
            @endforeach
        </div>

        {{-- Legend --}}
        <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-[11px] text-muted">
            <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded-[4px] bg-cream ring-1 ring-line"></span> Available</span>
            <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded-[4px] bg-clay"></span> Your dates</span>
            <span class="flex items-center gap-1.5"><span class="text-[12px] font-bold text-muted/50 line-through">12</span> Booked</span>
        </div>

        {{-- Selection --}}
        @if ($checkin && $checkout)
            <div class="mt-5 flex flex-wrap items-center justify-between gap-4 rounded-[0.9rem] bg-forest p-4 text-white">
                <div>
                    <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-[#F6D6C9]">Your stay</p>
                    <p class="mt-1 font-dm-serif text-[1.25rem] leading-tight">
                        {{ Carbon::parse($checkin)->format('D j M') }} – {{ Carbon::parse($checkout)->format('D j M') }}
                    </p>
                    <p class="text-[11px] text-white/70">{{ Carbon::parse($checkin)->diffInDays(Carbon::parse($checkout)) }} nights</p>
                </div>
                <button type="button" wire:click="book" wire:loading.attr="disabled" wire:target="book" class="dv-btn dv-btn-primary">
                    <span wire:loading.remove wire:target="book">Continue to booking</span>
                    <span wire:loading wire:target="book">Checking…</span>
                    <x-line-icon name="arrow" :size="16" />
                </button>
            </div>
        @elseif ($checkin)
            <p class="mt-5 rounded-[0.9rem] border border-dashed border-line px-4 py-3 text-center text-[12px] text-muted">
                Checking in {{ Carbon::parse($checkin)->format('D j M') }} — now pick your check-out date.
            </p>
        @endif

        {{-- Errors --}}
        @foreach (['stay', 'dates', 'checkin', 'checkout'] as $field)
            @error($field)
                <div class="auth-alert auth-alert-error mt-3">{{ $message }}</div>
            @enderror
        @endforeach
    </div>
</div>
