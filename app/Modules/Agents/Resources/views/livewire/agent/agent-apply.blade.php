<div>
    <section class="dv-section dv-foryou-head !pb-8">
        <div class="relative max-w-[46rem]">
            <p class="dv-eyebrow dv-magic-in" style="--i: 0">Agent studio</p>
            <h1 class="dv-magic-in mt-3 font-dm-serif text-[clamp(2.4rem,5vw,4.2rem)] leading-[0.98] tracking-[-0.02em] text-forest" style="--i: 1">
                Sell journeys built from <span class="italic text-clay">real places.</span>
            </h1>
            <p class="dv-magic-in mt-4 max-w-[38rem] text-[14px] leading-relaxed text-muted" style="--i: 2">
                As a HolidayHub agent you string together stays and experiences that are already live here into multi-day journeys, add your margin, and guests book the whole thing in one go. Each host gets their booking; you get your commission.
            </p>
        </div>
    </section>

    <section class="dv-section !pt-2">
        <div class="grid gap-8 lg:grid-cols-[1fr_20rem]">
            <form wire:submit="apply" class="rounded-[1.4rem] border border-line bg-white p-5 shadow-[0_20px_60px_rgba(20,36,28,0.06)] sm:p-8">
                <p class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-clay">Your agency</p>
                <h2 class="mt-1 font-dm-serif text-[clamp(1.6rem,3vw,2.1rem)] leading-tight text-forest">Tell us who you are</h2>

                <div class="mt-7 space-y-6">
                    <label class="block">
                        <span class="auth-label">Agency or trading name</span>
                        <input type="text" wire:model.blur="agency_name" placeholder="Swahili Coast Journeys" @class(['auth-input !h-14 !text-[18px] font-dm-serif', 'auth-input-error' => $errors->has('agency_name')])>
                        @error('agency_name') <span class="auth-error">{{ $message }}</span> @enderror
                    </label>

                    <div>
                        <span class="auth-label">How you work</span>
                        <div class="mt-1 grid gap-2 sm:grid-cols-3">
                            @foreach ($businessTypes as $key => [$label, $line])
                                <label class="cursor-pointer" wire:key="bt-{{ $key }}">
                                    <input type="radio" value="{{ $key }}" wire:model.live="business_type" class="peer sr-only">
                                    <span class="block h-full rounded-[0.9rem] border border-line bg-white p-3.5 transition-colors peer-checked:border-clay peer-checked:bg-[#FBE7DF] hover:border-forest">
                                        <span class="block text-[13px] font-extrabold text-ink">{{ $label }}</span>
                                        <span class="block text-[11px] text-muted">{{ $line }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('business_type') <span class="auth-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <label class="block">
                            <span class="auth-label">First name</span>
                            <input type="text" wire:model.blur="first_name" @class(['auth-input', 'auth-input-error' => $errors->has('first_name')])>
                            @error('first_name') <span class="auth-error">{{ $message }}</span> @enderror
                        </label>
                        <label class="block">
                            <span class="auth-label">Last name</span>
                            <input type="text" wire:model.blur="last_name" @class(['auth-input', 'auth-input-error' => $errors->has('last_name')])>
                            @error('last_name') <span class="auth-error">{{ $message }}</span> @enderror
                        </label>
                        <label class="block">
                            <span class="auth-label">Licence or registration number <span class="font-medium normal-case tracking-normal text-muted">(if you have one)</span></span>
                            <input type="text" wire:model.blur="agency_license" placeholder="KATA 0421" @class(['auth-input', 'auth-input-error' => $errors->has('agency_license')])>
                            @error('agency_license') <span class="auth-error">{{ $message }}</span> @enderror
                        </label>
                        <label class="block">
                            <span class="auth-label">Phone</span>
                            <input type="tel" wire:model.blur="phone" placeholder="+254 712 000 000" @class(['auth-input', 'auth-input-error' => $errors->has('phone')])>
                            @error('phone') <span class="auth-error">{{ $message }}</span> @enderror
                        </label>
                        <label class="block">
                            <span class="auth-label">Country</span>
                            <select wire:model.blur="country_code" @class(['auth-input', 'auth-input-error' => $errors->has('country_code')])>
                                <option value="">Choose a country…</option>
                                @foreach ($countries as $code => $name)
                                    <option value="{{ $code }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            @error('country_code') <span class="auth-error">{{ $message }}</span> @enderror
                        </label>
                        <label class="block">
                            <span class="auth-label">Town or city</span>
                            <input type="text" wire:model.blur="city" placeholder="Mombasa" @class(['auth-input', 'auth-input-error' => $errors->has('city')])>
                            @error('city') <span class="auth-error">{{ $message }}</span> @enderror
                        </label>
                        <label class="block sm:col-span-2">
                            <span class="auth-label">What you’re known for <span class="font-medium normal-case tracking-normal text-muted">(optional)</span></span>
                            <input type="text" wire:model.blur="specialization" placeholder="Coast and safari honeymoons" @class(['auth-input', 'auth-input-error' => $errors->has('specialization')])>
                            @error('specialization') <span class="auth-error">{{ $message }}</span> @enderror
                        </label>
                        <label class="block sm:col-span-2" x-data="{ count: @js(mb_strlen($bio)) }">
                            <span class="auth-label">A line about you <span class="font-medium normal-case tracking-normal text-muted">(optional)</span></span>
                            <textarea wire:model.blur="bio" x-on:input="count = $event.target.value.length" rows="2" maxlength="250" placeholder="Twelve years planning East African journeys for families who want the wild and the beach in one trip." @class(['auth-input !h-auto py-3 leading-relaxed', 'auth-input-error' => $errors->has('bio')])></textarea>
                            <span class="mt-1.5 flex justify-end text-[11px] text-muted"><span x-text="count + ' / 250'"></span></span>
                            @error('bio') <span class="auth-error">{{ $message }}</span> @enderror
                        </label>
                    </div>

                    <label class="flex cursor-pointer items-start gap-3 rounded-[1rem] border border-line p-4 transition-colors hover:border-forest has-[:checked]:border-forest has-[:checked]:bg-[#E7EEE8]">
                        <input type="checkbox" wire:model="agree" class="auth-check mt-0.5">
                        <span class="text-[12.5px] leading-relaxed text-ink">I only sell journeys I can stand behind, and I’ll answer guests who book them. I understand HolidayHub verifies my agency before my packages go live.</span>
                    </label>
                    @error('agree') <span class="auth-error">{{ $message }}</span> @enderror
                </div>

                <div class="mt-8 flex flex-wrap items-center justify-between gap-3 border-t border-line pt-6">
                    <a href="{{ route('dashboard') }}" class="dv-btn dv-btn-ghost"><span class="rotate-180"><x-line-icon name="arrow" :size="15" /></span> Not now</a>
                    <button type="submit" wire:loading.attr="disabled" wire:target="apply" class="dv-btn dv-btn-primary min-h-[3.3rem] px-7 text-[13px]">
                        <span wire:loading.remove wire:target="apply">Open my agent studio</span>
                        <span wire:loading wire:target="apply">Setting up…</span>
                    </button>
                </div>
            </form>

            <aside class="space-y-4 lg:sticky lg:top-28 lg:self-start">
                @foreach ([
                    ['compass', 'Build from what’s live', 'Every stay and experience in your journey is a real HolidayHub listing, priced live for the dates guests choose.'],
                    ['ticket', 'Guests book once', 'One booking and one payment for the whole journey. If any part is taken, nothing is booked.'],
                    ['star', 'Your margin, your way', 'Add a percentage or a fixed amount on top. It’s recorded as your commission on every booking.'],
                    ['check', 'Verified agents only', 'Build straight away. Our team verifies your agency before your first journey goes live.'],
                ] as [$icon, $title, $text])
                    <div class="rounded-[1rem] border border-line bg-white p-4">
                        <span class="grid h-9 w-9 place-items-center rounded-full bg-cream text-clay"><x-line-icon :name="$icon" :size="16" /></span>
                        <p class="mt-3 text-[13.5px] font-extrabold text-ink">{{ $title }}</p>
                        <p class="mt-1 text-[12px] leading-relaxed text-muted">{{ $text }}</p>
                    </div>
                @endforeach
            </aside>
        </div>
    </section>
</div>
