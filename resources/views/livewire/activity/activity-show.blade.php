<div>
<div class="bg-gray-100 min-h-screen">
    {{-- Full-width image hero section with gradient overlay --}}
    <div class="relative w-full h-[50vh] sm:h-[60vh] lg:h-[70vh] xl:h-[80vh] overflow-hidden">
        <img src="{{ $activity->cover_image_url }}" alt="{{ $activity->title }}"
             class="absolute inset-0 w-full h-full object-cover">

        <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-gray-900/40 to-transparent"></div>

        {{-- Content overlay --}}
        <div class="absolute bottom-0 left-0 right-0 p-8 sm:p-12 text-white">
            <div class="max-w-7xl mx-auto">
                {{-- Title & Location --}}
                <h1 class="text-4xl sm:text-5xl font-extrabold leading-tight tracking-tight mb-2">
                    {{ $activity->title }}
                </h1>
                <p class="text-lg sm:text-xl font-medium text-gray-200 flex items-center">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                    </svg>
                    {{ $activity->location }}
                </p>

                {{-- Dynamic Wishlist Button --}}
                <button wire:click="toggleWishlist"
                    class="absolute top-4 right-4 bg-white/80 hover:bg-white rounded-full p-3 shadow-md transition-all duration-300 transform hover:scale-110">
                    {{-- @if($isWishlisted) --}}
                    <svg class="w-6 h-6 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" />
                    </svg>
                    {{-- @else --}}
                    <svg class="w-6 h-6 text-gray-600 hover:text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                    {{-- @endif --}}
                </button>
            </div>
        </div>
    </div>

    {{-- Main content area --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 -mt-16 sm:-mt-20 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            {{-- Left column: Description & Details --}}
            <div class="lg:col-span-2 space-y-12">
                {{-- Description Card --}}
                <div class="bg-white rounded-3xl shadow-xl p-8">
                    <h2 class="text-3xl font-bold text-gray-800 mb-4">About This Activity</h2>
                    <p class="text-gray-700 leading-relaxed text-lg">
                        {{ $activity->description }}
                    </p>

                    {{-- Key Features/Tags --}}
                    <div class="mt-6 flex flex-wrap gap-2">
                        <span class="px-4 py-1.5 rounded-full bg-indigo-50 text-indigo-700 text-sm font-medium">Adventure</span>
                        <span class="px-4 py-1.5 rounded-full bg-green-50 text-green-700 text-sm font-medium">Family Friendly</span>
                        <span class="px-4 py-1.5 rounded-full bg-yellow-50 text-yellow-700 text-sm font-medium">Guided Tour</span>
                    </div>
                </div>

                {{-- Reviews Section --}}
                <div class="bg-white rounded-3xl shadow-xl p-8">
                    <h2 class="text-3xl font-bold text-gray-800 mb-6">What people are saying</h2>

                    <div class="space-y-6">
                        @forelse($reviews as $review)
                            <div class="border-b border-gray-200 pb-6 last:border-b-0 last:pb-0">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <img src="{{ $review->user->avatar_url ?? 'https://www.gravatar.com/avatar/' . md5($review->user->email) . '?d=mp' }}" alt="{{ $review->user->name }}" class="w-10 h-10 rounded-full mr-4">
                                        <div>
                                            <p class="font-semibold text-gray-900">{{ $review->user->name }}</p>
                                            <p class="text-sm text-gray-500">{{ $review->created_at->diffForHumans() }}</p>
                                        </div>
                                    </div>
                                    {{-- Rating --}}
                                    <div class="flex items-center space-x-1 text-yellow-500">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <svg class="w-5 h-5 {{ $i <= $review->rating ? '' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.966a1 1 0 00.95.69h4.178c.969 0 1.371 1.24.588 1.81l-3.385 2.46a1 1 0 00-.364 1.118l1.287 3.966c.3.921-.755 1.688-1.54 1.118l-3.385-2.46a1 1 0 00-1.176 0l-3.385 2.46c-.784.57-1.838-.197-1.539-1.118l1.287-3.966a1 1 0 00-.364-1.118L2.049 9.393c-.783-.57-.38-1.81.588-1.81h4.178a1 1 0 00.95-.69l1.286-3.966z" />
                                            </svg>
                                        @endfor
                                    </div>
                                </div>
                                <p class="mt-3 text-gray-700">{{ $review->comment }}</p>
                            </div>
                        @empty
                            <p class="text-gray-500 text-center py-4">No reviews yet. Be the first to share your experience!</p>
                        @endforelse
                    </div>

                    {{-- Load More --}}
                    @if($hasMoreReviews)
                        <div class="mt-6 text-center">
                            <button wire:click="loadReviews"
                                class="inline-flex items-center px-6 py-2 border border-gray-300 text-sm font-medium rounded-full shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <span wire:loading.remove wire:target="loadReviews">Load More Reviews</span>
                                <span wire:loading wire:target="loadReviews">Loading...</span>
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Right column: Booking CTA & Price --}}
            <div class="lg:col-span-1">
                <div class="sticky top-24">
                    <div class="bg-white rounded-3xl shadow-xl p-8">
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">Book Your Adventure</h3>
                        <p class="text-lg text-gray-600 mb-6">
                            Secure your spot for an unforgettable experience.
                        </p>

                        <div class="flex items-end justify-between mb-8">
                            <div>
                                <span class="text-4xl font-extrabold text-gray-900">${{ number_format($activity->price) }}</span>
                                <span class="text-gray-500 font-medium">/ person</span>
                            </div>
                        </div>

                        {{-- Booking CTA --}}
                        <button wire:click="bookNow"
                            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-xl font-bold px-6 py-4 rounded-xl shadow-lg transition-colors duration-300 transform hover:scale-105">
                            Book Now
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
