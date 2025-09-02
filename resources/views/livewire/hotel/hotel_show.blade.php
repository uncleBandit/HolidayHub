<div>
<div class="container mx-auto px-4 py-8 space-y-8">

    <div x-data="{ open: false, activeImage: 0, images: @js($hotel->images->pluck('url')) }">
        <div class="relative group">
            <img src="{{ $hotel->hero_image_url }}" @click="open = true"
                 class="w-full h-96 object-cover rounded-2xl shadow-lg transition-transform duration-300 group-hover:scale-105 cursor-pointer" />

            <x-wishlist-button :is-wishlisted="$isWishlisted" />
        </div>

        <template x-if="open">
            <div class="fixed inset-0 bg-black bg-opacity-90 z-[100] flex items-center justify-center p-4">
                <div class="relative w-full max-w-5xl h-full max-h-[80vh]">
                    <button @click="open = false"
                            class="absolute top-4 right-4 text-white text-4xl p-2 z-10 opacity-70 hover:opacity-100 transition-opacity">&times;</button>

                    <img :src="images[activeImage]" class="w-full h-full object-contain rounded-lg shadow-2xl" />

                    <button @click="activeImage = (activeImage > 0) ? activeImage - 1 : images.length - 1"
                            class="absolute left-4 top-1/2 -translate-y-1/2 text-white text-5xl opacity-70 hover:opacity-100 transition">&larr;</button>

                    <button @click="activeImage = (activeImage < images.length - 1) ? activeImage + 1 : 0"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-white text-5xl opacity-70 hover:opacity-100 transition">&rarr;</button>

                    <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 text-white bg-black bg-opacity-50 px-4 py-1 rounded-full text-sm">
                        <span x-text="activeImage + 1"></span> / <span x-text="images.length"></span>
                    </div>
                </div>
            </div>
        </template>
    </div>



    <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
        <div>
            <h1 class="text-4xl font-extrabold text-gray-900">{{ $hotel->name }}</h1>
            <p class="text-gray-500 text-lg">{{ $hotel->location }}</p>
        </div>
        <div class="flex items-center space-x-2 mt-4 md:mt-0">
            <x-star-rating :rating="$hotel->stars" />
            <span class="text-xl font-bold text-gray-800">{{ number_format($hotel->average_rating, 1) }}</span>
            <p class="text-sm text-gray-400">({{ $reviews->count() }} reviews)</p>
        </div>
    </div>



    <div>
        <h2 class="text-2xl font-bold mb-4">Hotel Amenities</h2>
        <div x-data="{ openCategory: null }">
            @php
                $amenitiesByCategory = $hotel->hotelAmenities->groupBy('category');
            @endphp

            @foreach($amenitiesByCategory as $category => $amenities)
                <x-amenity-accordion :category="$category" :amenities="$amenities" />
            @endforeach
        </div>
    </div>



    <hr class="border-gray-200">
    <div x-data="{ showAvailability: false }">
        <button @click="showAvailability = !showAvailability"
                class="w-full px-6 py-3 bg-blue-600 text-white font-bold rounded-xl shadow-lg transition-all duration-300 hover:bg-blue-700">
            <span x-show="!showAvailability">Check Availability</span>
            <span x-show="showAvailability">Hide Calendar</span>
        </button>

        <div x-show="showAvailability"
             x-transition:enter="transition ease-out duration-500"
             x-transition:enter-start="opacity-0 transform -translate-y-4"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="opacity-100 transform translate-y-0"
             x-transition:leave-end="opacity-0 transform -translate-y-4">

            <div wire:ignore class="mt-8">
                <h2 class="text-2xl font-bold mb-4">Availability Calendar</h2>
                @livewire('hotel.availability-calendar', ['hotelId' => $hotel->id])
            </div>

            <hr class="my-8 border-gray-200">

            <div>
                <h2 class="text-2xl font-bold mb-4">Room Types</h2>
                <div class="grid md:grid-cols-2 gap-6">
                    @foreach($hotel->rooms as $room)
                        <x-room-card :room="$room" />
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    

    <hr class="border-gray-200">
    <div wire:init="loadReviews" class="mt-8">
        <h2 class="text-2xl font-bold mb-4">Guest Reviews</h2>
        <div class="space-y-6">
            @forelse($reviews as $review)
                <div class="border-l-4 border-blue-500 p-4 rounded-lg shadow-sm bg-gray-50">
                    <p class="text-gray-700 italic text-lg leading-relaxed">“{{ $review->content }}”</p>
                    <div class="flex items-center justify-between mt-4">
                        <p class="text-sm font-semibold text-gray-600">- {{ $review->guest->name }}</p>
                        <x-star-rating :rating="$review->rating" />
                    </div>
                </div>
            @empty
                <p class="text-gray-500 italic">No reviews yet. Be the first to leave one!</p>
            @endforelse
        </div>
    </div>

</div>
</div>
