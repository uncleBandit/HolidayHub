<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

     @vite(['resources/css/app.css', 'resources/js/app.js'])


    <title>@yield('title', config('app.name'))</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('favicon-96x96.png') }}" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}" />
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" />
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}" />
    <link rel="manifest" href="{{ asset('site.webmanifest') }}" />

    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .text-tropical-blue {
            color: #1a73e8; /* A vibrant, deep blue for links and accents */
        }
        .bg-sea-foam {
            background-color: #e0f2fe; /* A light, airy background color */
        }
        .bg-sunset-orange {
            background-color: #ff7043; /* A warm accent color for buttons */
        }
        .bg-deep-ocean {
            background-color: #003366; /* A dark, rich color for the footer */
        }
    </style>
    @livewireStyles
</head>
<body class="antialiased bg-sea-foam text-gray-900">
    <!-- Navbar -->
    <header class="bg-white/80 backdrop-blur-sm shadow-xl rounded-b-[4rem] border-b border-white/20">
    <div class="container mx-auto px-6 py-4 flex justify-between items-center">
        <!-- Logo with Icon -->
        <a href="{{ url('/') }}" class="flex items-center gap-2">
            <img src="{{ asset('images/HolidayHubLogo.png') }}" alt="HolidayHub Logo" class="h-12 w-auto">
            <span class="text-2xl sm:text-3xl font-extrabold text-tropical-blue hover:text-deep-ocean transition-colors duration-200">
                HolidayHub
            </span>
        </a>

        <nav class="space-x-4 flex items-center">
            <a href="{{ route('dashboard') }}" class="hover:text-tropical-blue transition-colors duration-200 font-semibold">Dashboard</a>
            <a href="{{ route('destination.index') }}" class="hover:text-tropical-blue transition-colors duration-200 font-semibold">Destinations</a>
            <a href="{{ route('media.reels') }}" class="hover:text-tropical-blue transition-colors duration-200 font-semibold">Reels</a>
            <a href="{{ route('hotel.index') }}" class="hover:text-tropical-blue transition-colors duration-200 font-semibold">Hotels</a>
            <a href="{{ route('bedandbreakfast-index') }}" class="hover:text-tropical-blue transition-colors duration-200 font-semibold">B&B</a>

            <!-- Auth Links -->
            @auth
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" type="button" class="inline-flex items-center px-4 py-2 bg-tropical-blue text-white rounded-full font-semibold shadow-lg hover:bg-deep-ocean transition-colors duration-200 focus:outline-none">
                        {{ Auth::user()->name }}
                        <svg class="ml-2 w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-2xl py-2 z-50 animate-fade-in-up">
                        <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-2 text-gray-800 hover:bg-gray-100 rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-tropical-blue" viewBox="0 0 24 24" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 6a3 3 0 0 1 3-3h12a3 3 0 0 1 3 3v12a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V6Zm12 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" clip-rule="evenodd" />
                            </svg>
                            Dashboard
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left flex items-center px-4 py-2 text-gray-800 hover:bg-gray-100 rounded-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-sunset-orange" viewBox="0 0 24 24" fill="currentColor">
                                    <path fill-rule="evenodd" d="M12 2.25a.75.75 0 0 1 .75.75v9a.75.75 0 0 1-1.5 0V3a.75.75 0 0 1 .75-.75ZM6.906 5.46a.75.75 0 1 1-1.21-.882c.433-.59.952-1.124 1.522-1.597.433-.357.534-.99.234-1.464a.75.75 0 0 1 1.149-.968c.552.793 1.157 1.54 1.815 2.235a.75.75 0 0 1-1.026 1.118l-.208-.192Z" clip-rule="evenodd" />
                                </svg>
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" class="px-6 py-3 bg-tropical-blue text-blue rounded-full font-bold shadow-lg hover:bg-deep-ocean transition-all duration-300 transform hover:scale-105">Login</a>
                <a href="{{ route('register') }}" class="px-6 py-3 bg-sunset-orange text-white rounded-full font-bold shadow-lg hover:bg-orange-600 transition-all duration-300 transform hover:scale-105">Register</a>
            @endauth
        </nav>
    </div>
</header>



    <!-- Page Content -->
    <main class="min-h-screen">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-deep-ocean text-tropical-blue py-8 mt-12">
        <div class="container mx-auto px-6 text-center">
            <p>&copy; {{ date('Y') }} HolidayHub. All rights reserved.</p>
            <p class="text-sm text-gray-400 mt-2">Designed with a love for travel and tropical escapes. </p>
        </div>
    </footer>

    @livewireScripts
    {{-- Put this in your layout (e.g. layouts/app.blade.php) or inside the booking page view --}}
<script>
    document.addEventListener("DOMContentLoaded", () => {
        Livewire.on('bookingConfirmed', ({ bookingId, message }) => {
            console.log("✅ Booking confirmed event caught in JS!");
            console.log("Booking ID:", bookingId);
            console.log("Message:", message);

            // Show a quick alert (replace with your toast/notification later)
            alert("Booking #" + bookingId + " → " + message);
        });
    });
</script>

</body>
</html>
