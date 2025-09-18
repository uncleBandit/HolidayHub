<div>

<div class="w-full max-w-6xl mx-auto px-4 md:px-8 py-12 lg:py-24 font-sans antialiased text-gray-800 dark:text-gray-200 relative z-20">
<!-- Sticky Search & Sort Header -->
<header class="sticky top-6 z-30">
<div class="mx-auto max-w-7xl">
<div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl rounded-full shadow-lg border border-gray-100/50 dark:border-gray-800/50 p-3 flex flex-col md:flex-row items-center justify-between space-y-4 md:space-y-0 md:space-x-6">

            <div class="relative w-full md:w-1/2" x-data="{ open: @entangle('showResults') }" @click.away="open = false">
                <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                    <svg class="h-6 w-6 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M10 4a6 6 0 0 1 6 6c0 1.25-.33 2.4-.92 3.39l4.5 4.5a.75.75 0 0 1-1.06 1.06l-4.5-4.5A5.96 5.96 0 0 1 10 16a6 6 0 0 1 0-12zm0 1.5A4.5 4.5 0 0 0 5.5 10a4.5 4.5 0 0 0 9 0A4.5 4.5 0 0 0 10 5.5z" />
                    </svg>
                </div>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search destinations..."
                    class="w-full pl-12 pr-6 py-4 rounded-full border-none bg-gray-100 dark:bg-gray-800 text-lg text-gray-700 dark:text-gray-300 transition-colors duration-200 focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:outline-none placeholder-gray-500 dark:placeholder-gray-400"
                >

                <!-- Search Suggestions Dropdown -->
                @if($showResults && $destinations->isNotEmpty())
                    <div class="absolute top-full mt-4 w-full z-50 bg-white dark:bg-gray-900 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-800 overflow-hidden max-h-96 overflow-y-auto animate-fade-in-up">
                        @foreach($destinations as $destination)
                            <a href="{{ route('destination.show', $destination->id) }}" class="flex items-center gap-6 p-4 md:p-6 hover:bg-gray-50 dark:hover:bg-gray-800 transition duration-200">
                                <img src="{{ $destination->image_url ?? 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?q=80&w=2946&auto=format&fit=crop' }}"
                                     onerror="this.onerror=null;this.src='https://placehold.co/800x600/D4D4D8/FFFFFF?text=Destination';"
                                     class="w-16 h-16 rounded-xl object-cover shadow-sm"
                                     alt="Image of {{ $destination->name }}">
                                <div class="flex flex-col">
                                    <span class="font-bold text-xl text-gray-800 dark:text-gray-200">{{ $destination->name }}</span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $destination->city }}, {{ $destination->country }}</span>
                                </div>
                                <i class="fas fa-arrow-right ml-auto text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition"></i>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="flex space-x-4 w-full md:w-auto justify-center">
                <select wire:model.live="sortBy" class="rounded-full border-none bg-gray-100 dark:bg-gray-800 px-6 py-4 text-lg text-gray-700 dark:text-gray-300 transition-colors duration-200 focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="name">Destination Name</option>
                    <option value="rating">Top Rated</option>
                    <option value="created_at">Newest</option>
                </select>
            </div>
        </div>
    </div>
</header>

<!-- Loading Indicator -->
<div wire:loading class="text-center mb-12 mt-12 text-indigo-600 dark:text-indigo-400 font-semibold text-lg animate-pulse">
    Loading results...
</div>

<!-- Destination Cards Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8 md:gap-12 mt-12">
    @forelse($destinations as $destination)
        <a href="{{ route('destination.show', $destination->id) }}" class="group relative rounded-3xl shadow-xl overflow-hidden cursor-pointer transform transition-all duration-500 hover:scale-[1.03] hover:shadow-2xl">
            <img src="{{ $destination->image_url ?? 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?q=80&w=2946&auto=format&fit=crop' }}"
                 onerror="this.onerror=null;this.src='https://placehold.co/800x600/D4D4D8/FFFFFF?text=Destination';"
                 class="w-full h-72 object-cover transition-transform duration-500 group-hover:scale-110"
                 alt="Image of {{ $destination->name }}">

            <!-- Card Overlay with details -->
            <div class="absolute inset-0 bg-gradient-to-t from-gray-900/80 to-transparent p-6 flex flex-col justify-end transition-opacity duration-300 group-hover:opacity-100">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold text-white bg-red-500 px-3 py-1 rounded-full uppercase tracking-wider">
                        {{ $destination->packages_count ?? '0' }} Packages
                    </span>
                    <div class="flex items-center gap-1">
                        <span class="text-sm font-semibold text-yellow-400">4.5</span>
                        <i class="fas fa-star text-yellow-400"></i>
                    </div>
                </div>
                <h3 class="text-3xl font-bold text-white mt-1">{{ $destination->name }}</h3>
                <p class="text-lg text-gray-200 font-light mt-1">{{ $destination->city }}, {{ $destination->country }}</p>
            </div>
        </a>
    @empty
        <div class="col-span-full text-center py-16 bg-white dark:bg-gray-900 rounded-3xl shadow-lg border border-gray-100 dark:border-gray-800">
            <i class="fas fa-compass text-6xl text-gray-300 dark:text-gray-700 mb-4"></i>
            <p class="font-bold text-xl text-gray-500 dark:text-gray-400">No destinations match your search.</p>
            <p class="text-gray-400 dark:text-gray-500 mt-2">Try a different search or explore some of our top picks!</p>
        </div>
    @endforelse
</div>

<!-- Pagination with more style -->
<div class="mt-20 flex justify-center">
    {{ $destinations->links('pagination::tailwind') }}
</div>

</div>


</div>
