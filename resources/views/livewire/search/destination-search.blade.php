<div>
    {{-- Top Bar --}}
    <div class="sticky top-0 z-20 bg-white/70 backdrop-blur-md py-6 px-4 rounded-b-3xl shadow-lg border-b border-gray-100 flex items-center justify-between space-x-4 mb-8">

        <div class="relative w-full max-w-5xl mx-auto" x-data="{ open: @entangle('showResults') }" @click.away="open = false">
    {{-- Main Search Bar --}}
    <form action="{{ route('destinations.index') }}" method="GET">
        <div class="grid md:grid-cols-4 lg:grid-cols-5 gap-4 bg-white/90 backdrop-blur-md rounded-3xl p-4 shadow-xl text-neutral-800">
            <div class="relative md:col-span-2">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M10 4a6 6 0 0 1 6 6c0 1.25-.33 2.4-.92 3.39l4.5 4.5a.75.75 0 0 1-1.06 1.06l-4.5-4.5A5.96 5.96 0 0 1 10 16a6 6 0 0 1 0-12zm0 1.5A4.5 4.5 0 0 0 5.5 10a4.5 4.5 0 0 0 9 0A4.5 4.5 0 0 0 10 5.5z" />
                    </svg>
                </div>
                <input
                    type="text"
                    name="searchQuery"
                    placeholder="Search destinations..."
                    class="w-full pl-10 pr-4 py-3 rounded-full border-none bg-gray-100 focus:ring-2 focus:ring-tropical-blue focus:outline-none transition-colors duration-200 text-lg"
                    wire:model.live.debounce.300ms="searchQuery"
                    @focus="open = true"
                >
            </div>

            <button type="submit" class="md:col-span-2 lg:col-span-1 flex items-center justify-center bg-gradient-to-r from-indigo-600 to-indigo-700 text-white font-semibold py-3 rounded-full hover:scale-105 transition-transform shadow-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.2-5.2M5.2 5.2a7.5 7.5 0 1010.6 10.6 7.5 7.5 0 00-10.6-10.6z"/>
                </svg>
                Search
            </button>
        </div>
    </form>

    {{-- Search Suggestions Dropdown --}}
    @if ($showResults && count($results) > 0)
        <div class="absolute mt-2 w-full max-w-5xl z-50 bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden max-h-60 overflow-y-auto">
            @foreach ($results as $destination)
                <a href="{{ route('destination.show',  $destination->id) }}" class="flex items-center gap-4 p-4 hover:bg-indigo-50 transition">
                    <img src="{{ $destination->image_url ?? 'https://placehold.co/100x100' }}" class="w-12 h-12 rounded-lg object-cover">
                    <div class="flex flex-col">
                        <span class="font-semibold text-zinc-900">{{ $destination->name }}</span>
                        <span class="text-sm text-zinc-500">{{ $destination->city }}, {{ $destination->country }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    @elseif(strlen($searchQuery) > 2 && $showResults)
        <div class="absolute mt-2 w-full max-w-5xl z-50 bg-white rounded-2xl shadow-lg border border-gray-200">
            <p class="p-4 text-center text-gray-500">No results found.</p>
        </div>
    @endif
    </div>

        {{-- Sort Dropdown --}}
        <select wire:model="sortBy" class="rounded-full border-none bg-gray-100 px-6 py-3 text-lg text-gray-700 focus:ring-2 focus:ring-tropical-blue focus:outline-none transition-colors duration-200">
            <option value="name">Name (A-Z)</option>
            <option value="created_at">Newest</option>
        </select>
    </div>

    {{-- Destinations Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
        @forelse($destinations as $destination)
            <a href="{{ route('destination.show',  $destination->id) }}" class="relative bg-white rounded-3xl shadow-xl overflow-hidden cursor-pointer transform transition-all duration-300 hover:scale-[1.03] hover:shadow-2xl">
                <img src="{{ $destination->image_url ?? 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?q=80&w=2946&auto=format&fit=crop' }}"
                     onerror="this.onerror=null;this.src='https://placehold.co/600x400/D9F99D/FFFFFF?text=Destination+Image';"
                     class="w-full h-48 object-cover"
                     alt="{{ $destination->name }}">
                <div class="p-6">
                    <h3 class="text-2xl font-semibold mb-2 text-deep-ocean flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-sunset-orange" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="m11.54 22.351.07.074a.75.75 0 0 0 1.06 0l.068-.073a.874.874 0 0 1 .425-.262 5.25 5.25 0 0 0 2.222-.857c.725-.384 1.45-.968 2.15-1.551A9.752 9.752 0 0 0 21.75 12a.75.75 0 0 0-1.5 0 8.252 8.252 0 0 1-1.28 4.675 14.505 14.505 0 0 1-2.404-2.195c-.477-.481-.951-.979-1.423-1.47a.75.75 0 0 0-.219-.228 3.003 3.003 0 0 0-2.25-1.071c-.782 0-1.55.28-2.12.822l-.1.096a.75.75 0 0 0 .114 1.14l.099.098c.174.175.405.293.655.378a3 3 0 0 0 1.442.221c.64 0 1.25-.205 1.745-.58L10.5 14.28l.06-.056a.75.75 0 0 0-.91-.186c-.506.27-.995.532-1.468.784A.876.876 0 0 1 8.25 15a.75.75 0 0 0-.825 1.258l.196.11c.21.118.417.228.625.334a14.506 14.506 0 0 1-2.404 2.195A8.252 8.252 0 0 1 2.25 12a.75.75 0 0 0-1.5 0 9.752 9.752 0 0 0 1.95 5.213c.7.583 1.425 1.167 2.15 1.55A5.25 5.25 0 0 0 8.778 22c.207.069.28.163.425.262ZM6.375 7.5a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" clip-rule="evenodd" />
                        </svg>
                        <span>{{ $destination->name }}</span>
                    </h3>
                    <p class="text-gray-600 font-light">{{ Str::limit($destination->description, 100) }}</p>
                </div>
            </a>
        @empty
            <p class="text-gray-500 col-span-full text-center py-8">
                <span class="font-semibold text-lg">No destinations match your search criteria.</span>
                <br>
                Please try a different search!
            </p>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="mt-12">
        {{ $destinations->links() }}
    </div>
</div>
