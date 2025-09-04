<div>
<div class="bg-gray-50 dark:bg-gray-900 min-h-screen font-sans antialiased text-gray-800 dark:text-gray-200">
{{-- Top Bar with Filters - Unified and Elegant --}}
<div class="sticky top-0 z-20 bg-white/70 dark:bg-gray-950/80 backdrop-blur-lg py-6 px-4 rounded-b-3xl shadow-xl border-b border-gray-100 dark:border-gray-800">
<div class="max-w-7xl mx-auto">
<div class="text-center mb-6">
<h1 class="text-4xl sm:text-5xl font-extrabold text-gray-900 dark:text-white">Featured Holiday Packages</h1>
<p class="text-lg mt-2 text-gray-600 dark:text-gray-400">
Discover hand-picked selections tailored just for you.
</p>
</div>

        <div class="bg-gray-100 dark:bg-gray-800 rounded-full shadow-inner p-2 flex flex-col md:flex-row items-center justify-between space-y-2 md:space-y-0 md:space-x-4">
            {{-- Search Input --}}
            <div class="relative w-full md:w-1/2">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="h-6 w-6 text-gray-500 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                    </svg>
                </div>
                <input wire:model.debounce.300ms="search" type="text" id="search" placeholder="Search by title..."
                       class="w-full pl-12 pr-4 py-3 rounded-full border-0 bg-transparent focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-colors duration-200 text-base placeholder-gray-500 dark:placeholder-gray-400">
            </div>

            {{-- Price Filters --}}
            <div class="w-full md:w-1/4 flex items-center space-x-2">
                <input wire:model.debounce.300ms="minPrice" type="number" placeholder="Min price"
                       class="block w-full rounded-full border-0 py-3 px-4 bg-transparent focus:ring-2 focus:ring-indigo-500 sm:text-base placeholder-gray-500 dark:placeholder-gray-400">
                <span class="text-gray-500 dark:text-gray-400 text-lg">-</span>
                <input wire:model.debounce.300ms="maxPrice" type="number" placeholder="Max price"
                       class="block w-full rounded-full border-0 py-3 px-4 bg-transparent focus:ring-2 focus:ring-indigo-500 sm:text-base placeholder-gray-500 dark:placeholder-gray-400">
            </div>

            {{-- Sort By Dropdown --}}
            <div class="w-full md:w-1/4">
                <label for="sort" class="sr-only">Sort By</label>
                <select wire:model="sortBy" id="sort"
                        class="w-full rounded-full border-0 py-3 px-4 bg-transparent focus:ring-2 focus:ring-indigo-500 sm:text-base cursor-pointer">
                    <option value="latest">Latest</option>
                    <option value="price_asc">Price: Low to High</option>
                    <option value="price_desc">Price: High to Low</option>
                </select>
            </div>
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
    {{-- Loading Indicator --}}
    <div wire:loading class="text-center mb-6 text-indigo-600 dark:text-indigo-400 font-semibold text-lg animate-pulse">
        Loading...
    </div>

    {{-- Packages Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
        @forelse ($packages ?? [] as $package)
            <a href="{{ route('packages.show', $package->id) }}" class="group relative bg-white dark:bg-gray-800 rounded-3xl shadow-lg overflow-hidden cursor-pointer transform transition-all duration-300 hover:scale-[1.03] hover:shadow-2xl">
                <img class="h-48 w-full object-cover transition-transform duration-300 group-hover:scale-110" src="{{ $package->image_url ?? 'https://placehold.co/600x400/3B82F6/FFFFFF?text=Coming+Soon' }}" alt="{{ $package->title }}">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white truncate">
                            {{ $package->title }}
                        </h3>
                        <span class="text-xl font-extrabold text-indigo-600 dark:text-indigo-400">
                            ${{ number_format($package->price) }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        <i class="fas fa-map-marker-alt mr-1 text-indigo-500"></i>
                        {{ $package->destination->name ?? 'Unknown Destination' }}
                    </p>
                    <p class="text-gray-600 dark:text-gray-300 text-base mb-4 line-clamp-3">
                        {{ $package->description }}
                    </p>
                    <div class="w-full px-6 py-3 text-center border border-transparent rounded-xl shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 transition-colors duration-300">
                        View Details
                    </div>
                </div>
            </a>
        @empty
            <div class="col-span-full text-center py-12">
                <p class="text-2xl font-semibold text-gray-500 dark:text-gray-400">No featured packages found.</p>
            </div>
        @endforelse
    </div>

    {{-- Pagination Links --}}
    <div class="mt-12">
        {{ $packages->links() }}
    </div>
</div>

</div>

<script src="https://www.google.com/search?q=https://cdn.tailwindcss.com"></script>

<script>
tailwind.config = {
darkMode: 'class',
}
</script>

<style>
.line-clamp-3 {
overflow: hidden;
display: -webkit-box;
-webkit-line-clamp: 3;
-webkit-box-orient: vertical;
}
</style>
</div>
