<div
    x-data="{ open: false }"
    class="grid grid-cols-1 md:grid-cols-4 gap-6"
>
    <!-- Mobile Filter Button -->
    <div class="md:hidden flex justify-end mb-4">
        <button
            @click="open = true"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-700 transition"
        >
            <svg xmlns="http://www.w3.org/2000/svg"
                 class="h-5 w-5" fill="none"
                 viewBox="0 0 24 24"
                 stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 4h18M3 12h18M3 20h18"/>
            </svg>
            Filters
        </button>
    </div>

    <!-- Filters Sidebar (desktop only) -->
    <aside class="hidden md:block md:col-span-1 space-y-6 bg-white p-4 rounded-2xl shadow-sm">
        @include('packages.partials.filters')
    </aside>

    <!-- Slide-over Drawer (mobile only) -->
    <div
        class="fixed inset-0 z-50 md:hidden"
        x-show="open"
        x-transition.opacity
        x-cloak
    >
        <!-- Background overlay -->
        <div
            class="absolute inset-0 bg-black bg-opacity-40"
            @click="open = false"
        ></div>

        <!-- Drawer Panel -->
        <div
            class="absolute right-0 top-0 h-full w-80 bg-white shadow-xl p-6 overflow-y-auto"
            x-transition:enter="transform transition ease-in-out duration-300"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in-out duration-300"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
        >
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold">Filters</h2>
                <button @click="open = false" class="text-gray-500 hover:text-gray-700">
                    ✕
                </button>
            </div>

            @include('packages.partials.filters')

            <div class="mt-6">
                <button
                    @click="open = false"
                    class="w-full bg-blue-600 text-white py-2 rounded-xl hover:bg-blue-700 transition"
                >
                    Apply Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Results Grid -->
    <section class="md:col-span-3 space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($packages as $package)
                <div class="bg-white rounded-2xl shadow hover:shadow-lg transition">
                    <img src="{{ $package->cover_image ?? 'https://via.placeholder.com/400x250' }}"
                         alt="{{ $package->title }}"
                         class="w-full h-40 object-cover rounded-t-2xl"/>

                    <div class="p-4 space-y-2">
                        <h3 class="text-lg font-semibold">{{ $package->title }}</h3>
                        <p class="text-sm text-gray-500">{{ $package->destination }}, {{ $package->country }}</p>
                        <p class="text-blue-600 font-bold text-lg">
                            ${{ number_format($package->final_price, 2) }}
                        </p>

                        <a href="{{ route('packages.show', $package) }}"
                           class="inline-block mt-2 w-full text-center bg-blue-600 text-white py-2 rounded-xl hover:bg-blue-700 transition">
                            View Details
                        </a>
                    </div>
                </div>
            @empty
                <p class="col-span-full text-center text-gray-500">No packages found</p>
            @endforelse
        </div>

        <!-- Pagination -->
        <div>
            {{ $packages->links() }}
        </div>
    </section>
</div>
