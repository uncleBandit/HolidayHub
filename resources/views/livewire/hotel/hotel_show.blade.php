<div>
<div class="bg-gray-50 font-inter text-gray-800 antialiased">
    <!-- Tailwind CSS CDN for styling -->

    <div class="container mx-auto px-4 lg:px-8 py-12 space-y-12">

        <!-- Hero Image & Gallery -->
        <div x-data="{ open: false, activeImage: 0, images: @js($hotel->images->pluck('url')) }">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 rounded-3xl overflow-hidden shadow-2xl transform transition-transform duration-500 hover:scale-[1.005]">
                <!-- Main Hero Image -->
                <div @click="open = true; activeImage = 0" class="col-span-1 md:col-span-2 lg:col-span-3 cursor-pointer relative h-96 lg:h-[550px]">
                    <img src="{{ $hotel->hero_image_url }}" alt="{{ $hotel->name }} Hero Image" class="w-full h-full object-cover transition-opacity duration-300 ease-in-out hover:opacity-90"/>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
                    <div class="absolute bottom-6 left-6 text-white z-10">
                        <h1 class="text-4xl lg:text-5xl font-extrabold">{{ $hotel->name }}</h1>
                        <p class="text-xl font-light opacity-80">{{ $hotel->location }}</p>
                    </div>
                </div>

                <!-- Small Image Gallery -->
                <div class="hidden lg:grid grid-cols-1 gap-4 p-2 bg-white">
                    @foreach($hotel->images->take(2) as $key => $image)
                        <img src="{{ $image->url }}" class="w-full h-full object-cover rounded-xl cursor-pointer transition-transform duration-300 hover:scale-105" @click="open = true; activeImage = {{ $key }}" />
                    @endforeach
                    <button @click="open = true" class="w-full h-full bg-gray-100 rounded-xl flex items-center justify-center text-gray-500 font-semibold transition-colors duration-300 hover:bg-gray-200">
                        <span class="text-center">+{{ $hotel->images->count() - 2 }}<br>Photos</span>
                    </button>
                </div>
            </div>

            <!-- Full-screen gallery modal -->
            <template x-if="open">
                <div class="fixed inset-0 bg-black bg-opacity-95 z-[100] flex items-center justify-center p-4">
                    <div class="relative w-full max-w-7xl h-full max-h-[90vh]">
                        <button @click="open = false" aria-label="Close" class="absolute top-4 right-4 text-white text-3xl p-2 z-10 opacity-70 hover:opacity-100 transition-opacity rounded-full bg-gray-900/50 hover:bg-gray-900/80">
                             <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                        <img :src="images[activeImage]" class="w-full h-full object-contain" alt="Gallery Image" />
                        <button @click="activeImage = (activeImage > 0) ? activeImage - 1 : images.length - 1" aria-label="Previous Image" class="absolute left-4 top-1/2 -translate-y-1/2 text-white text-5xl opacity-70 hover:opacity-100 transition rounded-full p-2 bg-gray-900/50 hover:bg-gray-900/80">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                        </button>
                        <button @click="activeImage = (activeImage < images.length - 1) ? activeImage + 1 : 0" aria-label="Next Image" class="absolute right-4 top-1/2 -translate-y-1/2 text-white text-5xl opacity-70 hover:opacity-100 transition rounded-full p-2 bg-gray-900/50 hover:bg-gray-900/80">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </button>
                        <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 text-white bg-black bg-opacity-50 px-4 py-1 rounded-full text-sm">
                            <span x-text="activeImage + 1"></span> / <span x-text="images.length"></span>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Main Content Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">

            <!-- Left Column: Details, Amenities, Rooms, Reviews -->
            <div class="lg:col-span-2 space-y-12">
                <!-- Hotel Details & Ratings -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                    <div>
                        <h2 class="text-3xl lg:text-4xl font-extrabold text-gray-900">{{ $hotel->name }}</h2>
                        <p class="text-xl text-gray-500">{{ $hotel->location }}</p>
                    </div>
                    <div class="flex flex-col items-end mt-4 md:mt-0">
                        <div class="flex items-center space-x-2">
                            <x-star-rating :rating="$hotel->stars" />
                            <span class="text-xl font-bold text-gray-800">{{ number_format($hotel->avg_rating, 1) }}</span>
                        </div>
                        <p class="text-sm text-gray-400 mt-1">({{ $reviews->count() }} reviews)</p>
                    </div>
                </div>

                <hr class="border-gray-200">

                <!-- Amenities -->
                <div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-6">Signature Amenities</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach ($hotel->amenities as $amenity)
                            <div class="flex items-center space-x-4 p-4 rounded-xl bg-white border border-gray-200 shadow-sm transition-all duration-300 hover:bg-blue-50 hover:border-blue-300 hover:shadow-md">
                                <div class="p-2 rounded-full bg-blue-100 text-blue-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <span class="text-gray-800 font-medium text-lg">{{ $amenity->name }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <hr class="border-gray-200">

                <!-- Room Types -->
                <div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-6">Room Types</h3>
                    <p class="text-gray-600 mb-6">Select a room type to check availability and book.</p>
                    <div class="grid md:grid-cols-2 gap-6">
                        @foreach($hotel->roomTypes as $roomType)
                            <div wire:click="selectRoomType({{ $roomType->id }})" class="p-4 border-2 rounded-xl transition-all duration-300 transform hover:scale-[1.02] cursor-pointer shadow-sm {{ $selectedRoomType && $selectedRoomType->id === $roomType->id ? 'border-blue-500 ring-4 ring-blue-500/30' : 'border-gray-200' }}">
                                <img src="{{ $roomType->hero_image_url }}" alt="{{ $roomType->name }}" class="w-full h-48 object-cover rounded-lg mb-4" />
                                <div class="flex justify-between items-center mb-2">
                                    <h4 class="text-xl font-semibold text-gray-900">{{ $roomType->name }}</h4>
                                    <span class="text-lg font-bold text-blue-600">${{ number_format($roomType->price_per_night, 2) }}</span>
                                </div>
                                <p class="text-gray-500 text-sm mb-4">{{ $roomType->guests }} guests · {{ $roomType->beds }} beds</p>
                                <p class="text-sm text-gray-600 line-clamp-3">{{ $roomType->description }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Right Column: Dynamic Calendar & Booking Form (Sticky) -->
            <div class="lg:col-span-1">
                <div class="sticky top-8 bg-white p-6 rounded-3xl shadow-xl border border-gray-100">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Check Availability</h3>
                    @if($selectedRoomType)
                        <!-- Loading State for the Calendar -->
                        <div wire:loading.flex wire:target="selectRoomType" class="flex items-center justify-center p-8">
                            <svg class="animate-spin -ml-1 mr-3 h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-lg text-gray-500">Loading calendar...</span>
                        </div>
                        <!-- Livewire Component -->
                        <div wire:loading.remove wire:target="selectRoomType">
                            @livewire('calendar.calendar-component', ['bookable' => $selectedRoomType])

                        </div>


                    @else
                        <div class="p-6 text-center">
                            <p class="text-gray-500 text-lg">Select a room type to see the availability calendar.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <hr class="border-gray-200">

        <!-- Guest Reviews -->
        <div wire:init="loadReviews" class="mt-8">
            <h3 class="text-2xl font-bold text-gray-900 mb-6">Guest Reviews</h3>
            <div class="space-y-6">
                @forelse($reviews as $review)
                    <div class="border-l-4 border-blue-500 p-6 rounded-lg shadow-sm bg-gray-50">
                        <p class="text-gray-700 italic text-lg leading-relaxed">“{{ $review->content }}”</p>
                        <div class="flex items-center justify-between mt-4">
                            <p class="text-sm font-semibold text-gray-600">- {{ $review->guest->first_name ?? 'Anonymous' }}</p>
                            <x-star-rating :rating="$review->rating" />
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 italic text-center">No reviews yet. Be the first to leave one!</p>
                @endforelse
            </div>
        </div>

    </div>
</div>
<livewire:forms.booking-form />

</div>
