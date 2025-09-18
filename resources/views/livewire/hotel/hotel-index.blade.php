<div>
    <div class="container mx-auto px-4 py-8">

    <div class="bg-white rounded-3xl p-6 md:p-10 shadow-xl mb-12">
        <h1 class="text-4xl md:text-5xl font-extrabold text-gray-800 leading-tight mb-4">
            Find Your Dream Hotel
        </h1>
        <p class="text-lg md:text-xl text-gray-600 mb-8">
            Explore thousands of hotels and find the perfect stay for your next trip.
        </p>

        <div class="flex flex-col lg:flex-row gap-4">
            <div class="relative flex-1">
                <input wire:model.live.debounce.500ms="search" type="text"
                    placeholder="Search by hotel name, city, or country..."
                    class="w-full pl-12 pr-4 py-4 rounded-full border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                    <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </span>
            </div>

            <div class="hidden md:flex items-center gap-4">
                <input wire:model.live.debounce.500ms="minPrice" type="number" placeholder="Min Price"
                    class="w-32 py-4 px-4 rounded-full border border-gray-300 text-center focus:outline-none focus:ring-2 focus:ring-blue-500">
                <span class="text-gray-500">-</span>
                <input wire:model.live.debounce.500ms="maxPrice" type="number" placeholder="Max Price"
                    class="w-32 py-4 px-4 rounded-full border border-gray-300 text-center focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>

        <div class="mt-6 flex flex-wrap items-center gap-4">
            <span class="text-gray-600 font-semibold">Sort by:</span>
            <button wire:click="sortBy('name')" class="py-2 px-4 rounded-full text-sm font-medium transition-colors
                {{ $sortField === 'name' ? 'bg-blue-600 text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Name
                @if ($sortField === 'name')
                    <span class="ml-1">{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                @endif
            </button>
            <button wire:click="sortBy('avg_price_per_night')" class="py-2 px-4 rounded-full text-sm font-medium transition-colors
                {{ $sortField === 'avg_price_per_night' ? 'bg-blue-600 text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Price
                @if ($sortField === 'avg_price_per_night')
                    <span class="ml-1">{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                @endif
            </button>
            <button wire:click="sortBy('avg_rating')" class="py-2 px-4 rounded-full text-sm font-medium transition-colors
                {{ $sortField === 'avg_rating' ? 'bg-blue-600 text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Top Rated
                @if ($sortField === 'avg_rating')
                    <span class="ml-1">{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                @endif
            </button>
        </div>
    </div>

    <div wire:loading.class="opacity-50" class="transition-opacity duration-300">
        @if ($hotels->isEmpty())
            <div class="text-center py-12 bg-gray-100 rounded-2xl shadow-inner">
                <p class="text-2xl font-semibold text-gray-500">No hotels found matching your criteria.</p>
                <p class="text-gray-400 mt-2">Try adjusting your search or filters.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                @foreach ($hotels as $hotel)
                    <a href="{{ route('hotel-show', $hotel->slug) }}" class="relative group block rounded-2xl overflow-hidden shadow-lg transition-transform duration-300 transform hover:scale-105">
                        <img src="{{ $hotel->image_url ?? 'https://images.unsplash.com/photo-1571896349842-33c89424de2d?ixlib=rb-1.2.1&auto=format&fit=crop&w=1000&q=80' }}"
                            alt="{{ $hotel->name }}"
                            class="w-full h-64 object-cover transition-transform duration-500 group-hover:scale-110">
                        <div class="p-6 bg-white">
                            <h3 class="text-xl font-bold text-gray-900 mb-1">{{ $hotel->name }}</h3>
                            <p class="text-sm text-gray-500 flex items-center mb-2">
                                <svg class="h-4 w-4 mr-1 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a8 8 0 100 16 8 8 0 000-16zM2 10a8 8 0 0116 0 8 8 0 01-16 0z" /></svg>
                                {{ $hotel->city }}, {{ $hotel->country }}
                            </p>
                            <div class="flex items-center justify-between mt-4">
                                <div class="flex items-center text-yellow-400">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.961a1 1 0 00.95.69h4.16a1 1 0 01.595 1.838l-3.376 2.454a1 1 0 00-.364 1.118l1.286 3.961c.3.921-.755 1.688-1.54 1.118L10 15.659l-3.376 2.454c-.785.57-1.84-.197-1.54-1.118l1.286-3.961a1 1 0 00-.364-1.118L2.094 9.416a1 1 0 01.595-1.838h4.16a1 1 0 00.95-.69l1.286-3.961z" /></svg>
                                    <span class="ml-1 text-lg font-bold text-gray-900">{{ number_format($hotel->avg_rating ?? 0, 1) }}</span>
                                </div>
                                <span class="text-xl font-bold text-gray-900">${{ number_format($hotel->avg_price_per_night) }}<span class="text-sm font-normal text-gray-500">/night</span></span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    @if ($hotels->hasPages())
        <div class="mt-12">
            {{ $hotels->links() }}
        </div>
    @endif
</div>
</div>
