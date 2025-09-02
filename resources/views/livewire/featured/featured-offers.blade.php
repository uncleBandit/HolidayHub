
<div>
<div class="sticky top-0 z-20 bg-white/70 backdrop-blur-md py-6 px-4 rounded-b-3xl shadow-lg border-b border-gray-100 flex items-center justify-between space-x-4 mb-8">
<div class="relative w-1/2 md:w-1/3">
<div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
<svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
<path d="M10 4a6 6 0 0 1 6 6c0 1.25-.33 2.4-.92 3.39l4.5 4.5a.75.75 0 0 1-1.06 1.06l-4.5-4.5A5.96 5.96 0 0 1 10 16a6 6 0 0 1 0-12zm0 1.5A4.5 4.5 0 0 0 5.5 10a4.5 4.5 0 0 0 9 0A4.5 4.5 0 0 0 10 5.5z" />
</svg>
</div>
<input
type="text"
wire:model.debounce.300ms="search"
placeholder="Search offers..."
class="w-full pl-10 pr-4 py-3 rounded-full border-none bg-gray-100 focus:ring-2 focus:ring-tropical-blue focus:outline-none transition-colors duration-200 text-lg"
>
</div>

    <div class="flex space-x-4">
        <select wire:model="sortBy" class="rounded-full border-none bg-gray-100 px-6 py-3 text-lg text-gray-700 focus:ring-2 focus:ring-tropical-blue focus:outline-none transition-colors duration-200">
            <option value="rating">Rating</option>
            <option value="newest">Newest</option>
        </select>
        <select wire:model="direction" class="rounded-full border-none bg-gray-100 px-6 py-3 text-lg text-gray-700 focus:ring-2 focus:ring-tropical-blue focus:outline-none transition-colors duration-200">
            <option value="desc">Descending</option>
            <option value="asc">Ascending</option>
        </select>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
    @forelse($offers as $offer)
        <a href="{{ route('offers.show', $offer) }}" class="relative bg-white rounded-3xl shadow-xl overflow-hidden cursor-pointer transform transition-all duration-300 hover:scale-[1.03] hover:shadow-2xl">
            <img src="{{ $offer->image_url ?? 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?q=80&w=2946&auto=format&fit=crop' }}"
                 onerror="this.onerror=null;this.src='https://placehold.co/600x400/D9F99D/FFFFFF?text=Offer+Image';"
                 class="w-full h-48 object-cover"
                 alt="{{ $offer->name }}">
            <div class="p-6">
                <h3 class="text-2xl font-semibold mb-2 text-deep-ocean flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-sunset-orange" viewBox="0 0 24 24" fill="currentColor">
                        <path fill-rule="evenodd" d="m11.54 22.351.07.074a.75.75 0 0 0 1.06 0l.068-.073a.874.874 0 0 1 .425-.262 5.25 5.25 0 0 0 2.222-.857c.725-.384 1.45-.968 2.15-1.551A9.752 9.752 0 0 0 21.75 12a.75.75 0 0 0-1.5 0 8.252 8.252 0 0 1-1.28 4.675 14.505 14.505 0 0 1-2.404-2.195c-.477-.481-.951-.979-1.423-1.47a.75.75 0 0 0-.219-.228 3.003 3.003 0 0 0-2.25-1.071c-.782 0-1.55.28-2.12.822l-.1.096a.75.75 0 0 0 .114 1.14l.099.098c.174.175.405.293.655.378a3 3 0 0 0 1.442.221c.64 0 1.25-.205 1.745-.58L10.5 14.28l.06-.056a.75.75 0 0 0-.91-.186c-.506.27-.995.532-1.468.784A.876.876 0 0 1 8.25 15a.75.75 0 0 0-.825 1.258l.196.11c.21.118.417.228.625.334a14.506 14.506 0 0 1-2.404 2.195A8.252 8.252 0 0 1 2.25 12a.75.75 0 0 0-1.5 0 9.752 9.752 0 0 0 1.95 5.213c.7.583 1.425 1.167 2.15 1.55A5.25 5.25 0 0 0 8.778 22c.207.069.28.163.425.262ZM6.375 7.5a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ $offer->name }}</span>
                </h3>
                <p class="text-gray-600 font-light">{{ Str::limit($offer->description, 100) }}</p>
            </div>
        </div>
    @empty
        <p class="text-gray-500 col-span-full text-center py-8">
            <span class="font-semibold text-lg">No offers match your search criteria.</span>
            <br>
            Please try a different search!
        </p>
    @endforelse
</div>

<div class="mt-12">
    {{ $offers->links() }}
</div>

</div>
