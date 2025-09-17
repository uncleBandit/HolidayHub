<div>
<div class="space-y-12 py-10 px-4 sm:px-6 lg:px-8">

    <header class="sticky top-6 z-30">
        <div class="mx-auto max-w-7xl">
            <div class="bg-white/80 backdrop-blur-xl rounded-full shadow-lg border border-gray-100/50 p-3 flex flex-col md:flex-row items-center justify-between space-y-4 md:space-y-0 md:space-x-6">

                <div class="relative w-full md:w-1/2">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                        <svg class="h-6 w-6 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M10 4a6 6 0 0 1 6 6c0 1.25-.33 2.4-.92 3.39l4.5 4.5a.75.75 0 0 1-1.06 1.06l-4.5-4.5A5.96 5.96 0 0 1 10 16a6 6 0 0 1 0-12zm0 1.5A4.5 4.5 0 0 0 5.5 10a4.5 4.5 0 0 0 9 0A4.5 4.5 0 0 0 10 5.5z" />
                        </svg>
                    </div>
                    <input
                        type="text"
                        wire:model.debounce.300ms="search"
                        placeholder="Search offers..."
                        class="w-full pl-12 pr-6 py-4 rounded-full border-none bg-gray-100 text-lg text-gray-700 transition-colors duration-200 focus:bg-white focus:ring-2 focus:ring-tropical-blue focus:outline-none placeholder-gray-500"
                    >
                </div>

                <div class="flex space-x-4 w-full md:w-auto justify-center">
                    <select wire:model="sortBy" class="rounded-full border-none bg-gray-100 px-6 py-4 text-lg text-gray-700 transition-colors duration-200 focus:bg-white focus:ring-2 focus:ring-tropical-blue focus:outline-none">
                        <option value="rating">Rating</option>
                        <option value="newest">Newest</option>
                    </select>
                    <select wire:model="direction" class="rounded-full border-none bg-gray-100 px-6 py-4 text-lg text-gray-700 transition-colors duration-200 focus:bg-white focus:ring-2 focus:ring-tropical-blue focus:outline-none">
                        <option value="desc">Descending</option>
                        <option value="asc">Ascending</option>
                    </select>
                </div>
            </div>
        </div>
    </header>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8 max-w-7xl mx-auto">
        @forelse($offers as $offer)
            <a href="{{ route('offers.show', $offer) }}" class="relative bg-white rounded-3xl shadow-xl overflow-hidden cursor-pointer transform transition-all duration-500 hover:scale-105 hover:shadow-2xl group">
                <div class="relative w-full h-64">
                    <img src="{{ $offer->main_image ?? 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?q=80&w=2946&auto=format&fit=crop' }}"
                         onerror="this.onerror=null;this.src='https://placehold.co/600x400/D9F99D/FFFFFF?text=Offer+Image';"
                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                         alt="{{ $offer->name }}">
                    <div class="absolute inset-0 bg-gradient-to-t from-gray-900/50 to-transparent"></div>

                    @if($offer->discount_percent > 0)
                        <div class="absolute top-4 right-4 bg-sunset-orange text-white text-sm font-bold px-3 py-1.5 rounded-full shadow-lg transform rotate-3 transition-transform duration-300 group-hover:rotate-0 group-hover:scale-110">
                            {{ $offer->discount_percent }}% OFF
                        </div>
                    @endif
                </div>

                <div class="p-6">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-2xl font-bold text-deep-ocean line-clamp-1">{{ $offer->name }}</h3>
                        <div class="flex items-center text-yellow-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 fill-current" viewBox="0 0 24 24">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 18.27l-6.18 3.25L7 14.14l-5-4.87 6.91-1.01L12 2z"/>
                            </svg>
                            <span class="ml-1 text-base font-semibold text-gray-600">{{ number_format($offer->rating, 1) }}</span>
                        </div>
                    </div>
                    <p class="text-gray-600 font-light line-clamp-2">{{ Str::limit($offer->description, 100) }}</p>
                    <div class="mt-4 flex items-center justify-between">
                        <span class="text-lg font-bold text-teal-600">
                            {{ number_format($offer->price, 2) }} {{ $offer->currency ?? 'USD' }}
                        </span>
                        <span class="text-sm font-medium text-gray-500">
                           <span class="line-through">{{ $offer->original_price ?? '' }}</span>
                        </span>
                    </div>
                </div>
            </a>
        @empty
            <div class="col-span-full text-center py-16">
                <p class="text-gray-500 text-2xl font-semibold">
                    No offers found.
                </p>
                <p class="text-gray-400 mt-2">
                    Please try adjusting your search or filters.
                </p>
            </div>
        @endforelse
    </div>

    <div class="mt-12 max-w-7xl mx-auto">
        {{ $offers->links() }}
    </div>

</div>
</div>
