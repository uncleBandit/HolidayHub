<div>
<div class="bg-gray-100 min-h-screen font-sans antialiased">

    {{-- Hero Section with Search --}}
    <div class="relative bg-cover bg-center bg-no-repeat h-[50vh] flex items-center justify-center text-white" style="background-image: url('https://images.unsplash.com/photo-1542314840-a3e791b8f522?q=80&w=2670&auto=format&fit=crop');">
        <div class="absolute inset-0 bg-black/40"></div>
        <div class="relative z-10 text-center px-4 sm:px-6 lg:px-8 max-w-2xl mx-auto">
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold leading-tight tracking-tighter mb-4 drop-shadow-md">
                Find Your Perfect Escape ✨
            </h1>
            <p class="text-lg sm:text-xl lg:text-2xl font-light mb-8 drop-shadow-md">
                Unforgettable stays await. Explore curated hotels, villas, and more.
            </p>

            {{-- Main Search Bar (Elevated) --}}
            <div class="bg-white rounded-full shadow-2xl p-2 flex flex-col md:flex-row items-center gap-2 max-w-xl mx-auto">
                <div class="relative w-full">
                    <input wire:model.live.debounce.500ms="search" type="text" placeholder="Search for places..." class="w-full pl-12 pr-4 py-3 rounded-full border-0 focus:ring-0 text-gray-800">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </div>
                <div class="w-full md:w-auto">
                    <select wire:model.live="destinationId" class="w-full py-3 px-4 rounded-full border-0 focus:ring-0 text-gray-800 bg-white cursor-pointer">
                        <option value="">All Destinations</option>
                        @foreach($destinations as $destination)
                            <option value="{{ $destination->id }}">{{ $destination->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content Container --}}
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12">

        {{-- Filters & Sort Bar --}}
        <div class="flex flex-col sm:flex-row justify-between items-center mb-10">
            <div class="w-full sm:w-auto mb-4 sm:mb-0">
                {{-- Grouping Tabs (Enhanced Functionality) --}}
                <div class="flex items-center gap-2 bg-white rounded-full p-1 shadow-sm">
                    <button wire:click="$set('groupBy', 'none')" class="py-2 px-4 rounded-full text-sm font-medium transition-all duration-200 {{ $groupBy === 'none' ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                        All Results
                    </button>
                    <button wire:click="$set('groupBy', 'type')" class="py-2 px-4 rounded-full text-sm font-medium transition-all duration-200 {{ $groupBy === 'type' ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                        Group by Type
                    </button>
                    <button wire:click="$set('groupBy', 'destination')" class="py-2 px-4 rounded-full text-sm font-medium transition-all duration-200 {{ $groupBy === 'destination' ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                        Group by Destination
                    </button>
                </div>
            </div>

            {{-- Sort By Dropdown --}}
            <div class="w-full sm:w-auto relative">
                <select wire:model.live="sortBy" class="w-full py-2 px-4 pr-10 rounded-full border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors duration-300 bg-white text-sm">
                    <option value="latest">Latest</option>
                    <option value="price_low">Price: Low to High</option>
                    <option value="price_high">Price: High to Low</option>
                    <option value="rating">Top Rated</option>
                </select>
                <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Advanced Filters (Collapsible Sidebar) --}}
        <div x-data="{ open: false }" class="fixed top-28 right-0 z-50">
            <button @click="open = !open" class="bg-indigo-600 text-white p-3 rounded-l-full shadow-lg hover:bg-indigo-700 transition-colors duration-300 focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6a2 2 0 100 4m0-4a2 2 0 110 4m0 4v2m0-6v-2"></path>
                </svg>
            </button>

            <div x-show="open" x-transition:enter="transition-transform duration-300 ease-out" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition-transform duration-300 ease-in" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full" @click.away="open = false" class="fixed inset-y-0 right-0 w-80 bg-white shadow-xl p-6 overflow-y-auto">
                <h3 class="text-2xl font-bold mb-6 text-gray-800">Advanced Filters</h3>
                <div class="space-y-6">
                    <div>
                        <label for="min-price" class="block text-sm font-medium text-gray-700 mb-2">Price Range</label>
                        <div class="flex items-center gap-2">
                            <input wire:model.live.debounce.500ms="minPrice" type="number" placeholder="Min" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            <span class="text-gray-400">-</span>
                            <input wire:model.live.debounce.500ms="maxPrice" type="number" placeholder="Max" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>
                    <div>
                        <label for="min-rating" class="block text-sm font-medium text-gray-700 mb-2">Minimum Rating</label>
                        <input wire:model.live.debounce.500ms="minRating" type="number" step="0.5" min="0" max="5" placeholder="e.g., 4.0" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>
            </div>
        </div>

        {{-- Loading State --}}
        <div wire:loading.flex wire:target="search, destinationId, sortBy, minPrice, maxPrice, minRating, groupBy" class="flex items-center justify-center p-8">
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
                            {{ Str::plural($group['group']) }}
                            <span class="text-indigo-600 text-xl font-light">
                                ({{ count($group['items']) }})
                            </span>
                        </h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                            @foreach ($group['items'] as $accommodation)
                                @php
                                    $bookableType = $accommodation->bookable_type;
                                    $routePrefix = '';
                                    $slug = $accommodation->bookable->slug;

                                    if ($bookableType === 'App\Modules\Accommodation\Domain\Models\Hotel') {
                                        $routePrefix = 'hotel-show';
                                    } elseif ($bookableType === 'App\Modules\Accommodation\Domain\Models\Villa') {
                                        $routePrefix = 'villa.show';
                                    } elseif ($bookableType === 'App\Modules\Accommodation\Domain\Models\BedAndBreakfast') {
                                        $routePrefix = 'bedandbreakfast.show';
                                    }
                                @endphp

                                <a href="{{ route($routePrefix, $slug) }}" class="block transform hover:scale-105 transition-transform duration-300 ease-in-out">
                                    <div class="bg-white rounded-3xl shadow-xl overflow-hidden h-full flex flex-col">
                                        {{-- Image with Overlay --}}
                                        <div class="relative h-48 w-full overflow-hidden">
                                            <img
                                                src="{{ empty($accommodation->bookable->cover_image_url) ? asset('images/default-accommodation.jpg') : $accommodation->bookable->cover_image_url }}"
                                                alt="{{ $accommodation->bookable->name }}"
                                                class="w-full h-full object-cover">
                                            <span class="absolute bottom-4 left-4 text-white text-sm font-semibold bg-gray-900/50 backdrop-blur-sm px-3 py-1 rounded-full">
                                                <i class="fas fa-bed"></i> {{ $group['group'] }}
                                            </span>
                                        </div>

                                        {{-- Content --}}
                                        <div class="p-6 flex flex-col justify-between flex-grow">
                                            <div>
                                                <div class="flex items-start justify-between mb-2">
                                                    <h3 class="text-xl font-bold text-gray-900 truncate">{{ $accommodation->bookable->name }}</h3>
                                                    <span class="flex items-center text-yellow-500 font-bold text-sm ml-2">
                                                        <i class="fas fa-star mr-1"></i> {{ number_format($accommodation->avg_rating, 1) }}
                                                    </span>
                                                </div>
                                                <div class="flex items-center text-gray-500 text-sm mb-4">
                                                    <i class="fas fa-map-marker-alt mr-2"></i>
                                                    <span>{{ $accommodation->destination->name }}</span>
                                                    <span class="mx-2">•</span>
                                                    <span>{{ $accommodation->reviews_count }} reviews</span>
                                                </div>
                                            </div>
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
