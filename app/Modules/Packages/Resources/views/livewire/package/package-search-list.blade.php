<div
    x-data="{ open: false }"
    class="relative grid grid-cols-1 md:grid-cols-4 gap-12"
>
    <div class="fixed bottom-6 right-6 z-40 md:hidden">
        <button
            @click="open = true"
            class="flex items-center justify-center w-14 h-14 rounded-full bg-blue-600 text-white shadow-xl hover:bg-blue-700 transition transform hover:scale-110"
        >
            <svg xmlns="http://www.w3.org/2000/svg"
                class="h-6 w-6"
                viewBox="0 0 24 24"
                fill="currentColor"
            >
                <path d="M10 18a2 2 0 100-4 2 2 0 000 4zM4 12a2 2 0 100-4 2 2 0 000 4zM16 6a2 2 0 100-4 2 2 0 000 4z"/>
                <path fill-rule="evenodd" d="M14 18a2 2 0 104 0 2 2 0 00-4 0zM18 12a2 2 0 10-4 0 2 2 0 004 0zM8 6a2 2 0 10-4 0 2 2 0 004 0z" clip-rule="evenodd"/>
            </svg>
        </button>
    </div>

    <aside class="hidden md:block md:col-span-1 space-y-8 p-6 rounded-3xl bg-white shadow-lg border border-gray-100">
        <h2 class="text-2xl font-bold text-gray-800">Filter Your Adventure</h2>
        @include('package.partials.filters')
    </aside>

    <div
        class="fixed inset-0 z-50 md:hidden"
        x-show="open"
        x-transition.opacity.duration.300ms
        x-cloak
    >
        <div
            class="absolute inset-0 bg-black bg-opacity-60"
            @click="open = false"
        ></div>

        <div
            class="absolute right-0 top-0 h-full w-80 bg-gray-50 shadow-2xl p-6 overflow-y-auto"
            x-transition:enter="transform transition ease-in-out duration-500"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in-out duration-500"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
        >
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold text-gray-800">Filters</h2>
                <button @click="open = false" class="text-gray-500 hover:text-gray-900 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            @include('package.partials.filters')

            <div class="mt-8">
                <button
                    @click="open = false"
                    class="w-full bg-blue-600 text-white font-semibold py-3 rounded-2xl hover:bg-blue-700 transition shadow"
                >
                    Apply Filters
                </button>
            </div>
        </div>
    </div>

    <section class="md:col-span-3 space-y-8">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
            @forelse($packages as $package)
                <a href="{{ route('packages-show', $package) }}" class="group block relative rounded-3xl overflow-hidden shadow-xl hover:shadow-2xl transition-transform duration-300 transform hover:-translate-y-2">
                    <div class="relative w-full h-64 overflow-hidden">
                        <img
                            src="{{ $package->cover_image ?? 'https://via.placeholder.com/600x400' }}"
                            alt="{{ $package->title }}"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-in-out"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                    </div>

                    <div class="absolute bottom-0 left-0 right-0 p-6 text-white space-y-2">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xl font-bold tracking-tight leading-tight line-clamp-2">
                                {{ $package->title }}
                            </h3>
                            <div class="bg-blue-600/80 backdrop-blur-sm rounded-full py-1 px-4 text-sm font-bold shadow-lg">
                                ${{ number_format($package->final_price, 0) }}
                            </div>
                        </div>
                        <p class="text-sm font-medium text-gray-200 flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                            </svg>
                            {{ $package->destination->name ?? 'Unknown' }}, {{ $package->destination->country ?? 'Unknown' }}
                        </p>
                    </div>
                </a>
            @empty
                <p class="col-span-full text-center text-gray-500 font-medium py-12">No packages found that match your criteria. Please try different filters.</p>
            @endforelse
        </div>

        <div class="mt-12">
            {{ $packages->links() }}
        </div>
    </section>
</div>
