<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('layouts.partials.head')
</head>
<body class="bg-paper font-sans text-ink antialiased">
    {{-- ─────────────────────────────  Header  ──────────────────────────── --}}
    <header
        x-data="{ menu: false }"
        class="sticky top-0 z-40 border-b border-ink/10 bg-paper/90 backdrop-blur-xl"
    >
        <div class="flex h-[68px] items-center justify-between px-6 md:h-[82px] md:px-[clamp(24px,4vw,72px)]">
            {{-- Logo --}}
            <a href="{{ route('welcome') }}" class="flex items-center gap-2.5" aria-label="HolidayHub home">
                <span class="text-coral"><x-line-icon name="compass" :size="21" /></span>
                <span class="text-xl font-semibold tracking-[-0.6px]">HolidayHub</span>
            </a>

            {{-- Desktop navigation --}}
            <nav class="hidden h-full items-center gap-9 md:flex" aria-label="Main navigation">
                @php
                    $nav = [
                        ['label' => 'Discover', 'route' => 'welcome'],
                        ['label' => 'Destinations', 'route' => 'destination.index'],
                        ['label' => 'Stays', 'route' => 'stays.index'],
                        ['label' => 'Experiences', 'route' => 'experiences.index'],
                    ];
                @endphp

                @foreach ($nav as $item)
                    <a
                        href="{{ route($item['route']) }}"
                        @class([
                            'relative flex h-full items-center text-sm font-medium transition-colors hover:text-ink',
                            'text-ink' => request()->routeIs($item['route']),
                            'text-ink-soft' => ! request()->routeIs($item['route']),
                        ])
                        @if (request()->routeIs($item['route'])) aria-current="page" @endif
                    >
                        {{ $item['label'] }}

                        @if (request()->routeIs($item['route']))
                            <span class="absolute inset-x-0 bottom-0 h-0.5 bg-coral"></span>
                        @endif
                    </a>
                @endforeach
            </nav>

            {{-- Actions --}}
            <div class="flex items-center gap-2 md:gap-3">
                <button
                    type="button"
                    x-on:click="$dispatch('open-search')"
                    class="grid h-10 w-10 place-items-center rounded-full transition-colors hover:bg-sage"
                    aria-label="Search"
                >
                    <x-line-icon name="search" />
                </button>

                <a
                    href="{{ route('register') }}"
                    class="hidden text-sm font-medium text-ink-soft transition-colors hover:text-ink lg:block"
                >List your place</a>

                @auth
                    <div x-data="{ open: false }" class="relative">
                        <button
                            type="button"
                            x-on:click="open = !open"
                            x-on:click.outside="open = false"
                            class="grid h-[38px] w-[38px] place-items-center rounded-full bg-ink text-xs font-semibold text-white transition-colors hover:bg-coral"
                            aria-label="Open account menu"
                        >{{ strtoupper(substr(Auth::user()->name, 0, 1)).substr(Auth::user()->name, 1, 1) }}</button>

                        <div
                            x-show="open"
                            x-cloak
                            x-transition.origin.top.right
                            class="absolute right-0 mt-3 w-56 overflow-hidden rounded-2xl border border-line bg-white py-2 shadow-[0_18px_40px_rgba(23,33,30,0.12)]"
                        >
                            <p class="px-4 pb-2 pt-1 text-[11px] uppercase tracking-[1.1px] text-ink-soft">
                                {{ Auth::user()->name }}
                            </p>

                            <a href="{{ route('dashboard') }}" class="block px-4 py-2 text-sm text-ink transition-colors hover:bg-paper">Dashboard</a>
                            <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-ink transition-colors hover:bg-paper">Profile</a>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-ink transition-colors hover:bg-paper">Log out</button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="hidden text-sm font-medium text-ink-soft transition-colors hover:text-ink md:block">Log in</a>

                    <a
                        href="{{ route('register') }}"
                        class="hidden rounded-full bg-ink px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-coral md:block"
                    >Sign up</a>
                @endauth

                <button
                    type="button"
                    x-on:click="menu = !menu"
                    class="grid h-10 w-10 place-items-center md:hidden"
                    aria-label="Toggle menu"
                    :aria-expanded="menu"
                >
                    <x-line-icon name="menu" x-show="!menu" />
                    <x-line-icon name="close" x-show="menu" x-cloak />
                </button>
            </div>
        </div>

        {{-- Mobile menu --}}
        <div
            x-show="menu"
            x-cloak
            x-transition
            class="border-t border-ink/10 bg-paper px-6 pb-6 pt-4 md:hidden"
        >
            <nav class="flex flex-col" aria-label="Mobile navigation">
                @foreach ($nav as $item)
                    <a
                        href="{{ route($item['route']) }}"
                        @class([
                            'border-b border-line py-3.5 text-base font-medium last:border-b-0',
                            'text-coral' => request()->routeIs($item['route']),
                            'text-ink' => ! request()->routeIs($item['route']),
                        ])
                    >{{ $item['label'] }}</a>
                @endforeach
            </nav>

            <div class="mt-5 flex flex-col gap-2">
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-full border border-line px-5 py-3 text-center text-sm font-semibold text-ink">Dashboard</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full rounded-full bg-ink px-5 py-3 text-sm font-semibold text-white">Log out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="rounded-full border border-line px-5 py-3 text-center text-sm font-semibold text-ink">Log in</a>
                    <a href="{{ route('register') }}" class="rounded-full bg-ink px-5 py-3 text-center text-sm font-semibold text-white">Sign up</a>
                @endauth
            </div>
        </div>
    </header>

    {{-- Site-wide search overlay --}}
    <livewire:search.search-overlay />

    {{-- Page content: rendered either by a Livewire page component ($slot) or by
         a classic view using @section('content'). --}}
    <main class="min-h-screen">
        @if (isset($slot) && ! $slot->isEmpty())
            {{ $slot }}
        @else
            @yield('content')
        @endif
    </main>

    {{-- ─────────────────────────────  Footer  ──────────────────────────── --}}
    <footer class="flex min-h-[120px] flex-wrap items-center justify-between gap-6 px-6 py-8 text-[11px] text-ink-soft md:px-[clamp(24px,4vw,72px)]">
        <a href="{{ route('welcome') }}" class="flex items-center gap-2.5 text-ink" aria-label="HolidayHub home">
            <span class="text-coral"><x-line-icon name="compass" :size="18" /></span>
            <span class="text-[17px] font-semibold tracking-[-0.4px]">HolidayHub</span>
        </a>

        <p class="max-[680px]:hidden">Made for curious travellers.</p>

        <nav class="flex items-center gap-6" aria-label="Footer navigation">
            <a href="{{ route('destination.index') }}" class="transition-colors hover:text-ink">Destinations</a>
            <a href="{{ route('stays.index') }}" class="transition-colors hover:text-ink">Stays</a>
            <a href="mailto:hello@holidayhub.test" class="transition-colors hover:text-ink">Support</a>
        </nav>
    </footer>

    @include('layouts.partials.scripts')
</body>
</html>
