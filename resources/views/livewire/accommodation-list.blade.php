<div>
<div class="bg-gray-50 min-h-screen">
    {{-- Main Container --}}
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12">

        {{-- Header & Filters --}}
        <div class="mb-10 text-center">
            <h1 class="text-5xl font-extrabold text-gray-900 leading-tight tracking-tighter mb-2">
                Discover Your Perfect Stay 🌍
            </h1>
            <p class="text-xl text-gray-600">
                Explore a curated selection of hotels, villas, and more.
            </p>
        </div>

        {{-- Search & Sorting Controls --}}
        <div class="bg-white rounded-3xl shadow-lg p-6 mb-12 flex flex-col md:flex-row items-center gap-6">
            <div class="relative w-full md:w-1/2">
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search for hotels, destinations..."
                       class="w-full pl-12 pr-4 py-3 rounded-2xl border-2 border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 transition-colors duration-300">
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </div>

            <div class="w-full md:w-1/4">
                {{-- This would be your Livewire select for destinations --}}
                <select wire:model="destinationId" class="w-full py-3 px-4 rounded-2xl border-2 border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 transition-colors duration-300 bg-white">
                    <option value="">All Destinations</option>
                    {{-- @foreach($destinations as $destination)
                        <option value="{{ $destination->id }}">{{ $destination->name }}</option>
                    @endforeach --}}
                </select>
            </div>

            <div class="w-full md:w-1/4">
                <select wire:model="sortBy" class="w-full py-3 px-4 rounded-2xl border-2 border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 transition-colors duration-300 bg-white">
                    <option value="latest">Latest</option>
                    <option value="price_low">Price: Low to High</option>
                    <option value="price_high">Price: High to Low</option>
                    <option value="rating">Top Rated</option>
                </select>
            </div>
        </div>

        {{-- Loading State --}}
        <div wire:loading.flex wire:target="search, destinationId, sortBy" class="flex items-center justify-center p-8">
            <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-indigo-500"></div>
            <p class="ml-4 text-gray-500 text-lg">Searching for amazing places...</p>
        </div>

        {{-- Accommodation Grid & Dynamic Grouping --}}
        <div wire:loading.remove>
            @if ($accommodations->isEmpty())
                <div class="text-center p-12 bg-white rounded-2xl shadow-inner">
                    <p class="text-gray-500 text-xl font-medium">No accommodations found. Try adjusting your search! 🧐</p>
                </div>
            @else
                @foreach ($groupedAccommodations as $group)
                    <div class="mb-16">
                        <h2 class="text-3xl font-bold text-gray-800 mb-6 flex items-center gap-3">
                            {{ Str::plural($group['type']) }}
                            <span class="text-indigo-600 text-xl">
                                ({{ count($group['items']) }})
                            </span>
                        </h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                            @foreach ($group['items'] as $accommodation)
                                @php
                                    // Determine the correct route and slug based on the bookable type
                                    $bookableType = $accommodation->bookable_type;
                                    $routePrefix = '';
                                    $slug = $accommodation->bookable->slug;

                                    if ($bookableType === 'App\Models\Hotel') {
                                        $routePrefix = 'hotel-show';
                                    } elseif ($bookableType === 'App\Models\Villa') {
                                        $routePrefix = 'villa.show';
                                    } elseif ($bookableType === 'App\Models\BedAndBreakfast') {
                                        $routePrefix = 'bedandbreakfast.show';
                                    }

                                @endphp

                                {{-- Accommodation Card Component with dynamic route --}}
                                <a href="{{ route($routePrefix, $slug) }}" class="block transform hover:scale-105 transition-transform duration-300 ease-in-out">
                                    <div class="bg-white rounded-3xl shadow-xl overflow-hidden h-full flex flex-col">
                                        {{-- Image --}}
                                        <div class="relative h-48 w-full overflow-hidden">
                                            <img src="{{ $accommodation->bookable->cover_image_url }}" alt="{{ $accommodation->bookable->name }}" class="w-full h-full object-cover">
                                            <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
                                            <span class="absolute bottom-4 left-4 text-white text-sm font-semibold bg-gray-900/50 backdrop-blur-sm px-3 py-1 rounded-full">
                                                <i class="fas fa-bed"></i> {{ $group['type'] }}
                                            </span>
                                        </div>

                                        <div class="p-6 flex flex-col justify-between flex-grow">
                                            {{-- Title and Rating --}}
                                            <div>
                                                <div class="flex items-center justify-between mb-2">
                                                    <h3 class="text-xl font-bold text-gray-900 truncate">{{ $accommodation->bookable->name }}</h3>
                                                    <span class="flex items-center text-yellow-500 font-bold text-sm">
                                                        <i class="fas fa-star mr-1"></i> {{ number_format($accommodation->avg_rating, 1) }}
                                                    </span>
                                                </div>

                                                {{-- Location and Reviews --}}
                                                <div class="flex items-center text-gray-500 text-sm mb-4">
                                                    <i class="fas fa-map-marker-alt mr-2"></i>
                                                    <span>{{ $accommodation->destination->name }}</span>
                                                    <span class="mx-2">•</span>
                                                    <span>{{ $accommodation->reviews_count }} reviews</span>
                                                </div>
                                            </div>

                                            {{-- Price --}}
                                            <div class="text-right mt-4">
                                                <span class="text-gray-900 font-extrabold text-2xl">
                                                    ${{ number_format($accommodation->avg_price_per_night) }}
                                                </span>
                                                <span class="text-gray-500">/ night</span>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @endif

            {{-- Pagination --}}
            <div class="mt-12">
                {{ $accommodations->links() }}
            </div>
        </div>

    </div>
</div>
</div>
