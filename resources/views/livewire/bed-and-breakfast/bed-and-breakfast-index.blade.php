<div>
{{-- resources/views/livewire/bed-and-breakfast/bed-and-breakfast-index.blade.php --}}
<div class="flex flex-col lg:flex-row gap-8">

    {{-- 🔎 Filters Sidebar --}}
    <aside class="w-full lg:w-1/4 bg-white shadow-md rounded-2xl p-6 space-y-6">
        <h2 class="text-xl font-bold text-gray-800">Filters</h2>

        {{-- Search --}}
        <div>
            <label class="text-sm font-medium text-gray-600">Search</label>
            <input type="text" wire:model.debounce.500ms="search"
                   class="mt-1 w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                   placeholder="Name, city, or country...">
        </div>

        {{-- City --}}
        <div>
            <label class="text-sm font-medium text-gray-600">City</label>
            <input type="text" wire:model.debounce.500ms="city"
                   class="mt-1 w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                   placeholder="Enter city">
        </div>

        {{-- Price Range --}}
        <div class="flex gap-4">
            <div class="flex-1">
                <label class="text-sm font-medium text-gray-600">Min Price</label>
                <input type="number" wire:model="minPrice"
                       class="mt-1 w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                       placeholder="0">
            </div>
            <div class="flex-1">
                <label class="text-sm font-medium text-gray-600">Max Price</label>
                <input type="number" wire:model="maxPrice"
                       class="mt-1 w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                       placeholder="1000">
            </div>
        </div>

        {{-- Amenities --}}
        {{-- Amenities Filter --}}
            <div>
                <label class="text-sm font-medium text-gray-600">Amenities</label>
                <div class="flex flex-wrap gap-2 mt-2">
                    @foreach($amenities as $amenity)
                        <label class="flex items-center gap-1 text-sm">
                            <input type="checkbox"
                                wire:model="selectedAmenities"
                                value="{{ $amenity->id }}"
                                class="rounded text-indigo-600 focus:ring-indigo-500">
                            {{ $amenity->name }}
                        </label>
                    @endforeach
                </div>
            </div>



        {{-- Featured --}}
        <div class="flex items-center space-x-2">
            <input type="checkbox" wire:model="onlyFeatured"
                   class="rounded text-indigo-600 focus:ring-indigo-500">
            <span class="text-sm text-gray-700">Only Featured</span>
        </div>
    </aside>

    {{-- 🏡 Results Section --}}
    <section class="flex-1">
        {{-- Header --}}
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
            <h2 class="text-2xl font-bold text-gray-800">Bed & Breakfasts</h2>

            {{-- Sorting --}}
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-600">Sort by:</label>
                <select wire:model="sortField"
                        class="rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    <option value="name">Name</option>
                    <option value="price">Price</option>
                    <option value="rating">Rating</option>
                    <option value="newest">Newest</option>
                </select>

                <button wire:click="sortBy('{{ $sortField }}')"
                        class="p-2 rounded-xl bg-gray-100 hover:bg-gray-200 transition">
                    @if($sortDirection === 'asc')
                        ⬆️
                    @else
                        ⬇️
                    @endif
                </button>
            </div>
        </div>

        {{-- Grid of B&Bs --}}
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($bnbList as $bnb)
                <div class="bg-white rounded-2xl shadow-md overflow-hidden hover:shadow-lg transition transform hover:-translate-y-1">
                    {{-- Image --}}
                    <div class="relative">
                        <img src="https://placehold.co/600x400" alt="{{ $bnb->name }}"
                             class="w-full h-48 object-cover">
                        @if($bnb->is_featured)
                            <span class="absolute top-2 left-2 bg-indigo-600 text-white text-xs font-semibold px-3 py-1 rounded-full">Featured</span>
                        @endif
                    </div>

                    {{-- Content --}}
                    <div class="p-4 space-y-3">
                        <h3 class="text-lg font-bold text-gray-800 truncate">{{ $bnb->name }}</h3>
                        <p class="text-sm text-gray-600 truncate">{{ $bnb->city }}, {{ $bnb->country }}</p>

                        {{-- Price & Rating --}}
                        <div class="flex items-center justify-between">
                            <span class="text-indigo-600 font-bold">${{ $bnb->price_per_night }}/night</span>
                            <span class="flex items-center text-sm text-yellow-500">
                                ⭐ {{ number_format($bnb->reviews_avg_rating, 1) ?? 'N/A' }}
                                <span class="ml-1 text-gray-500">({{ $bnb->reviews_count }})</span>
                            </span>
                        </div>

                        {{-- Amenities (preview only 3) --}}
                        <div class="flex flex-wrap gap-1 text-xs text-gray-600">
                            @foreach($bnb->amenities->take(3) as $amenity)
                                <span class="px-2 py-1 bg-gray-100 rounded-full">{{ $amenity->name }}</span>
                            @endforeach
                            @if($bnb->amenities->count() > 3)
                                <span class="text-gray-400">+{{ $bnb->amenities->count() - 3 }} more</span>
                            @endif
                        </div>

                        {{-- Action --}}
                        <div class="pt-2">
                            <a href="{{ route('bedandbreakfast.show', $bnb->slug) }}"
                               class="block text-center bg-indigo-600 text-white py-2 px-4 rounded-xl font-medium hover:bg-indigo-700 transition">
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center text-gray-600">
                    No Bed & Breakfasts found. Try adjusting your filters.
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        <div class="mt-8">
            {{ $bnbList->links() }}
        </div>
    </section>
</div>


</div>
