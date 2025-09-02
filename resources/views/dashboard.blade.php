<x-layouts.app :title="__('Welcome to Holiday Booking')">
    <div class="flex flex-col gap-16">

        {{-- Hero Section --}}
        <section class="relative h-screen flex items-center justify-center text-center bg-cover bg-center" style="background-image: url('https://placehold.co/1600x900/228B22/FFFFFF?text=Paradise');">
            <div class="absolute inset-0 bg-black/50"></div>
            <div class="relative z-10 px-6 md:px-12 lg:px-24">
                <h1 class="text-5xl md:text-7xl font-extrabold text-white drop-shadow-xl mb-4 animate-fade-in-down">
                    Discover Your Next Adventure
                </h1>
                <p class="text-lg md:text-2xl text-white/90 mb-8 animate-fade-in">
                    Search and book hotels, resorts, and unique getaways worldwide.
                </p>

                {{-- Search Form --}}
                <form action="{{ route('hotels.index') }}" method="GET" class="w-full max-w-5xl mx-auto grid md:grid-cols-4 lg:grid-cols-5 gap-4 bg-white/90 dark:bg-zinc-900/90 backdrop-blur-md rounded-3xl p-4 shadow-xl text-neutral-800 animate-slide-up-search">
                    <input type="text" name="location" placeholder="Destination or Hotel" class="md:col-span-2 rounded-full px-5 py-3 focus:ring-2 focus:ring-indigo-500 transition">
                    <input type="date" name="check_in" class="rounded-full px-5 py-3 focus:ring-2 focus:ring-indigo-500 transition">
                    <input type="date" name="check_out" class="rounded-full px-5 py-3 focus:ring-2 focus:ring-indigo-500 transition">
                    <button type="submit" class="flex items-center justify-center bg-gradient-to-r from-indigo-600 to-indigo-700 text-white font-semibold py-3 rounded-full hover:scale-105 transition-transform shadow-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.2-5.2M5.2 5.2a7.5 7.5 0 1010.6 10.6 7.5 7.5 0 00-10.6-10.6z"/>
                        </svg>
                        Search
                    </button>
                </form>
            </div>
        </section>

        {{-- Featured Destinations --}}
        <section class="px-6 md:px-12 lg:px-24">
            <h2 class="text-3xl md:text-4xl font-bold mb-8 text-neutral-800 dark:text-white">Featured Destinations 🌍</h2>
            <livewire:featured.featured-destinations />
        </section>

        {{-- Top Hotels --}}
        <section class="px-6 md:px-12 lg:px-24">
            <h2 class="text-3xl md:text-4xl font-bold mb-8 text-neutral-800 dark:text-white">Top Hotels 🏨</h2>
            <livewire:featured.featured-hotels />
        </section>

        {{-- Activities --}}
        <section class="px-6 md:px-12 lg:px-24">
            <h2 class="text-3xl md:text-4xl font-bold mb-8 text-neutral-800 dark:text-white">Exciting Activities 🎯</h2>
            <livewire:featured.featured-activities />
        </section>

        {{-- Offers --}}
    <section class="px-6 md:px-12 lg:px-24 mt-12">
        <h2 class="text-3xl md:text-4xl font-bold mb-6 text-neutral-800 dark:text-white">Last-Minute Deals 🔥</h2>
        <livewire:featured.featured-offers />
    </section>

        {{-- Why Choose Us --}}
        <section class="px-6 md:px-12 lg:px-24 mt-12">
            <h2 class="text-3xl md:text-4xl font-bold mb-8 text-neutral-800 dark:text-white">Why Choose HolidayHub?</h2>
            <div class="grid md:grid-cols-3 gap-6">
                <div class="p-8 bg-white dark:bg-neutral-800 rounded-2xl shadow-xl text-center hover:shadow-2xl hover:-translate-y-1 transition-all">
                    <h3 class="text-xl font-bold mb-2">Curated Selection</h3>
                    <p class="text-neutral-600 dark:text-neutral-400">Hand-picked hotels and unique stays for every traveler.</p>
                </div>
                <div class="p-8 bg-white dark:bg-neutral-800 rounded-2xl shadow-xl text-center hover:shadow-2xl hover:-translate-y-1 transition-all">
                    <h3 class="text-xl font-bold mb-2">Secure & Easy Booking</h3>
                    <p class="text-neutral-600 dark:text-neutral-400">A seamless and secure payment process from start to finish.</p>
                </div>
                <div class="p-8 bg-white dark:bg-neutral-800 rounded-2xl shadow-xl text-center hover:shadow-2xl hover:-translate-y-1 transition-all">
                    <h3 class="text-xl font-bold mb-2">24/7 Support</h3>
                    <p class="text-neutral-600 dark:text-neutral-400">Our dedicated team is always here to help you.</p>
                </div>
            </div>
        </section>

        {{-- Call to Action --}}
        <section class="px-6 md:px-12 lg:px-24 mt-12 text-center">
            <h2 class="text-3xl md:text-4xl font-bold mb-4">Ready for Your Next Adventure?</h2>
            <p class="text-neutral-600 dark:text-neutral-400 mb-6">Check out our latest offers and start planning today!</p>
            <a href="{{ route('offers.index') }}" class="inline-flex items-center space-x-2 bg-indigo-600 text-white font-semibold py-4 px-8 rounded-full shadow-lg hover:bg-indigo-700 transition-transform transform hover:scale-105">
                <span>Explore Offers</span>
            </a>
        </section>

    </div>
</x-layouts.app>
