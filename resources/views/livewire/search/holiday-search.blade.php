<div>
<div class="relative w-full max-w-5xl mx-auto" x-data="{ open: @entangle('showResults') }" @click.away="open = false">
{{-- Main Search Bar --}}
<form action="{{ route('hotels.index') }}" method="GET">
<div class="grid md:grid-cols-4 lg:grid-cols-5 gap-4 bg-white/90 dark:bg-zinc-900/90 backdrop-blur-md rounded-3xl p-4 shadow-xl text-neutral-800 animate-slide-up-search">
<input
type="text"
name="location"
placeholder="Destination or Hotel"
class="md:col-span-2 rounded-full px-5 py-3 focus:ring-2 focus:ring-indigo-500 transition"
wire:model.live.debounce.300ms="searchQuery"
@focus="open = true"
>
<input type="date" name="check_in" class="rounded-full px-5 py-3 focus:ring-2 focus:ring-indigo-500 transition">
<input type="date" name="check_out" class="rounded-full px-5 py-3 focus:ring-2 focus:ring-indigo-500 transition">
<button type="submit" class="flex items-center justify-center bg-gradient-to-r from-indigo-600 to-indigo-700 text-white font-semibold py-3 rounded-full hover:scale-105 transition-transform shadow-lg">
<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.2-5.2M5.2 5.2a7.5 7.5 0 1010.6 10.6 7.5 7.5 0 00-10.6-10.6z"/>
</svg>
Search
</button>
</div>
</form>

{{-- Search Suggestions Dropdown --}}
@if ($showResults && count($results) > 0)
    <div class="absolute mt-2 w-full max-w-5xl z-50 bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden max-h-60 overflow-y-auto">
        @foreach ($results as $destination)
            <a href="{{ route('destination.show',  $destination->id) }}" class="flex items-center gap-4 p-4 hover:bg-indigo-50 dark:hover:bg-zinc-700 transition">
                <img src="{{ $destination->thumbnail }}" alt="{{ $destination->name }}" class="w-12 h-12 rounded-lg object-cover">
                <div class="flex flex-col">
                    <span class="font-semibold text-zinc-900 dark:text-white">{{ $destination->name }}</span>
                    <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $destination->city }}, {{ $destination->country }}</span>
                </div>
            </a>
        @endforeach
    </div>
@elseif (strlen($searchQuery) > 2 && $showResults)
    <div class="absolute mt-2 w-full max-w-5xl z-50 bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-zinc-200 dark:border-zinc-700">
        <p class="p-4 text-center text-zinc-500 dark:text-zinc-400">No results found.</p>
    </div>
@endif

</div>
</div>
