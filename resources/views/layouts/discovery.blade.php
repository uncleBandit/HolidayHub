<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('layouts.partials.head')
</head>
@php
    $reels = $reels ?? false;
    $isReels = $reels || request()->query('view') === 'reels';
@endphp
<body class="dv-shell antialiased @if($isReels) dv-shell-dark @endif">
    {{-- ─────────────────────  Discovery topbar (solid variant)  ───────────────────── --}}
    <header class="dv-topbar h-[76px] md:h-[88px] @if($isReels) dv-topbar-dark @endif">
        <a href="{{ route('welcome') }}" class="dv-brand" aria-label="HolidayHub home">
            <span class="dv-brand-mark" aria-hidden="true"><span></span><span></span></span>
            <span class="dv-brand-name">HolidayHub</span>
        </a>

        <nav class="dv-links" aria-label="Main navigation">
            @php
                $nav = [
                    ['label' => 'Destinations', 'route' => 'destination.index'],
                    ['label' => 'Stays', 'route' => 'stays.index'],
                    ['label' => 'Experiences', 'route' => 'experiences.index'],
                ];

                if (auth()->check()) {
                    array_unshift($nav, ['label' => 'For you', 'route' => 'dashboard']);
                }
            @endphp

            @foreach ($nav as $item)
                @php $isActive = request()->routeIs($item['route']); @endphp
                <a
                    href="{{ route($item['route']) }}"
                    @class(['dv-link', 'dv-link-active' => $isActive])
                    @if ($isActive) aria-current="page" @endif
                >{{ $item['label'] }}</a>
            @endforeach
        </nav>

        <div class="flex items-center gap-2.5">
            <span class="dv-location">
                <x-line-icon name="map" :size="16" />
                <span>Worldwide</span>
            </span>

            <button
                type="button"
                x-on:click="$dispatch('open-search')"
                class="dv-round"
                aria-label="Search"
            >
                <x-line-icon name="search" :size="19" />
            </button>

            @auth
                <div x-data="{ open: false }" class="relative" x-on:keydown.escape.window="open = false">
                    <button
                        type="button"
                        x-on:click="open = ! open"
                        x-on:click.outside="open = false"
                        class="dv-avatar transition-transform duration-200 hover:scale-105"
                        aria-label="Open account menu"
                        :aria-expanded="open"
                    >{{ strtoupper(substr(Auth::user()->name, 0, 1)).substr(Auth::user()->name, 1, 1) }}</button>

                    <div
                        x-show="open"
                        x-cloak
                        x-transition.origin.top.right
                        class="absolute right-0 mt-3 w-60 overflow-hidden rounded-[1rem] border border-line bg-white py-2 text-ink shadow-[0_20px_60px_rgba(20,36,28,0.16)]"
                    >
                        <p class="truncate px-4 pb-2 pt-1 text-[11px] font-extrabold uppercase tracking-[0.1em] text-muted">{{ Auth::user()->name }}</p>
                        @php
                            $accountLinks = [['For you', 'star', route('dashboard')], ['My bookings', 'ticket', route('bookings')], ['Profile & security', 'user', route('profile.edit')]];

                            if (Auth::user()->hasRole('provider')) {
                                array_unshift($accountLinks, ['Host studio', 'grid', route('provider.dashboard')]);
                            }
                            if (Auth::user()->hasRole('agent')) {
                                array_unshift($accountLinks, ['Agent studio', 'compass', route('agent.dashboard')]);
                            }
                            if (Auth::user()->can('admin.panel.access')) {
                                array_unshift($accountLinks, ['Admin panel', 'sliders', url('/admin')]);
                            }
                        @endphp
                        @foreach ($accountLinks as [$label, $icon, $url])
                            <a href="{{ $url }}" class="flex items-center gap-3 px-4 py-2.5 text-[13px] font-semibold transition-colors hover:bg-cream">
                                <span class="text-clay"><x-line-icon :name="$icon" :size="17" /></span> {{ $label }}
                            </a>
                        @endforeach
                        <form method="POST" action="{{ route('logout') }}" class="mt-1 border-t border-line pt-1">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-[13px] font-semibold transition-colors hover:bg-cream">
                                <span class="text-muted"><x-line-icon name="arrow-up-right" :size="17" /></span> Log out
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" class="dv-avatar" aria-label="Log in">HH</a>
            @endauth
        </div>
    </header>

    {{-- Site-wide search overlay --}}
    <livewire:search.search-overlay />

    {{-- Page content: rendered either by a Livewire page component ($slot) or by
         a classic view using @section('content'). --}}
    <main class="dv-shell-pad-bottom @if($isReels) dv-main-flush @endif">
        @if (isset($slot) && ! $slot->isEmpty())
            {{ $slot }}
        @else
            @yield('content')
        @endif
    </main>

    {{-- ─────────────────────────────  Footer  ────────────────────────────── --}}
    @unless ($isReels)
        <footer class="dv-footer">
            <a href="{{ route('welcome') }}" class="dv-brand" aria-label="HolidayHub home">
                <span class="dv-brand-mark" aria-hidden="true"><span></span><span></span></span>
                <span class="dv-brand-name text-white">HolidayHub</span>
            </a>

            <p class="dv-footer-tagline">Go somewhere that stays with you.</p>

            <nav class="flex items-center gap-6 text-[12px] font-semibold text-white/70" aria-label="Footer navigation">
                <a href="{{ route('destination.index') }}" class="transition-colors hover:text-white">Destinations</a>
                <a href="{{ route('stays.index') }}" class="transition-colors hover:text-white">Stays</a>
                <a href="mailto:hello@holidayhub.test" class="transition-colors hover:text-white">Support</a>
            </nav>
        </footer>
    @endunless

    {{-- ──────────────────────────  Mobile bottom nav  ────────────────────── --}}
    @php
        $bottomNav = [
            ['label' => 'Home', 'icon' => 'home', 'route' => 'welcome', 'active' => 'welcome'],
            ['label' => 'Explore', 'icon' => 'compass', 'route' => 'destination.index', 'active' => 'destination.index'],
            ['label' => 'Stays', 'icon' => 'bed', 'route' => 'stays.index', 'active' => 'stays.index'],
            auth()->check()
                ? ['label' => 'For you', 'icon' => 'star', 'route' => 'dashboard', 'active' => 'dashboard']
                : ['label' => 'Sign in', 'icon' => 'user', 'route' => 'login', 'active' => 'login'],
        ];
    @endphp

    <nav class="dv-bottom-nav dv-bottom-nav-4 @if($isReels) dv-bottom-nav-dark @endif" aria-label="Primary">
        @foreach ($bottomNav as $item)
            <a
                href="{{ route($item['route']) }}"
                @class(['dv-nav-item', 'dv-nav-item-active' => request()->routeIs($item['active'])])
                @if (request()->routeIs($item['active'])) aria-current="page" @endif
            >
                <x-line-icon :name="$item['icon']" :size="21" :filled="request()->routeIs($item['active'])" />
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    @include('layouts.partials.scripts')
</body>
</html>