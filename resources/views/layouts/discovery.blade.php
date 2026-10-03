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
                    ['label' => 'Discover', 'route' => 'welcome'],
                    ['label' => 'Destinations', 'route' => 'destination.index'],
                    ['label' => 'Stays', 'route' => 'stays.index'],
                    ['label' => 'Experiences', 'route' => 'experiences.index'],
                ];
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
                <a href="{{ route('dashboard') }}" class="dv-avatar" aria-label="Dashboard">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)).substr(Auth::user()->name, 1, 1) }}
                </a>
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
            ['label' => 'Profile', 'icon' => 'user', 'route' => auth()->check() ? 'dashboard' : 'login', 'active' => 'dashboard'],
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