<div>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Hero Section --}}
    <div class="relative rounded-2xl overflow-hidden shadow-lg">
        <img src="{{ $activity->cover_image_url }}" alt="{{ $activity->title }}"
             class="w-full h-72 sm:h-96 object-cover">

        {{-- Wishlist Toggle --}}
        <button wire:click="toggleWishlist"
            class="absolute top-4 right-4 bg-white/80 hover:bg-white rounded-full p-3 shadow-md transition">
            @if($isWishlisted)
                <svg class="w-6 h-6 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                          d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4
                          4 0 115.656 5.656L10 17.657l-6.828-6.829a4
                          4 0 010-5.656z"
                          clip-rule="evenodd" />
                </svg>
            @else
                <svg class="w-6 h-6 text-gray-600 hover:text-red-500" fill="none"
                     stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M4.318 6.318a4.5 4.5 0 000
                          6.364L12 20.364l7.682-7.682a4.5
                          4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5
                          4.5 0 00-6.364 0z" />
                </svg>
            @endif
        </button>
    </div>

    {{-- Activity Info --}}
    <div class="mt-6 space-y-4">
        <h1 class="text-3xl font-bold text-gray-900">{{ $activity->title }}</h1>
        <p class="text-gray-600">{{ $activity->location }}</p>
        <p class="text-lg text-gray-700 leading-relaxed">
            {{ $activity->description }}
        </p>
    </div>

    {{-- Booking CTA --}}
    <div class="mt-6">
        <button wire:click="bookNow"
            class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white text-lg font-semibold px-6 py-3 rounded-xl shadow-lg transition">
            Book Now
        </button>
    </div>

    {{-- Reviews --}}
    <div class="mt-10">
        <h2 class="text-2xl font-semibold text-gray-900 mb-4">Reviews</h2>

        <div class="space-y-6">
            @forelse($reviews as $review)
                <div class="bg-white p-4 rounded-xl shadow-sm border">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="font-semibold">{{ $review->user->name }}</p>
                            <p class="text-sm text-gray-500">{{ $review->created_at->diffForHumans() }}</p>
                        </div>
                        {{-- Rating --}}
                        <div class="flex items-center space-x-1">
                            @for ($i = 1; $i <= 5; $i++)
                                <svg class="w-5 h-5 {{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-300' }}"
                                     fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921
                                    1.603-.921 1.902 0l1.286 3.966a1 1
                                    0 00.95.69h4.178c.969 0 1.371
                                    1.24.588 1.81l-3.385 2.46a1 1
                                    0 00-.364 1.118l1.287
                                    3.966c.3.921-.755
                                    1.688-1.54 1.118l-3.385-2.46a1 1
                                    0 00-1.176 0l-3.385
                                    2.46c-.784.57-1.838-.197-1.539-1.118l1.287-3.966a1 1
                                    0 00-.364-1.118L2.049
                                    9.393c-.783-.57-.38-1.81.588-1.81h4.178a1 1
                                    0 00.95-.69l1.286-3.966z" />
                                </svg>
                            @endfor
                        </div>
                    </div>
                    <p class="mt-3 text-gray-700">{{ $review->comment }}</p>
                </div>
            @empty
                <p class="text-gray-500">No reviews yet. Be the first to share your experience!</p>
            @endforelse
        </div>

        {{-- Load More --}}
        @if($hasMoreReviews)
            <div class="mt-6 text-center">
                <button wire:click="loadReviews"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-5 py-2 rounded-lg shadow-sm transition">
                    Load More Reviews
                </button>
            </div>
        @endif
    </div>
</div>
</div>
