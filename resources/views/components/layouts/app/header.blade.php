<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head')
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        /* --- Animations for the "living" header --- */
        @keyframes pulse-light {
            0%, 100% { opacity: 0.1; }
            50% { opacity: 0.3; }
        }

        @keyframes move-bg {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .animated-gradient-bg {
            background-size: 200% 200%;
            animation: move-bg 20s ease infinite;
        }

        .pulse-light-overlay {
            animation: pulse-light 4s ease-in-out infinite;
        }
    </style>
</head>
<body class="min-h-screen bg-white dark:bg-zinc-900">

    <header class="relative z-50 overflow-hidden bg-gradient-to-r from-sky-500 via-indigo-600 to-purple-600 shadow-2xl animated-gradient-bg">
        <div class="absolute inset-0 size-full opacity-10 bg-repeat [background-image:radial-gradient(ellipse_at_center,rgba(255,255,255,0.4)_0%,transparent_80%)] pulse-light-overlay"></div>
        <div class="absolute inset-0 bg-black/15 backdrop-blur-md"></div>

        <div class="relative flex h-24 items-center px-4 md:px-6 lg:px-10">
            <flux:sidebar.toggle class="lg:hidden text-white" icon="bars-3" inset="left" />

            <a href="{{ route('dashboard') }}" class="flex items-center space-x-2 drop-shadow-lg">
                <span class="text-white text-4xl font-extrabold tracking-tight">HolidayHub</span>
            </a>

            <nav class="hidden lg:flex ml-12 space-x-8">
                <flux:navbar.item
                    icon="home"
                    :href="route('dashboard')"
                    :current="request()->routeIs('dashboard')"
                    wire:navigate
                    class="text-white font-medium transition duration-300 hover:text-white/80 transform hover:scale-105"
                >
                    {{ __('Discover') }}
                </flux:navbar.item>
                {{-- Conditional links for authenticated users --}}
                @auth
                    <flux:navbar.item
                        icon="calendar"
                        :href="route('bookings')"
                        :current="request()->routeIs('bookings')"
                        wire:navigate
                        class="text-white font-medium transition duration-300 hover:text-white/80 transform hover:scale-105"
                    >
                        {{ __('Bookings') }}
                    </flux:navbar.item>
                @endauth
            </nav>

            <flux:spacer />

            <div class="hidden lg:flex w-[480px] h-12 bg-white/90 backdrop-blur-md rounded-full shadow-lg overflow-hidden border border-white/30 transition-all duration-300 focus-within:w-[600px] focus-within:shadow-2xl focus-within:ring-2 focus-within:ring-white">
                <input
                    type="text"
                    placeholder="{{ __('Where will your story begin? (City, Hotel, Landmark)') }}"
                    class="flex-1 px-5 text-sm bg-transparent focus:outline-none text-zinc-700 placeholder-zinc-400"
                />
                <button class="flex items-center px-5 text-white bg-gradient-to-r from-indigo-600 to-sky-600 hover:from-indigo-700 hover:to-sky-700 transition-all duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            <div class="flex items-center gap-3 ml-4">
                <flux:tooltip :content="__('Language & Currency')" position="bottom">
                    <flux:navbar.item
                        icon="globe-alt"
                        class="h-12 w-12 text-white hover:text-white/80 transition transform hover:scale-110"
                        href="#"
                    />
                </flux:tooltip>

                @auth
                    @php
    // Map roles to dashboard links
    $dashboards = [
        'provider' => ['route' => 'provider.dashboard', 'icon' => 'building-office', 'label' => 'Provider Dashboard'],
        'agent' => ['route' => 'agent.dashboard', 'icon' => 'users', 'label' => 'Agent Dashboard'],
        // Add more role dashboards here if needed
    ];
@endphp

<flux:dropdown position="top" align="end">
    <flux:profile class="cursor-pointer text-white border-2 border-white/50 hover:border-white/90 transition-colors"
                  :initials="auth()->user()->initials()" />
    <flux:menu class="w-[200px]">
        <div class="px-3 py-2">
            <div class="font-semibold">{{ auth()->user()->name }}</div>
            <div class="text-xs text-gray-500">{{ auth()->user()->email }}</div>
        </div>
        <flux:menu.separator />
        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
            {{ __('Account Settings') }}
        </flux:menu.item>

        @php
            $userRoles = auth()->user()->getRoleNames(); // Collection of user's roles
        @endphp

        {{-- Loop through dashboards and show if user has role or is admin --}}
        @foreach($dashboards as $role => $dashboard)
            @if($userRoles->contains($role) || $userRoles->contains('admin'))
                <flux:menu.item :href="route($dashboard['route'])" icon="{{ $dashboard['icon'] }}" wire:navigate>
                    {{ __($dashboard['label']) }}
                </flux:menu.item>
            @endif
        @endforeach

        <flux:menu.separator />
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle">
                {{ __('Log Out') }}
            </flux:menu.item>
        </form>
    </flux:menu>
</flux:dropdown>

                @else
                    <a href="{{ route('login') }}" class="text-white font-medium hover:text-white/80 transition">Log In</a>
                @endauth
            </div>
        </div>
    </header>

    <flux:sidebar stashable sticky class="lg:hidden border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />
        <a href="{{ route('dashboard') }}" class="ms-1 flex items-center space-x-2" wire:navigate>
            <span class="text-2xl font-bold">Voyage</span>
        </a>
        <flux:navlist variant="outline" class="mt-4">
            <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                {{ __('Discover') }}
            </flux:navlist.item>
            @auth
                <flux:navlist.item icon="paper-airplane" href="#" wire:navigate>
                    {{ __('My Trips') }}
                </flux:navlist.item>
                <flux:navlist.item icon="calendar" href="#" wire:navigate>
                    {{ __('Bookings') }}
                </flux:navlist.item>
                <flux:navlist.item icon="cog" :href="route('profile.edit')" wire:navigate>
                    {{ __('Account Settings') }}
                </flux:navlist.item>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <flux:navlist.item as="button" type="submit" icon="arrow-right-start-on-rectangle">
                        {{ __('Log Out') }}
                    </flux:navlist.item>
                </form>
            @else
                <flux:navlist.item icon="arrow-right-start-on-rectangle" :href="route('login')">
                    {{ __('Log In') }}
                </flux:navlist.item>
            @endauth
            <flux:navlist.item icon="magnifying-glass" href="#">
                {{ __('Search') }}
            </flux:navlist.item>
            <flux:navlist.item icon="globe-alt" href="#">
                {{ __('Language & Currency') }}
            </flux:navlist.item>
        </flux:navlist>
    </flux:sidebar>

    <main>
        {{ $slot }}
    </main>

    @fluxScripts
</body>
</html>
