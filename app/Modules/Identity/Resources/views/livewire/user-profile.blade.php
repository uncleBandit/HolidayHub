@php
    $initials = collect(preg_split('/\s+/', trim((string) $user->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('') ?: 'HH';
    $avatarUrl = $user->profile?->avatar_url;
    $tabs = [
        'account' => ['label' => 'Account', 'icon' => 'user'],
        'profile' => ['label' => 'Profile', 'icon' => 'leaf'],
        'password' => ['label' => 'Security', 'icon' => 'check'],
    ];
@endphp

<div>
    {{-- ────────────────────────────  Header  ───────────────────────────── --}}
    <section class="dv-section dv-foryou-head pb-0 md:pb-0">
        <div class="relative flex flex-col items-start gap-6 sm:flex-row sm:items-end">
            <div class="dv-magic-in relative shrink-0" style="--i: 0">
                <span class="dv-story-ring !h-[7.5rem] !w-[7.5rem] !p-1">
                    @if ($avatarUrl)
                        <img src="{{ $avatarUrl }}" alt="Profile Avatar">
                    @else
                        <span class="grid h-full w-full place-items-center rounded-full border-[3px] border-cream bg-forest font-dm-serif text-[2.6rem] text-cream" aria-label="Profile Avatar">{{ $initials }}</span>
                    @endif
                </span>
            </div>

            <div class="min-w-0">
                <p class="dv-eyebrow dv-magic-in" style="--i: 1">Your profile</p>
                <h1 class="dv-magic-in mt-2 break-words font-dm-serif text-[clamp(2.3rem,5vw,4.2rem)] leading-[0.98] tracking-[-0.03em] text-forest" style="--i: 2">{{ $user->name }}</h1>
                <p class="dv-magic-in mt-2 break-all text-[14px] text-muted" style="--i: 3">{{ $user->email }}</p>
            </div>
        </div>

        {{-- Tab Navigation --}}
        <div class="dv-tabs mt-10 max-w-full" role="tablist" aria-label="Profile sections" data-no-reveal>
            @foreach ($tabs as $key => $item)
                <button
                    type="button"
                    role="tab"
                    wire:click="setTab('{{ $key }}')"
                    @class(['dv-tab', 'dv-tab-active' => $tab === $key])
                    aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                >{{ $item['label'] }}</button>
            @endforeach
        </div>
    </section>

    <section class="dv-section border-t border-line pt-8 md:pt-12">
        <div class="mx-auto grid max-w-[1100px] gap-8 lg:grid-cols-[0.75fr_1.25fr] lg:gap-14">
            {{-- Side note --}}
            <aside class="lg:pt-2">
                @switch($tab)
                    @case('profile')
                        <h2 class="font-dm-serif text-[clamp(1.7rem,2.6vw,2.3rem)] leading-tight text-forest">Profile Details</h2>
                        <p class="mt-3 text-[13px] leading-relaxed text-muted">A photo, a way to reach you and a line about yourself help hosts get your stay just right.</p>
                        @break
                    @case('password')
                        <h2 class="font-dm-serif text-[clamp(1.7rem,2.6vw,2.3rem)] leading-tight text-forest">Change Password</h2>
                        <p class="mt-3 text-[13px] leading-relaxed text-muted">Choose something long and unique. You will stay signed in on this device.</p>
                        @break
                    @default
                        <h2 class="font-dm-serif text-[clamp(1.7rem,2.6vw,2.3rem)] leading-tight text-forest">Account Information</h2>
                        <p class="mt-3 text-[13px] leading-relaxed text-muted">The name and email we use for your bookings, tickets and trip updates.</p>
                @endswitch

                <div class="mt-6 hidden gap-2 lg:grid">
                    <a href="{{ route('bookings') }}" class="flex items-center justify-between rounded-[0.9rem] border border-line bg-white px-4 py-3 text-[12px] font-bold text-ink transition-colors hover:border-forest">
                        <span class="flex items-center gap-2"><span class="text-clay"><x-line-icon name="ticket" :size="16" /></span> My trips</span>
                        <x-line-icon name="chevron" :size="15" />
                    </a>
                </div>
            </aside>

            <div class="min-w-0">
                {{-- Success Notification --}}
                @if (session()->has('success'))
                    <div class="mb-5 flex items-center gap-3 rounded-[1rem] border border-forest/20 bg-[#E3EFE7] px-4 py-3 text-[13px] font-semibold text-forest" role="alert">
                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-forest text-white"><x-line-icon name="check" :size="15" :stroke="2.6" /></span>
                        <p>{{ session('success') }}</p>
                    </div>
                @endif

                <div class="dv-panel dv-panel-shadow p-5 sm:p-8">
                    {{-- Account Tab --}}
                    @if ($tab === 'account')
                        <form wire:submit.prevent="saveAccount" class="space-y-5">
                            <div>
                                <label for="name" class="auth-label">Name</label>
                                <input type="text" id="name" wire:model="name" autocomplete="name" @class(['auth-input', 'auth-input-error' => $errors->has('name')])>
                                @error('name') <span class="auth-error">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="email" class="auth-label">Email</label>
                                <input type="email" id="email" wire:model="email" autocomplete="email" @class(['auth-input', 'auth-input-error' => $errors->has('email')])>
                                @error('email') <span class="auth-error">{{ $message }}</span> @enderror
                            </div>
                            <div class="border-t border-line pt-5">
                                <button type="submit" class="dv-btn dv-btn-primary w-full sm:w-auto sm:px-8" wire:loading.attr="disabled" wire:target="saveAccount">
                                    <span wire:loading.remove wire:target="saveAccount">Save Changes</span>
                                    <span wire:loading wire:target="saveAccount">Saving...</span>
                                </button>
                            </div>
                        </form>
                    @endif

                    {{-- Profile Tab --}}
                    @if ($tab === 'profile')
                        <form wire:submit.prevent="saveProfile" class="space-y-5">
                            <div>
                                <span class="auth-label">Avatar</span>
                                <div class="flex flex-col gap-4 rounded-[0.9rem] border border-dashed border-line bg-surface p-4 sm:flex-row sm:items-center">
                                    <span class="grid h-16 w-16 shrink-0 place-items-center overflow-hidden rounded-full bg-forest font-dm-serif text-[1.5rem] text-cream">
                                        @if ($avatar)
                                            <img src="{{ $avatar->temporaryUrl() }}" alt="Avatar preview" class="h-full w-full object-cover">
                                        @elseif ($avatarUrl)
                                            <img src="{{ $avatarUrl }}" alt="" class="h-full w-full object-cover">
                                        @else
                                            {{ $initials }}
                                        @endif
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <label for="avatar" class="sr-only">Avatar</label>
                                        <input type="file" id="avatar" wire:model="avatar" accept="image/jpeg,image/png,image/webp" class="block w-full text-[12px] text-muted file:mr-4 file:cursor-pointer file:rounded-full file:border-0 file:bg-forest file:px-4 file:py-2 file:text-[11px] file:font-extrabold file:text-white hover:file:bg-forest-light">
                                        <p class="mt-1.5 text-[11px] text-muted">
                                            <span wire:loading.remove wire:target="avatar">JPG, PNG or WebP, up to 2 MB.@if ($avatar) Preview shown.@endif</span>
                                            <span wire:loading wire:target="avatar">Uploading…</span>
                                        </p>
                                    </div>
                                </div>
                                @error('avatar') <span class="auth-error">{{ $message }}</span> @enderror
                            </div>

                            <div class="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <label for="phone" class="auth-label">Phone</label>
                                    <input type="text" id="phone" wire:model="profileData.phone" autocomplete="tel" @class(['auth-input', 'auth-input-error' => $errors->has('profileData.phone')])>
                                    @error('profileData.phone') <span class="auth-error">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="address" class="auth-label">Address</label>
                                    <input type="text" id="address" wire:model="profileData.address" autocomplete="street-address" @class(['auth-input', 'auth-input-error' => $errors->has('profileData.address')])>
                                    @error('profileData.address') <span class="auth-error">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div>
                                <label for="bio" class="auth-label">Bio</label>
                                <textarea id="bio" wire:model="profileData.bio" rows="4" @class(['auth-input !h-auto py-3 leading-relaxed', 'auth-input-error' => $errors->has('profileData.bio')])></textarea>
                                @error('profileData.bio') <span class="auth-error">{{ $message }}</span> @enderror
                            </div>

                            <div class="border-t border-line pt-5">
                                <button type="submit" class="dv-btn dv-btn-primary w-full sm:w-auto sm:px-8" wire:loading.attr="disabled" wire:target="saveProfile">
                                    <span wire:loading.remove wire:target="saveProfile">Update Profile</span>
                                    <span wire:loading wire:target="saveProfile">Updating...</span>
                                </button>
                            </div>
                        </form>
                    @endif

                    {{-- Password Tab --}}
                    @if ($tab === 'password')
                        <form wire:submit.prevent="savePassword" class="space-y-5">
                            <div>
                                <label for="current_password" class="auth-label">Current Password</label>
                                <input type="password" id="current_password" wire:model.lazy="current_password" autocomplete="current-password" @class(['auth-input', 'auth-input-error' => $errors->has('current_password')])>
                                @error('current_password') <span class="auth-error">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="password" class="auth-label">New Password</label>
                                <input type="password" id="password" wire:model.lazy="password" autocomplete="new-password" @class(['auth-input', 'auth-input-error' => $errors->has('password')])>
                                @error('password') <span class="auth-error">{{ $message }}</span> @enderror
                                <p class="mt-2 text-[11px] leading-relaxed text-muted">Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, and one number.</p>
                            </div>
                            <div>
                                <label for="password_confirmation" class="auth-label">Confirm New Password</label>
                                <input type="password" id="password_confirmation" wire:model.lazy="password_confirmation" autocomplete="new-password" class="auth-input">
                            </div>
                            <div class="border-t border-line pt-5">
                                <button type="submit" class="dv-btn dv-btn-primary w-full sm:w-auto sm:px-8" wire:loading.attr="disabled" wire:target="savePassword">
                                    <span wire:loading.remove wire:target="savePassword">Update Password</span>
                                    <span wire:loading wire:target="savePassword">Updating...</span>
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </section>
</div>
