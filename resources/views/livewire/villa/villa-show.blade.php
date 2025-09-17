<div>
<div class="bg-gray-50 min-h-screen font-sans antialiased">
    <div class="relative w-full h-[60vh] md:h-[80vh] overflow-hidden rounded-b-3xl shadow-2xl">
        <img src="{{ $villa->main_image }}" alt="{{ $villa->name }}"
             class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent"></div>

        <div class="absolute inset-x-0 bottom-0 p-8 md:p-12 text-white">
            <h1 class="text-4xl md:text-6xl font-extrabold leading-tight drop-shadow-lg">
                {{ $villa->name }}
            </h1>
            <p class="text-xl md:text-2xl mt-2 font-medium drop-shadow-sm">
                {{ $villa->city }}, {{ $villa->country }}
            </p>
            <div class="flex items-center mt-4 text-sm md:text-base font-semibold">
                <i class="fas fa-star text-yellow-400 mr-2"></i>
                <span>{{ number_format($villa->average_rating, 1) }} ({{ $villa->reviews->count() }} reviews)</span>
            </div>
        </div>

        <div class="absolute top-8 right-8 flex items-center gap-4">
            <button wire:click="toggleWishlist"
                    class="p-3 rounded-full bg-white/20 backdrop-blur-sm text-white hover:bg-white/30 transition shadow-lg">
                <i class="{{ $isWishlisted ? 'fas text-rose-500' : 'far text-white' }} fa-heart text-xl"></i>
            </button>
            <button wire:click="showGallery(0)"
                    class="px-6 py-3 bg-white/20 backdrop-blur-sm text-white rounded-full font-semibold hover:bg-white/30 transition shadow-lg hidden md:block">
                <i class="fas fa-camera mr-2"></i>
                View All Photos
            </button>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-6 py-16 grid grid-cols-1 lg:grid-cols-3 gap-16">

        <div class="lg:col-span-2 space-y-12">

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-6 text-center">
                <div class="flex flex-col items-center p-4 bg-white rounded-xl shadow-sm border border-gray-100">
                    <i class="fas fa-bed text-2xl text-indigo-600 mb-2"></i>
                    <p class="font-semibold text-sm text-gray-700">Bedrooms</p>
                    <p class="text-lg font-bold text-gray-900 mt-1">{{ $villa->bedrooms }}</p>
                </div>
                <div class="flex flex-col items-center p-4 bg-white rounded-xl shadow-sm border border-gray-100">
                    <i class="fas fa-users text-2xl text-indigo-600 mb-2"></i>
                    <p class="font-semibold text-sm text-gray-700">Guests</p>
                    <p class="text-lg font-bold text-gray-900 mt-1">{{ $villa->max_guests }}</p>
                </div>
                <div class="flex flex-col items-center p-4 bg-white rounded-xl shadow-sm border border-gray-100">
                    <i class="fas fa-bath text-2xl text-indigo-600 mb-2"></i>
                    <p class="font-semibold text-sm text-gray-700">Bathrooms</p>
                    <p class="text-lg font-bold text-gray-900 mt-1">{{ $villa->bathrooms }}</p>
                </div>
                <div class="flex flex-col items-center p-4 bg-white rounded-xl shadow-sm border border-gray-100">
                    <i class="fas fa-ruler-combined text-2xl text-indigo-600 mb-2"></i>
                    <p class="font-semibold text-sm text-gray-700">Size</p>
                    <p class="text-lg font-bold text-gray-900 mt-1">1,200 sqft</p>
                </div>
            </div>

            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <button wire:click="switchTab('overview')"
                            class="{{ $activeTab === 'overview' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                        Overview
                    </button>
                    <button wire:click="switchTab('amenities')"
                            class="{{ $activeTab === 'amenities' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                        Amenities
                    </button>
                    <button wire:click="switchTab('reviews')"
                            class="{{ $activeTab === 'reviews' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                        Reviews ({{ $villa->reviews->count() }})
                    </button>
                </nav>
            </div>

            <div class="py-6">
                @if($activeTab === 'overview')
                    <h2 class="text-2xl font-bold mb-4 text-gray-800">About this Villa</h2>
                    <p class="text-gray-600 leading-relaxed text-lg">{{ $villa->description }}</p>
                @elseif($activeTab === 'amenities')
                    <h2 class="text-2xl font-bold mb-4 text-gray-800">What this place offers</h2>
                    <ul class="grid grid-cols-2 md:grid-cols-3 gap-6 text-gray-700">
                        @foreach($villa->amenities as $amenity)
                            <li class="flex items-center gap-3">
                                <i class="fas fa-check-circle text-green-500 text-xl"></i>
                                <span class="font-medium">{{ $amenity }}</span>
                            </li>
                        @endforeach
                    </ul>
                @elseif($activeTab === 'reviews')
                    <h2 class="text-2xl font-bold mb-6 text-gray-800">Guest Reviews</h2>
                    <div class="space-y-6">
                        @forelse($reviews as $review)
                            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                                <div class="flex items-center gap-4">
                                    <div class="flex-shrink-0 w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 font-bold text-xl border border-gray-300">
                                        {{ substr($review->user->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-lg text-gray-800">{{ $review->user->name }}</h4>
                                        <p class="text-sm text-gray-500">
                                            Reviewed on {{ $review->created_at->format('M d, Y') }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 text-yellow-500 mt-3">
                                    @for ($i = 0; $i < $review->rating; $i++)
                                        <i class="fas fa-star text-base"></i>
                                    @endfor
                                </div>
                                <p class="text-gray-700 mt-3 leading-relaxed">"{{ $review->comment }}"</p>
                            </div>
                        @empty
                            <p class="text-gray-500 text-center py-10">No reviews yet. Be the first to leave one!</p>
                        @endforelse
                    </div>
                @endif
            </div>

        </div>

        <div class="lg:col-span-1" id="booking-form">
            <div class="lg:sticky lg:top-16 bg-white p-8 rounded-3xl shadow-2xl border border-gray-100 space-y-6">
                <h3 class="text-2xl font-bold text-gray-800">
                    <span class="text-4xl font-extrabold">${{ number_format($villa->base_price, 0) }}</span> / night
                </h3>

                <form wire:submit.prevent="bookVilla" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="check-in" class="block text-sm font-medium text-gray-700 mb-1">Check-In</label>
                            <input type="date" wire:model.live="checkIn" id="check-in"
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label for="check-out" class="block text-sm font-medium text-gray-700 mb-1">Check-Out</label>
                            <input type="date" wire:model.live="checkOut" id="check-out"
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>

                    <div>
                        <label for="guests" class="block text-sm font-medium text-gray-700 mb-1">Guests</label>
                        <input type="number" min="1" wire:model.live="guests" id="guests"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    @if($availabilityMessage)
                        <div class="p-4 rounded-xl text-sm font-medium {{ $calculatedPrice ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ $availabilityMessage }}
                        </div>
                    @endif

                    @if($calculatedPrice)
                        <div class="flex items-center justify-between font-bold text-gray-800 text-lg border-t border-gray-100 pt-4">
                            <span>Total Price:</span>
                            <span>${{ number_format($calculatedPrice, 2) }}</span>
                        </div>
                    @endif

                    <button type="submit"
                            class="w-full py-4 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 transition duration-300 shadow-lg">
                        Reserve
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@if($isGalleryOpen)
    <div x-data="{ open: @entangle('isGalleryOpen') }" x-show="open"
         class="fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-95">
        <div class="relative w-full h-full flex items-center justify-center p-8">
            <button wire:click="closeGallery"
                    class="absolute top-8 right-8 text-white text-3xl z-50 hover:text-gray-300 transition">
                <i class="fas fa-times-circle"></i>
            </button>

            <div class="flex items-center space-x-4 w-full h-full">
                <button wire:click="previousImage" class="text-white text-5xl opacity-50 hover:opacity-100 transition">
                    <i class="fas fa-chevron-left"></i>
                </button>

                <div class="flex-grow flex items-center justify-center h-full">
                    <img src="{{ $villa->gallery[$activeImageId] ?? $villa->main_image }}"
                         class="max-w-full max-h-[80vh] object-contain rounded-xl shadow-2xl"
                         alt="Full-size gallery image">
                </div>

                <button wire:click="nextImage" class="text-white text-5xl opacity-50 hover:opacity-100 transition">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
@endif
</div>
