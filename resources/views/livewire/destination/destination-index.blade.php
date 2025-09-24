<div>
    <div class="space-y-12 py-8">
    <header class="bg-white rounded-3xl p-6 shadow-lg">
        <h1 class="text-4xl font-bold text-gray-800 mb-2">Explore Destinations</h1>
        <p class="text-gray-600 text-lg mb-6">Find your perfect getaway from our curated selection of amazing places.</p>

        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="relative flex-grow w-full md:w-auto">
                <input wire:model.live.debounce.500ms="search" type="text"
                    placeholder="Search destinations, cities, or countries..."
                    class="w-full pl-10 pr-4 py-3 text-gray-700 bg-white border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center">
                    <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
            </div>

            <div class="flex items-center space-x-4">
                <label for="sort" class="text-gray-700 font-medium whitespace-nowrap hidden sm:inline">Sort by:</label>
                <div class="relative">
                    <select id="sort" wire:model="sortField"
                        class="block w-full px-4 py-3 pr-8 rounded-full border border-gray-300 bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 appearance-none transition duration-200">
                        <option value="name">Name</option>
                        <option value="popularity_score">Top Rated</option>
                        {{--<option value="trending">Trending</option>

                        <option value="bookings_count">Most Booked</option>--}}
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                            <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div wire:loading.class="opacity-50" class="transition-opacity duration-300">
        @if ($destinations->isEmpty())
            <div class="text-center p-12 bg-gray-100 rounded-xl">
                <p class="text-2xl text-gray-500 font-medium">No destinations found matching your criteria.</p>
                <p class="text-gray-400 mt-2">Try adjusting your search or filters.</p>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach ($destinations as $destination)
                    <a href="{{ route('destinations.show', $destination) }}" class="relative group block w-full h-80 rounded-2xl overflow-hidden shadow-lg transition-transform duration-300 transform hover:scale-105">
                        <img src="{{ $destination->image_url ?? asset('images/paradise2.jpg') }}"
                             alt="{{ $destination->name }}"
                             class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                        <div class="absolute inset-0 bg-gradient-to-t from-black via-transparent to-transparent opacity-80 group-hover:opacity-90 transition-opacity"></div>
                        <div class="relative p-6 flex flex-col justify-end h-full text-white">
                            <h3 class="text-2xl font-bold mb-1">{{ $destination->name }}</h3>
                            <p class="text-sm font-semibold text-gray-200 flex items-center">
                                <svg class="w-4 h-4 mr-1 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.961a1 1 0 00.95.69h4.16a1 1 0 01.595 1.838l-3.376 2.454a1 1 0 00-.364 1.118l1.286 3.961c.3.921-.755 1.688-1.54 1.118L10 15.659l-3.376 2.454c-.785.57-1.84-.197-1.54-1.118l1.286-3.961a1 1 0 00-.364-1.118L2.094 9.416a1 1 0 01.595-1.838h4.16a1 1 0 00.95-.69l1.286-3.961z" />
                                </svg>
                                {{ number_format($destination->popularity_score ?? 0, 1) }}

                            </p>
                            <p class="text-gray-300 mt-2">{{ $destination->city }}, {{ $destination->country }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    @if ($destinations->hasPages())
        <div class="mt-8">
            {{ $destinations->links() }}
        </div>
    @endif
</div>
</div>
