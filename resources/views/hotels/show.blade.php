<x-layouts.app.header>
    <div class="max-w-7xl mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">{{ $hotel->name }}</h1>
            <p class="text-gray-600 mt-1">{{ $hotel->location }}</p>
            <div class="flex items-center space-x-1 mt-2">
                @for ($i = 1; $i <= 5; $i++)
                    <svg class="w-5 h-5 {{ $i <= $hotel->rating ? 'text-yellow-400' : 'text-gray-300' }}"
                         fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 15l-5.878 3.09 1.122-6.545L.488 6.91l6.561-.955L10 0l2.951 5.955 6.561.955-4.756 4.635 1.122 6.545z"/>
                    </svg>
                @endfor
                <span class="ml-2 text-gray-600">({{ $hotel->reviews_count }} reviews)</span>
            </div>
        </div>
        <div class="mt-4 md:mt-0">
            <a href="#booking" class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl shadow hover:bg-blue-700 transition">
                Book Now
            </a>
        </div>
    </div>

    {{-- Image Gallery --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="md:col-span-2">
            <img src="{{ $hotel->cover_image }}" alt="{{ $hotel->name }}"
                 class="w-full h-96 object-cover rounded-2xl shadow-lg">
        </div>
        <div class="grid grid-cols-2 gap-2">
            @foreach ($hotel->gallery as $image)
                <img src="{{ $image }}" class="w-full h-44 object-cover rounded-xl shadow" alt="Gallery">
            @endforeach
        </div>
    </div>

    {{-- Hotel Description & Amenities --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
        <div class="md:col-span-2">
            <h2 class="text-2xl font-semibold mb-3">About this Hotel</h2>
            <p class="text-gray-700 leading-relaxed">{{ $hotel->description }}</p>

            <h3 class="text-xl font-semibold mt-6 mb-3">Amenities</h3>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                @foreach ($hotel->amenities as $amenity)
                    <div class="flex items-center space-x-2 text-gray-700">
                        <x-heroicon-o-check class="w-5 h-5 text-green-500"/>
                        <span>{{ $amenity }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Map --}}
        <div>
            <h3 class="text-xl font-semibold mb-3">Location</h3>
            <div id="map" class="w-full h-72 rounded-xl shadow"></div>
        </div>
    </div>

    {{-- Available Rooms --}}
    <div id="booking" class="mb-12">
        <h2 class="text-2xl font-semibold mb-6">Available Rooms</h2>
        <div class="grid grid-cols-1 gap-6">
            @foreach ($rooms as $room)
                <div class="p-5 bg-white rounded-2xl shadow hover:shadow-lg transition">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold">{{ $room->name }}</h3>
                            <p class="text-gray-600">{{ $room->description }}</p>
                            <div class="flex space-x-4 mt-2 text-sm text-gray-500">
                                <span>Max Guests: {{ $room->max_guests }}</span>
                                <span>Bed: {{ $room->bed_type }}</span>
                            </div>
                        </div>
                        <div class="mt-4 md:mt-0 text-right">
                            <p class="text-xl font-bold text-blue-600">${{ $room->price }}/night</p>
                            <a href="{{ route('bookings.create', ['room' => $room->id]) }}"
                               class="mt-2 inline-block px-5 py-2 bg-blue-600 text-white font-semibold rounded-lg shadow hover:bg-blue-700">
                               Reserve
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Guest Reviews --}}
    <div class="mb-12">
        <h2 class="text-2xl font-semibold mb-6">Guest Reviews</h2>
        <div class="space-y-6">
            @foreach ($reviews as $review)
                <div class="p-5 bg-white rounded-xl shadow">
                    <div class="flex items-center space-x-3">
                        <img src="{{ $review->user->avatar }}" class="w-12 h-12 rounded-full object-cover" alt="{{ $review->user->name }}">
                        <div>
                            <p class="font-semibold">{{ $review->user->name }}</p>
                            <p class="text-sm text-gray-500">{{ $review->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                    <div class="flex items-center mt-2">
                        @for ($i = 1; $i <= 5; $i++)
                            <svg class="w-4 h-4 {{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-300' }}"
                                 fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 15l-5.878 3.09 1.122-6.545L.488 6.91l6.561-.955L10 0l2.951 5.955 6.561.955-4.756 4.635 1.122 6.545z"/>
                            </svg>
                        @endfor
                    </div>
                    <p class="mt-3 text-gray-700">{{ $review->comment }}</p>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Leaflet.js Map --}}
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const map = L.map('map').setView([{{ $hotel->latitude }}, {{ $hotel->longitude }}], 14);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        L.marker([{{ $hotel->latitude }}, {{ $hotel->longitude }}]).addTo(map)
            .bindPopup("{{ $hotel->name }}").openPopup();
    });
</script>
</x-layouts.app.header>
