<div>
<div class="relative font-sans antialiased text-gray-800 dark:text-gray-200">

    <div class="space-y-12 py-10 px-4 sm:px-6 lg:px-8 bg-gray-50 dark:bg-gray-950 min-h-screen">
        <!-- Sticky Search & Sort Header -->
        <header class="sticky top-6 z-30">
            <div class="mx-auto max-w-7xl">
                <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl rounded-full shadow-lg border border-gray-100/50 dark:border-gray-800/50 p-3 flex flex-col md:flex-row items-center justify-between space-y-4 md:space-y-0 md:space-x-6">

                    <div class="relative w-full md:w-1/2">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                            <svg class="h-6 w-6 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M10 4a6 6 0 0 1 6 6c0 1.25-.33 2.4-.92 3.39l4.5 4.5a.75.75 0 0 1-1.06 1.06l-4.5-4.5A5.96 5.96 0 0 1 10 16a6 6 0 0 1 0-12zm0 1.5A4.5 4.5 0 0 0 5.5 10a4.5 4.5 0 0 0 9 0A4.5 4.5 0 0 0 10 5.5z" />
                            </svg>
                        </div>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Search hotels..."
                            class="w-full pl-12 pr-6 py-4 rounded-full border-none bg-gray-100 dark:bg-gray-800 text-lg text-gray-700 dark:text-gray-300 transition-colors duration-200 focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-tropical-blue focus:outline-none placeholder-gray-500 dark:placeholder-gray-400"
                        >
                    </div>

                    <div class="flex space-x-4 w-full md:w-auto justify-center">
                        <select wire:model.live="sortBy" class="rounded-full border-none bg-gray-100 dark:bg-gray-800 px-6 py-4 text-lg text-gray-700 dark:text-gray-300 transition-colors duration-200 focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-tropical-blue focus:outline-none">
                            <option value="rating">Top Rated</option>
                            <option value="stars">Star Ranking</option>
                            <option value="created_at">Newest</option>
                        </select>
                    </div>
                </div>
            </div>
        </header>

        <!-- Loading Indicator -->
        <div wire:loading class="text-center mb-12 text-indigo-600 dark:text-indigo-400 font-semibold text-lg animate-pulse">
            Loading results...
        </div>

        <!-- Hotels Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8 max-w-7xl mx-auto">
            @forelse ($hotels as $hotel)
                <a href="{{ route('hotel-show', ['slug' => $hotel->slug]) }}" class="relative bg-white dark:bg-gray-900 rounded-3xl shadow-xl overflow-hidden cursor-pointer group transform transition-all duration-500 hover:scale-[1.03] hover:shadow-2xl">
                    <!-- Image and Overlay -->
                    <div class="relative w-full h-72">
                        <img src="{{ $hotel->cover_image }}"
                            onerror="this.onerror=null;this.src='https://placehold.co/600x400/E5E7EB/6B7280?text=Hotel+Image';"
                            class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                            alt="{{ $hotel->name }}">
                        <div class="absolute inset-0 bg-gradient-to-t from-gray-900/70 to-transparent"></div>
                    </div>

                    <!-- Details on overlay with animation -->
                    <div class="absolute bottom-0 p-6 w-full text-white transform translate-y-0 group-hover:-translate-y-2 transition-transform duration-500">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-3xl font-extrabold line-clamp-1">{{ $hotel->name }}</h3>
                            <span class="text-lg font-bold bg-white/20 backdrop-blur-sm rounded-full px-4 py-1.5 border border-white/30 text-white">
                                ${{ number_format($hotel->price, 0) }}
                            </span>
                        </div>
                        <p class="text-gray-200 font-light text-sm line-clamp-2 mt-2">{{ $hotel->city }}, {{ $hotel->country }}</p>

                        <div class="mt-4 flex items-center justify-between">
                            <div class="flex items-center text-yellow-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 fill-current" viewBox="0 0 24 24">
                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 18.27l-6.18 3.25L7 14.14l-5-4.87 6.91-1.01L12 2z"/>
                                </svg>
                                <span class="ml-1 text-lg font-semibold">{{ number_format($hotel->rating, 1) }}</span>
                            </div>
                            <div class="w-10 h-10 flex items-center justify-center rounded-full bg-tropical-blue text-white transition-transform duration-300 transform group-hover:rotate-45">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-2 2a2 2 0 012 2v1a2 2 0 01-2 2H9a2 2 0 01-2-2V9a2 2 0 012-2h2m-6 0h.01M16 16h.01" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </a>
            @empty
                <div class="col-span-full text-center py-16 bg-white dark:bg-gray-900 rounded-3xl shadow-lg border border-gray-100 dark:border-gray-800">
                    <i class="fas fa-info-circle text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                    <p class="font-bold text-xl text-gray-500 dark:text-gray-400">No hotels found.</p>
                    <p class="text-gray-400 dark:text-gray-500 mt-2">Try adjusting your search criteria.</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination Links -->
        <div class="mt-12 max-w-7xl mx-auto flex justify-center">
            {{ $hotels->links('pagination::tailwind') }}
        </div>
    </div>
</div>

<style>
    .rounded-full { border-radius: 9999px; }
    .rounded-3xl { border-radius: 1.5rem; }

    @keyframes fade-in-down {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in-down { animation: fade-in-down 0.8s ease-out forwards; }

    @keyframes fade-in {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    .animate-fade-in { animation: fade-in 1s ease-in forwards; }

    .line-clamp-2 {
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }

    .line-clamp-1 {
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
    }

    .bg-tropical-blue { background-color: #4a5c96; }
</style>


</div>
