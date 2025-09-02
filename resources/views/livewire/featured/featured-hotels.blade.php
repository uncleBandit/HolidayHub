<div>
    <!-- Sticky Search & Filter Bar -->
    <div class="sticky top-0 z-20 bg-white/70 backdrop-blur-md py-6 px-4 rounded-b-3xl shadow-lg border-b border-gray-100 flex items-center justify-between space-x-4 mb-8">
        <div class="relative w-1/3">
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M10 4a6 6 0 0 1 6 6c0 1.25-.33 2.4-.92 3.39l4.5 4.5a.75.75 0 0 1-1.06 1.06l-4.5-4.5A5.96 5.96 0 0 1 10 16a6 6 0 0 1 0-12zm0 1.5A4.5 4.5 0 0 0 5.5 10a4.5 4.5 0 0 0 9 0A4.5 4.5 0 0 0 10 5.5z" />
                </svg>
            </div>
            <input
                type="text"
                wire:model.debounce.300ms="search"
                placeholder="Search hotels..."
                class="w-full pl-10 pr-4 py-3 rounded-full border-none bg-gray-100 focus:ring-2 focus:ring-tropical-blue focus:outline-none transition-colors duration-200 text-lg"
            >
        </div>

        <select wire:model="sortBy" class="rounded-full border-none bg-gray-100 px-6 py-3 text-lg text-gray-700 focus:ring-2 focus:ring-tropical-blue focus:outline-none transition-colors duration-200">
            <option value="rating">Top Rated</option>
            <option value="stars">Star Ranking</option>
            <option value="created_at">Newest</option>
        </select>
    </div>

    <!-- Hotel Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        @forelse($hotels as $hotel)
            <a href="{{ route('hotel.show', ['slug' => $hotel->slug]) }}"
                class="relative bg-white rounded-3xl shadow-xl overflow-hidden cursor-pointer transform transition-all duration-300 hover:scale-[1.03] hover:shadow-2xl">

                <!-- Discount Badge -->
                @if($hotel->discount_percentage > 0)
                    <div class="absolute top-4 left-4 bg-sunset-orange text-white text-xs font-bold px-4 py-1 rounded-full shadow-lg z-10">
                        {{ $hotel->discount_percentage }}% OFF
                    </div>
                @endif
                <img src="{{ $hotel->cover_image }}" onerror="this.onerror=null;this.src='https://placehold.co/600x400/D9F99D/FFFFFF?text=Hotel+Image';" class="w-full h-64 object-cover rounded-t-3xl">
                <div class="p-6">
                    <h3 class="text-2xl font-bold mb-1 text-deep-ocean">{{ $hotel->name }}</h3>
                    <p class="text-gray-600 mb-4">{{ $hotel->city }}, {{ $hotel->country }}</p>

                    <div class="flex items-center justify-between">
                        <!-- Rating Stars -->
                        <div class="flex text-sunset-orange">
                            @for($i = 0; $i < round($hotel->rating); $i++)
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10.788 3.212a.75.75 0 0 1 1.424 0l2.67 5.764a.75.75 0 0 0 .524.382l5.772.585a.75.75 0 0 1 .425 1.354l-4.32 3.82a.75.75 0 0 0-.256.634l1.29 5.617a.75.75 0 0 1-1.091.794L12 18.293l-4.757 2.815a.75.75 0 0 1-1.09-.794l1.29-5.617a.75.75 0 0 0-.257-.634l-4.32-3.82a.75.75 0 0 1 .425-1.354l5.772-.585a.75.75 0 0 0 .524-.382l2.67-5.764Z" clip-rule="evenodd" />
                                </svg>
                            @endfor
                        </div>

                        <!-- Star Ranking -->
                        <div class="flex items-center gap-1 text-gray-500 font-semibold">
                            @for($i = 0; $i < $hotel->stars; $i++)
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2a1.5 1.5 0 0 1 1.415 1.78l-.742 4.053 3.01 2.373a1.5 1.5 0 0 1 0 2.352l-3.01 2.373.742 4.053A1.5 1.5 0 0 1 12 22a1.5 1.5 0 0 1-1.415-1.78l.742-4.053-3.01-2.373a1.5 1.5 0 0 1 0-2.352l3.01-2.373-.742-4.053A1.5 1.5 0 0 1 12 2z" />
                                </svg>
                            @endfor
                        </div>
                    </div>
                </div>
            </a>
        @empty
            <p class="col-span-3 text-center text-gray-500 py-12">
                <span class="font-semibold text-lg">No hotels match your search criteria.</span>
                <br>
                Please try a different destination or a new search!
            </p>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-12">
        {{ $hotels->links() }}
    </div>
</div>
