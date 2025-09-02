<x-layouts.app.header>
    <div class="max-w-7xl mx-auto px-4 py-8">

    {{-- Search & Filters --}}
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-8">
        <div class="flex-1">
            <form method="GET" action="{{ route('hotels.index') }}" class="grid md:grid-cols-4 gap-4">

                {{-- Location Search --}}
                <div>
                    <label for="location" class="block text-sm font-medium text-gray-700">Location</label>
                    <input type="text" name="location" id="location" value="{{ request('location') }}"
                        placeholder="City, region, landmark..."
                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                {{-- Date Pickers --}}
                <div>
                    <label for="check_in" class="block text-sm font-medium text-gray-700">Check In</label>
                    <input type="date" name="check_in" id="check_in" value="{{ request('check_in') }}"
                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label for="check_out" class="block text-sm font-medium text-gray-700">Check Out</label>
                    <input type="date" name="check_out" id="check_out" value="{{ request('check_out') }}"
                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                {{-- Guests --}}
                <div>
                    <label for="guests" class="block text-sm font-medium text-gray-700">Guests</label>
                    <input type="number" name="guests" id="guests" min="1" value="{{ request('guests', 1) }}"
                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                {{-- Price Range --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Price Range</label>
                    <div class="flex gap-2">
                        <input type="number" name="min_price" value="{{ request('min_price') }}" placeholder="Min"
                            class="mt-1 block w-1/2 rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <input type="number" name="max_price" value="{{ request('max_price') }}" placeholder="Max"
                            class="mt-1 block w-1/2 rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>

                {{-- Rating --}}
                <div>
                    <label for="rating" class="block text-sm font-medium text-gray-700">Min Rating</label>
                    <select name="rating" id="rating"
                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Any</option>
                        @for($i = 5; $i >= 1; $i--)
                            <option value="{{ $i }}" {{ request('rating') == $i ? 'selected' : '' }}>
                                {{ $i }}+ Stars
                            </option>
                        @endfor
                    </select>
                </div>

                {{-- Amenities --}}
                <div class="md:col-span-2">
                    <label for="amenities" class="block text-sm font-medium text-gray-700">Amenities</label>
                    <input type="text" name="amenities" id="amenities" value="{{ request('amenities') }}"
                        placeholder="e.g. WiFi, Pool"
                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                {{-- Submit --}}
                <div class="md:col-span-4 flex justify-end">
                    <button type="submit"
                        class="px-6 py-2 bg-indigo-600 text-white rounded-xl shadow hover:bg-indigo-700 transition">
                        Search
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Hotel Listings --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($hotels as $hotel)
            <div class="bg-white rounded-2xl shadow hover:shadow-lg transition overflow-hidden">
                <a href="{{ route('hotels.show', $hotel) }}">
                    {{-- Image --}}
                    <div class="relative">
                        <img src="{{ $hotel->image ? asset('storage/'.$hotel->image) : 'https://via.placeholder.com/400x250' }}"
                             alt="{{ $hotel->name }}"
                             class="w-full h-48 object-cover">
                        <div class="absolute top-2 right-2 bg-white/90 px-2 py-1 text-xs rounded-lg font-medium">
                            ⭐ {{ number_format($hotel->rating, 1) }}
                        </div>
                    </div>

                    {{-- Info --}}
                    <div class="p-4">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $hotel->name }}</h3>
                        <p class="text-sm text-gray-500">{{ $hotel->location }}</p>

                        <p class="mt-2 text-sm text-gray-600 line-clamp-2">
                            {{ $hotel->description }}
                        </p>

                        {{-- Price --}}
                        <div class="mt-4 flex items-center justify-between">
                            <p class="text-indigo-600 font-bold">
                                {{ number_format($hotel->price_per_night, 2) }} {{ $hotel->currency ?? 'USD' }} / night
                            </p>
                            <span class="text-xs text-gray-500">per room</span>
                        </div>
                    </div>
                </a>
            </div>
        @empty
            <p class="col-span-3 text-center text-gray-500">No hotels found. Try adjusting filters.</p>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="mt-8">
        {{ $hotels->withQueryString()->links() }}
    </div>
</div>
</x-layouts.app.header>
