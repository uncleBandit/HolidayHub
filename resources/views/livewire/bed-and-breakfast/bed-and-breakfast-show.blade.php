<div>
<div>
    <div class="bg-gray-100 min-h-screen font-sans antialiased">
        {{-- Hero Section with Parallax Effect --}}
        <div class="relative w-full h-[80vh] overflow-hidden">
            {{-- Background Image with subtle parallax motion --}}
            <div class="absolute inset-0 bg-cover bg-center bg-fixed"
                 style="background-image: url('{{ $bnb->cover_image ?? ($bnb->gallery[0] ?? 'https://via.placeholder.com/1920x1080') }}');">
                <div class="absolute inset-0 bg-black opacity-40"></div>
            </div>

            {{-- Hero Content --}}
            <div class="relative z-10 flex flex-col items-center justify-center h-full text-white text-center p-6">
                <h1 class="text-6xl md:text-8xl font-serif font-extrabold leading-tight tracking-tight drop-shadow-lg animate-fade-in-down">
                    {{ $bnb->name }}
                </h1>
                <p class="mt-4 text-xl md:text-2xl font-light uppercase tracking-widest text-shadow-md animate-fade-in-up">
                    A getaway in {{ $bnb->city }}, {{ $bnb->country }}
                </p>
                <div class="mt-8 flex items-center justify-center space-x-8 text-xl animate-fade-in-up delay-200">
                    <span class="font-bold text-3xl">${{ number_format($bnb->price_per_night, 2) }}</span>
                    <span class="text-gray-300">/ night</span>
                    <div class="text-yellow-400 flex items-center space-x-2">
                        <i class="fas fa-star text-2xl"></i>
                        <span class="text-2xl font-semibold">{{ number_format($bnb->avg_rating, 1) ?? 'N/A' }}</span>
                    </div>
                    <button wire:click="toggleWishlist"
                            class="p-3 rounded-full bg-white bg-opacity-20 backdrop-filter backdrop-blur-sm transition-transform duration-300 hover:scale-125 focus:outline-none">
                        <i class="fas fa-heart text-white {{ $isWishlisted ? 'text-red-500' : 'text-gray-300' }} text-2xl"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- Main Content & Booking Form --}}
        <div class="container mx-auto px-4 py-16 -mt-24 relative z-20">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
                {{-- Left Column: Gallery, Description, Amenities, Reviews --}}
                <div class="lg:col-span-2 space-y-12">
                    {{-- Gallery Section --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 rounded-3xl overflow-hidden shadow-2xl bg-white p-4">
                        @if($bnb->galleryUrl && count($bnb->galleryUrl) > 1)
                            <div class="col-span-1 md:col-span-2">
                                <img src="{{ $bnb->gallery[0] }}"
                                     alt="{{ $bnb->name }} main view"
                                     class="w-full h-[60vh] object-cover rounded-2xl transition-transform duration-500 hover:scale-105 cursor-pointer">
                            </div>
                            @foreach(array_slice($bnb->gallery, 1, 4) as $image)
                                <img src="{{ $image }}"
                                     alt="{{ $bnb->name }} gallery image"
                                     class="w-full h-64 object-cover rounded-2xl transition-transform duration-500 hover:scale-105 cursor-pointer">
                            @endforeach
                        @else
                            <img src="{{ $bnb->cover_image ?? 'https://via.placeholder.com/1600x900' }}"
                                 alt="{{ $bnb->name }}"
                                 class="w-full h-[70vh] object-cover rounded-3xl transition-transform duration-500 hover:scale-105 cursor-pointer">
                        @endif
                    </div>

                    {{-- Key Details & Description --}}
                    <div class="bg-white rounded-3xl p-10 shadow-xl space-y-8">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between border-b pb-6">
                            <h2 class="text-3xl font-bold text-gray-800">
                                The {{ $bnb->name }} Experience
                            </h2>
                            <div class="mt-4 md:mt-0 text-gray-600 flex items-center space-x-4">
                                <span class="flex items-center space-x-2">
                                    <i class="fas fa-bed"></i>
                                    <span>{{ $bnb->bedrooms ?? 2 }} Bedrooms</span>
                                </span>
                                <span class="flex items-center space-x-2">
                                    <i class="fas fa-bath"></i>
                                    <span>{{ $bnb->bathrooms ?? 1 }} Baths</span>
                                </span>
                                <span class="flex items-center space-x-2">
                                    <i class="fas fa-users"></i>
                                    <span>Up to {{ $bnb->max_guests }} Guests</span>
                                </span>
                            </div>
                        </div>
                        <p class="text-lg text-gray-700 leading-relaxed">{{ $bnb->description }}</p>

                        @if($bnb->amenities && $bnb->amenities->isNotEmpty())
                            <hr class="my-6 border-gray-200">
                            <h3 class="text-2xl font-semibold text-gray-800">Key Features & Amenities</h3>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-x-6 gap-y-4">
                                @foreach($bnb->amenities as $amenity)
                                    <div class="flex items-center space-x-3 text-gray-700">
                                        <i class="fas fa-check-circle text-green-500 text-xl"></i>
                                        <span class="text-lg">{{ $amenity->name }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Reviews Section --}}
                    <div class="bg-white rounded-3xl p-10 shadow-xl space-y-8">
                        <h2 class="text-3xl font-bold text-gray-800">
                            Guest Reviews ({{ $bnb->reviews_count }})
                        </h2>
                        @forelse($reviews as $review)
                            <div class="p-6 bg-gray-50 rounded-2xl shadow-sm border-l-4 border-indigo-500">
                                <div class="flex items-center justify-between">
                                    <p class="font-bold text-lg text-gray-900">{{ $review->user->name }}</p>
                                    <div class="text-yellow-500 flex items-center space-x-1 text-lg">
                                        @for($i = 0; $i < 5; $i++)
                                            <i class="fas fa-star {{ $i < $review->rating ? '' : 'text-gray-300' }}"></i>
                                        @endfor
                                    </div>
                                </div>
                                <p class="mt-2 text-sm text-gray-500">{{ $review->created_at->diffForHumans() }}</p>
                                <p class="mt-4 text-gray-700 leading-relaxed">{{ $review->content }}</p>
                            </div>
                        @empty
                            <div class="text-center p-8 text-gray-500">
                                <p>No reviews yet. Be the first to share your experience! 📝</p>
                            </div>
                        @endforelse
                        @if($hasMoreReviews)
                            <div class="flex justify-center mt-6">
                                <button wire:click="loadReviews" class="text-indigo-600 font-semibold hover:underline transition-colors duration-300">
                                    Load More Reviews
                                </button>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Right Column: Sticky Booking Sidebar --}}
                <div class="lg:col-span-1">
                    <div class="sticky top-8 bg-white p-6 rounded-3xl shadow-xl border border-gray-100 space-y-6">
                        <h3 class="text-2xl font-bold text-gray-900 text-center">Check Availability</h3>

                        {{-- Calendar availability (re-usable like in Hotel) --}}
                        @if($bnb && $bnb->id)
                            <livewire:calendar.calendar-component :bookable="$bnb" />
                        @else
                            <p class="text-red-500 text-center mt-4">⚠️ Booking calendar unavailable.</p>
                        @endif


                        <hr class="border-gray-200">

                        {{-- Guests Input - This is the only booking state managed by the parent --}}
                        <div class="mt-4">
                            <label for="guests" class="block text-sm font-medium text-gray-700">Guests</label>
                            <input type="number" id="guests" wire:model.live="guests" min="1" max="{{ $bnb->max_guests }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('guests') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<livewire:forms.booking-form />

</div>
