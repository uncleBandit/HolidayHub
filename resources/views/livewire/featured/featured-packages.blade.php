<div>
<div class="relative min-h-screen bg-gray-50 dark:bg-gray-950 font-sans antialiased text-gray-800 dark:text-gray-200 overflow-x-hidden">

    {{-- Hero Section with search and filters --}}
    <header class="relative w-full min-h-[500px] flex items-center justify-center">
        {{-- Background Image with subtle gradient overlay --}}
        <img class="absolute inset-0 w-full h-full object-cover z-0" src="https://images.unsplash.com/photo-1542382103308-f41e5768e7ea?q=80&w=2940&auto=format&fit=crop" alt="Tropical holiday destination">
        <div class="absolute inset-0 bg-gradient-to-t from-gray-50 dark:from-gray-950 to-transparent z-10"></div>
        <div class="absolute inset-0 bg-black/30 z-10"></div>

        <div class="relative z-20 w-full max-w-7xl px-4 sm:px-6 lg:px-8 text-center animate-fade-in-down">
            <h1 class="text-4xl sm:text-6xl font-extrabold text-white drop-shadow-lg tracking-tight">
                Your Next Adventure Awaits
            </h1>
            <p class="text-lg md:text-xl mt-4 text-gray-100 drop-shadow-md max-w-2xl mx-auto">
                Discover curated holiday packages and unlock unforgettable experiences.
            </p>

            {{-- Unified Search and Filter Card --}}
            <div class="mt-12 w-full max-w-4xl mx-auto bg-white/70 dark:bg-gray-900/80 backdrop-blur-xl rounded-4xl p-6 shadow-2xl shadow-indigo-500/10 dark:shadow-indigo-500/20 transform transition-all duration-500 hover:scale-[1.01] hover:shadow-indigo-500/20 dark:hover:shadow-indigo-500/30">
                <div class="flex flex-col md:flex-row items-center gap-4">
                    {{-- Search Input --}}
                    <div class="relative w-full md:flex-1">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-search text-xl text-gray-500 dark:text-gray-400"></i>
                        </div>
                        <input wire:model.debounce.300ms="search" type="text" placeholder="Search by destination or package..."
                               class="w-full pl-12 pr-4 py-4 rounded-full border-2 border-transparent bg-gray-100 dark:bg-gray-800 focus:ring-4 focus:ring-indigo-400 dark:focus:ring-indigo-600 focus:border-transparent focus:outline-none transition-all duration-300 text-base placeholder-gray-500 dark:placeholder-gray-400">
                    </div>

                    {{-- Price & Sort Filters --}}
                    <div class="w-full md:w-auto flex flex-col sm:flex-row items-center gap-4">
                        <input wire:model.debounce.300ms="minPrice" type="number" placeholder="Min Price"
                               class="w-full sm:w-28 rounded-full border-2 border-transparent py-4 px-4 bg-gray-100 dark:bg-gray-800 focus:ring-2 focus:ring-indigo-400 dark:focus:ring-indigo-600 text-sm placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none transition-colors duration-200">
                        <span class="text-gray-500 dark:text-gray-400 hidden sm:block">-</span>
                        <input wire:model.debounce.300ms="maxPrice" type="number" placeholder="Max Price"
                               class="w-full sm:w-28 rounded-full border-2 border-transparent py-4 px-4 bg-gray-100 dark:bg-gray-800 focus:ring-2 focus:ring-indigo-400 dark:focus:ring-indigo-600 text-sm placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none transition-colors duration-200">
                        <select wire:model="sortBy"
                                class="w-full sm:min-w-[150px] rounded-full border-2 border-transparent py-4 px-4 bg-gray-100 dark:bg-gray-800 focus:ring-2 focus:ring-indigo-400 dark:focus:ring-indigo-600 text-sm cursor-pointer focus:outline-none transition-colors duration-200">
                            <option value="latest">Latest</option>
                            <option value="price_asc">Price: Low to High</option>
                            <option value="price_desc">Price: High to Low</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </header>

    ---

    <main class="w-full max-w-7xl mx-auto py-16 px-4 sm:px-6 lg:px-8 relative z-10">
        {{-- Loading Indicator --}}
        <div wire:loading class="text-center mb-12 text-indigo-600 dark:text-indigo-400 font-semibold text-lg animate-pulse">
            Loading results...
        </div>

        {{-- Packages Grid with entrance animation --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8 md:gap-12 animate-fade-in">
            @forelse ($packages ?? [] as $package)
                <a href="{{ route('packages.show', $package->id) }}" class="group relative bg-white dark:bg-gray-900 rounded-3xl shadow-xl shadow-gray-200/50 dark:shadow-gray-800/50 overflow-hidden cursor-pointer transform transition-all duration-500 hover:scale-[1.03] hover:shadow-2xl hover:shadow-indigo-200/50 dark:hover:shadow-indigo-500/20">

                    {{-- Image and Overlay --}}
                    <div class="relative h-72 overflow-hidden">
                        <img class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-115" src="{{ $package->image_url ?? 'https://placehold.co/600x400/E5E7EB/6B7280?text=Coming+Soon' }}" alt="{{ $package->title }}">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent flex flex-col justify-end p-6 transition-opacity duration-300 group-hover:opacity-100 opacity-0">
                            <h3 class="text-2xl font-bold text-white mb-2">{{ $package->title }}</h3>
                            <div class="flex items-center justify-between">
                                <span class="text-3xl font-extrabold text-white">${{ number_format($package->price) }}</span>
                                <div class="w-12 h-12 flex items-center justify-center rounded-full bg-indigo-500/80 backdrop-blur-sm text-white transition-transform duration-300 transform group-hover:rotate-45">
                                    <i class="fas fa-paper-plane"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Details outside of overlay for better SEO and accessibility --}}
                    <div class="p-6">
                        <p class="text-sm font-light text-gray-500 dark:text-gray-400 mb-2">
                            <i class="fas fa-map-marker-alt mr-1 text-indigo-500"></i>
                            {{ $package->destination->name ?? 'Unknown Destination' }}
                        </p>
                        <p class="text-gray-600 dark:text-gray-300 text-base line-clamp-2">
                            {{ $package->description }}
                        </p>
                    </div>
                </a>
            @empty
                <div class="col-span-full text-center py-20 bg-white dark:bg-gray-800 rounded-3xl shadow-lg border border-gray-100 dark:border-gray-700">
                    <i class="fas fa-compass text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                    <p class="font-bold text-xl text-gray-500 dark:text-gray-400">No featured packages found.</p>
                    <p class="text-gray-400 dark:text-gray-500 mt-2">Try adjusting your search criteria.</p>
                </div>
            @endforelse
        </div>

        {{-- Pagination Links with subtle style --}}
        <div class="mt-16 flex justify-center">
            {{ $packages->links('pagination::tailwind') }}
        </div>
    </main>
</div>

<style>
    .rounded-4xl { border-radius: 2rem; }

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
</style>
</div>
