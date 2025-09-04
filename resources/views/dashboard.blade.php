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

                {{-- Livewire Search Component --}}
            <livewire:search.holiday-search />
            </div>
        </section>

        {{-- Featured Destinations --}}
        <section class="px-6 md:px-12 lg:px-24">
            <h2 class="text-3xl md:text-4xl font-bold mb-8 text-neutral-800 dark:text-white">Featured Destinations 🌍</h2>
            <livewire:featured.featured-destinations />
        </section>

        {{-- Featured Packages --}}
        <section class="px-6 md:px-12 lg:px-24">
            <h2 class="text-3xl md:text-4xl font-bold mb-8 text-neutral-800 dark:text-white">Featured Holiday Packages 🎁</h2>
            <livewire:featured.featured-packages />
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
