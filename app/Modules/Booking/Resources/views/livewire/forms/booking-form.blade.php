<div>
    <div
        x-data="{ show: @entangle('showModal') }"
        x-init="$watch('show', value => document.body.classList.toggle('overflow-hidden', value))"
        x-show="show"
        x-cloak
        x-trap.noscroll.inert="show"
        x-on:keydown.escape.window="show && $wire.closeModal()"
        class="fixed inset-0 z-[100] overflow-y-auto"
        role="dialog"
        aria-modal="true"
        aria-labelledby="checkout-title"
    >
        {{-- Backdrop --}}
        <div
            x-show="show"
            x-transition.opacity.duration.300ms
            class="fixed inset-0 bg-[#0C1511]/75 backdrop-blur-sm"
            x-on:click="$wire.closeModal()"
        ></div>

        {{-- Scroll container: the dialog can be taller than the screen --}}
        <div class="relative flex min-h-full items-end justify-center p-0 sm:items-center sm:p-6">
            <div
                x-show="show"
                x-transition:enter="transition duration-300 ease-out"
                x-transition:enter-start="translate-y-8 opacity-0 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
                x-transition:leave="transition duration-200 ease-in"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="translate-y-8 opacity-0 sm:translate-y-0 sm:scale-95"
                class="relative w-full max-w-[60rem] overflow-hidden rounded-t-[1.6rem] bg-surface shadow-[0_40px_120px_rgba(0,0,0,0.45)] sm:rounded-[1.6rem]"
            >
                <button
                    type="button"
                    class="absolute right-4 top-4 z-20 grid h-10 w-10 place-items-center rounded-full border border-white/30 bg-black/30 text-white backdrop-blur-md transition-colors hover:bg-black/50 md:border-line md:bg-white md:text-ink md:hover:border-forest"
                    x-on:click="$wire.closeModal()"
                    aria-label="Close checkout"
                >
                    <x-line-icon name="close" :size="18" />
                </button>

                @php $summary = $this->summary; @endphp

                @if ($summary)
                    <div class="grid md:grid-cols-[0.9fr_1.1fr]">
                        {{-- ────────────────  Trip summary  ──────────────── --}}
                        <aside class="bg-cream md:border-r md:border-line">
                            <div class="relative h-48 overflow-hidden md:h-56">
                                <img src="{{ $summary['image'] }}" alt="{{ $summary['name'] }}" class="h-full w-full object-cover">
                                <div class="absolute inset-0 bg-[linear-gradient(0deg,rgba(10,20,16,0.85),transparent_60%)]"></div>
                                <div class="absolute inset-x-5 bottom-4 text-white">
                                    <p class="text-[10px] font-extrabold uppercase tracking-[0.18em] text-[#F6D6C9]">{{ $summary['type'] }}@if ($summary['place']) · {{ $summary['place'] }}@endif</p>
                                    <p class="mt-1 font-dm-serif text-[1.6rem] leading-tight">{{ $summary['name'] }}</p>
                                </div>
                            </div>

                            <div class="space-y-4 p-5 md:p-6">
                                {{-- Dates ticket --}}
                                <div class="grid grid-cols-2 overflow-hidden rounded-[0.9rem] border border-line bg-white">
                                    <div class="border-r border-line p-3.5">
                                        <p class="text-[9.5px] font-extrabold uppercase tracking-[0.12em] text-clay">Check in</p>
                                        <p class="mt-1 text-[13px] font-bold text-ink">{{ \Carbon\Carbon::parse($checkIn)->format('D, j M Y') }}</p>
                                    </div>
                                    <div class="p-3.5">
                                        <p class="text-[9.5px] font-extrabold uppercase tracking-[0.12em] text-clay">Check out</p>
                                        <p class="mt-1 text-[13px] font-bold text-ink">{{ \Carbon\Carbon::parse($checkOut)->format('D, j M Y') }}</p>
                                    </div>
                                    <div class="col-span-2 flex items-center justify-between gap-3 border-t border-line p-3.5">
                                        <div>
                                            <p class="text-[9.5px] font-extrabold uppercase tracking-[0.12em] text-clay">Guests</p>
                                            <p class="mt-0.5 text-[11px] text-muted">
                                                {{ $summary['nights'] }} {{ Str::plural('night', $summary['nights']) }}@if ($summary['max_guests']) · up to {{ $summary['max_guests'] }} guests @endif @if ($summary['bedrooms']) · {{ $summary['bedrooms'] }} {{ Str::plural('bedroom', $summary['bedrooms']) }}@endif
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button type="button" wire:click="$set('guests', {{ max(1, $guests - 1) }})" @disabled($guests <= 1) class="grid h-8 w-8 place-items-center rounded-full border border-line text-ink transition-colors hover:border-forest disabled:opacity-40" aria-label="Fewer guests">−</button>
                                            <input
                                                type="number"
                                                id="guests"
                                                wire:model.live="guests"
                                                min="1"
                                                @if ($summary['max_guests']) max="{{ $summary['max_guests'] }}" @endif
                                                class="w-12 border-0 bg-transparent p-0 text-center text-[15px] font-extrabold text-ink focus:ring-0 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none"
                                                aria-label="Number of guests"
                                            >
                                            <button type="button" wire:click="$set('guests', {{ $guests + 1 }})" @disabled($summary['max_guests'] && $guests >= $summary['max_guests']) class="grid h-8 w-8 place-items-center rounded-full border border-line text-ink transition-colors hover:border-forest disabled:opacity-40" aria-label="More guests">+</button>
                                        </div>
                                    </div>
                                </div>
                                @error('guests') <p class="auth-error">{{ $message }}</p> @enderror
                                @error('checkIn') <p class="auth-error">{{ $message }}</p> @enderror
                                @error('checkOut') <p class="auth-error">{{ $message }}</p> @enderror

                                {{-- Price lines --}}
                                <div class="space-y-2.5 text-[13px] text-muted">
                                    @if ($summary['nightly'] > 0)
                                        <p class="flex justify-between gap-3">
                                            <span>{{ $summary['currency'] }} {{ number_format($summary['nightly']) }} × {{ $summary['nights'] }} {{ Str::plural('night', $summary['nights']) }}<span class="block text-[10.5px]">average nightly rate</span></span>
                                            <b class="text-ink">{{ $summary['currency'] }} {{ number_format($summary['total']) }}</b>
                                        </p>
                                    @endif
                                    <div class="flex items-end justify-between gap-3 border-t border-line pt-3">
                                        <span class="font-extrabold text-ink">Total</span>
                                        <span wire:loading.remove wire:target="guests" class="font-dm-serif text-[1.7rem] leading-none text-forest">{{ $summary['currency'] }} {{ number_format($summary['total']) }}</span>
                                        <span wire:loading wire:target="guests" class="text-[12px] font-semibold text-muted">Updating…</span>
                                    </div>
                                </div>
                            </div>
                        </aside>

                        {{-- ────────────────  Checkout form  ──────────────── --}}
                        <div class="p-5 pb-8 md:p-8">
                            <p class="dv-eyebrow">Almost there</p>
                            <h2 id="checkout-title" class="mt-1.5 font-dm-serif text-[clamp(1.9rem,3.5vw,2.6rem)] leading-[1.05] text-forest">Confirm your escape</h2>

                            <form wire:submit="submit" class="mt-6 space-y-6">
                                {{-- 1. Details --}}
                                <section>
                                    <div class="flex items-center gap-3">
                                        <span class="grid h-7 w-7 place-items-center rounded-full bg-forest text-[11px] font-extrabold text-white">1</span>
                                        <div>
                                            <p class="font-dm-serif text-[1.2rem] leading-tight text-ink">Who’s travelling</p>
                                            <p class="text-[11px] text-muted">We’ll send the confirmation here</p>
                                        </div>
                                    </div>
                                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                        <label class="block">
                                            <span class="auth-label">Full name</span>
                                            <input type="text" wire:model="data.name" autocomplete="name" @class(['auth-input', 'auth-input-error' => $errors->has('data.name')])>
                                            @error('data.name') <span class="auth-error">{{ $message }}</span> @enderror
                                        </label>
                                        <label class="block">
                                            <span class="auth-label">Email</span>
                                            <input type="email" wire:model="data.email" autocomplete="email" @class(['auth-input', 'auth-input-error' => $errors->has('data.email')])>
                                            @error('data.email') <span class="auth-error">{{ $message }}</span> @enderror
                                        </label>
                                    </div>
                                </section>

                                {{-- 2. Payment --}}
                                <section class="border-t border-line pt-6">
                                    <div class="flex items-center gap-3">
                                        <span class="grid h-7 w-7 place-items-center rounded-full bg-forest text-[11px] font-extrabold text-white">2</span>
                                        <div>
                                            <p class="font-dm-serif text-[1.2rem] leading-tight text-ink">Payment</p>
                                            <p class="text-[11px] text-muted">Processed when you confirm</p>
                                        </div>
                                    </div>

                                    @if ($this->testMode)
                                        <div class="mt-4 flex gap-3 rounded-[0.9rem] border border-dashed border-[#C49A5C]/60 bg-[#F6EEDD] p-4 text-[12.5px] leading-relaxed text-[#6B5330]">
                                            <x-line-icon name="check" :size="18" />
                                            <p><b class="text-[#4E3B20]">Test mode is on.</b> No card is needed and nothing is charged — your booking is confirmed straight away.</p>
                                        </div>
                                    @else
                                        <div class="mt-4 flex gap-3 rounded-[0.9rem] bg-[#E7EEE8] p-4 text-[12.5px] leading-relaxed text-forest">
                                            <x-line-icon name="check" :size="18" />
                                            <p>Your payment is handled securely by our payment provider when you confirm. HolidayHub never stores your card details.</p>
                                        </div>
                                    @endif
                                </section>

                                @error('general')
                                    <div class="auth-alert auth-alert-error">{{ $message }}</div>
                                @enderror
                                @error('bookableType') <div class="auth-alert auth-alert-error">{{ $message }}</div> @enderror

                                <button
                                    type="submit"
                                    wire:loading.attr="disabled"
                                    wire:target="submit"
                                    class="dv-btn dv-btn-primary w-full !min-h-[3.5rem] !text-[14px] disabled:cursor-wait disabled:opacity-70"
                                >
                                    <span wire:loading.remove wire:target="submit" class="inline-flex items-center gap-2">
                                        {{ $this->testMode ? 'Confirm booking' : 'Confirm and pay' }} · {{ $summary['currency'] }} {{ number_format($summary['total']) }}
                                        <x-line-icon name="arrow" :size="18" />
                                    </span>
                                    <span wire:loading wire:target="submit" class="inline-flex items-center gap-2">
                                        <span class="inline-grid animate-spin"><x-line-icon name="compass" :size="17" /></span> Confirming your stay…
                                    </span>
                                </button>

                                <p class="flex items-center justify-center gap-2 text-center text-[11px] text-muted">
                                    <x-line-icon name="check" :size="13" /> Secure booking · you can review it any time in My trips
                                </p>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="flex flex-col items-center px-8 py-16 text-center">
                        <span class="grid h-14 w-14 place-items-center rounded-full bg-cream text-clay"><x-line-icon name="calendar" :size="24" /></span>
                        <p class="mt-4 font-dm-serif text-[1.6rem] text-forest">Pick your dates first</p>
                        <p class="mt-1.5 max-w-sm text-[13px] leading-relaxed text-muted">Choose a check-in and check-out in the availability calendar, then come back to confirm.</p>
                        <button type="button" x-on:click="$wire.closeModal()" class="dv-btn dv-btn-ghost mt-6">Back to the calendar</button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
