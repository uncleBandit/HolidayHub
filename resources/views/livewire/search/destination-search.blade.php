<div>
<div class="relative min-h-screen bg-gray-50 font-sans antialiased text-gray-800">
    <div class="absolute inset-0 bg-gradient-to-br from-indigo-50 to-gray-100 opacity-60"></div>

    {{-- Header Section: Centered and Elevated --}}
    <header class="relative z-30 pt-16 pb-32">
        <div class="relative w-full max-w-6xl mx-auto px-4 md:px-8" x-data="{ open: @entangle('showResults') }" @click.away="open = false">

            {{-- Main Search Card with Glassmorphism Effect --}}
            <form action="{{ route('destinations.index') }}" method="GET">
                <div class="relative grid grid-cols-1 lg:grid-cols-4 items-center gap-6 bg-white/70 backdrop-blur-xl rounded-4xl p-6 shadow-2xl shadow-holiday-200/50 transform transition-all duration-300 hover:scale-[1.01]">
                    <div class="relative lg:col-span-3">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-6 pointer-events-none">
                            <i class="fas fa-search text-xl text-holiday-400 animate-pulse"></i>
                        </div>
                        <input
                            type="text"
                            name="searchQuery"
                            placeholder="Where will your next adventure take you?"
                            class="w-full pl-14 pr-6 py-4 rounded-3xl border-2 border-transparent bg-holiday-50/70 text-lg font-medium placeholder-holiday-400 focus:ring-4 focus:ring-holiday-400 focus:border-holiday-400 focus:outline-none transition-all duration-300"
                            wire:model.live.debounce.300ms="searchQuery"
                            @focus="open = true"
                        >
                    </div>
                    <div class="lg:col-span-1 flex items-center justify-center">
                        <button type="submit" class="w-full flex items-center justify-center bg-gradient-to-br from-holiday-700 to-holiday-900 text-white font-bold text-lg py-4 rounded-3xl shadow-lg shadow-holiday-700/30 transform transition-all duration-300 hover:scale-105 hover:shadow-xl active:scale-[0.98]">
                            <i class="fas fa-paper-plane mr-3 transform -rotate-45"></i>
                            Explore
                        </button>
                    </div>
                </div>
            </form>

            {{-- Search Suggestions Dropdown with Glassmorphism --}}
            @if ($showResults && count($results) > 0)
                <div class="absolute top-full mt-6 w-full z-50 bg-white/80 backdrop-blur-md rounded-3xl shadow-xl border border-gray-100 overflow-hidden max-h-96 overflow-y-auto transform transition-opacity duration-300 animate-fade-in-up">
                    @foreach ($results as $destination)
                        <a href="{{ route('destination.show', $destination->id) }}" class="flex items-center gap-6 p-4 md:p-6 hover:bg-holiday-50/50 transition duration-200">
                            <img src="{{ $destination->image_url ?? 'https://placehold.co/100x100' }}" class="w-20 h-20 rounded-xl object-cover shadow-sm">
                            <div class="flex flex-col">
                                <span class="font-bold text-xl text-holiday-800">{{ $destination->name }}</span>
                                <span class="text-sm text-holiday-500 mt-1">{{ $destination->city }}, {{ $destination->country }}</span>
                            </div>
                            <i class="fas fa-arrow-right ml-auto text-holiday-400 hover:text-holiday-600 transition"></i>
                        </a>
                    @endforeach
                </div>
            @elseif(strlen($searchQuery) > 2 && $showResults)
                <div class="absolute top-full mt-6 w-full z-50 bg-white/80 backdrop-blur-md rounded-3xl shadow-lg border border-gray-200">
                    <p class="p-6 text-center text-gray-500 font-medium">No destinations found for "{{ $searchQuery }}".</p>
                </div>
            @endif
        </div>
    </header>

    ---

    <main class="w-full max-w-6xl mx-auto px-4 md:px-8 pb-20">
        <div class="flex flex-col md:flex-row items-center justify-between mb-12">
            <h2 class="text-3xl md:text-4xl font-extrabold text-holiday-800 mb-4 md:mb-0">Featured Adventures</h2>
            <div class="flex items-center gap-4">
                <span class="text-sm font-semibold text-gray-500 hidden md:inline">Sort by:</span>
                <select wire:model="sortBy" class="rounded-2xl border-2 border-gray-200 bg-white px-5 py-3 text-lg text-gray-700 focus:ring-2 focus:ring-holiday-400 focus:outline-none transition-all duration-200">
                    <option value="name">Name (A-Z)</option>
                    <option value="created_at">Newest</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8 md:gap-12">
            @forelse($destinations as $destination)
                <a href="{{ route('destination.show', $destination->id) }}" class="group relative rounded-3xl shadow-xl overflow-hidden cursor-pointer transform transition-all duration-500 hover:scale-[1.03] hover:shadow-2xl">
                    <img src="{{ $destination->image_url ?? 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?q=80&w=2946&auto=format&fit=crop' }}"
                        onerror="this.onerror=null;this.src='https://placehold.co/800x600/D4D4D8/FFFFFF?text=Destination';"
                        class="w-full h-72 object-cover transition-transform duration-500 group-hover:scale-110"
                        alt="{{ $destination->name }}">

                    {{-- Dynamic Card Overlay --}}
                    <div class="absolute inset-0 bg-gradient-to-t from-gray-900/80 to-transparent p-6 flex flex-col justify-end transition-opacity duration-300 group-hover:opacity-75">
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
                <div class="col-span-full text-center py-16 bg-white rounded-3xl shadow-lg border border-gray-100">
                    <i class="fas fa-compass text-6xl text-gray-300 mb-4"></i>
                    <p class="font-bold text-xl text-gray-500">No destinations match your search.</p>
                    <p class="text-gray-400 mt-2">Try a different search or explore some of our top picks!</p>
                </div>
            @endforelse
        </div>

        {{-- Pagination with more style --}}
        <div class="mt-20 flex justify-center">
            {{ $destinations->links('pagination::tailwind') }}
        </div>
    </main>
</div>

{{-- Custom CSS Styles --}}
<style>
    :root {
        --color-holiday-50: #f5f5f7;
        --color-holiday-400: #88a4e1;
        --color-holiday-500: #6b88d2;
        --color-holiday-700: #4a5c96;
        --color-holiday-900: #1a2333;
    }

    .bg-holiday-50 { background-color: var(--color-holiday-50); }
    .bg-holiday-700 { background-color: var(--color-holiday-700); }
    .bg-holiday-900 { background-color: var(--color-holiday-900); }
    .text-holiday-400 { color: var(--color-holiday-400); }
    .text-holiday-500 { color: var(--color-holiday-500); }
    .text-holiday-800 { color: #2d3b5e; }
    .focus-ring-holiday-400:focus { --tw-ring-color: var(--color-holiday-400); }
    .focus-border-holiday-400:focus { --tw-border-color: var(--color-holiday-400); }
    .shadow-holiday-200 { box-shadow: 0 4px 6px -1px var(--color-holiday-200), 0 2px 4px -2px var(--color-holiday-200); }
    .shadow-holiday-700 { box-shadow: 0 4px 6px -1px var(--color-holiday-700), 0 2px 4px -2px var(--color-holiday-700); }
    .rounded-4xl { border-radius: 2rem; }

    @keyframes fade-in-up {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fade-in-up {
        animation: fade-in-up 0.5s ease-out forwards;
    }
</style>
</div>
