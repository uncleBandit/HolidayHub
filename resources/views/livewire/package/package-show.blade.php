<div>
    <div class="max-w-8xl mx-auto p-4 md:p-10 font-sans text-gray-800">

    {{-- Hero Section: Image, Title, Price --}}
    <section class="relative h-[70vh] md:h-[85vh] rounded-3xl overflow-hidden shadow-2xl group">
        <img src="{{ $package->main_image ?? 'https://source.unsplash.com/random/1200x800/?vacation,destination' }}"
             alt="{{ $package->name }}"
             class="w-full h-full object-cover object-center transition-transform duration-500 group-hover:scale-105" />
        <div class="absolute inset-0 bg-gradient-to-t from-gray-900/90 to-transparent flex items-end">
            <div class="p-6 md:p-12 text-white w-full">
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                    <div class="space-y-2">
                        <p class="text-sm md:text-lg font-light tracking-wide opacity-80">{{ $package->destination?->name }}</p>
                        <h1 class="text-4xl md:text-6xl font-extrabold tracking-tight leading-none drop-shadow-lg">{{ $package->name }}</h1>
                        <div class="flex flex-wrap items-center gap-4 text-sm font-semibold mt-2">
                            <span class="bg-white/20 px-3 py-1 rounded-full backdrop-blur-sm">{{ $package->duration_label }}</span>
                            <span class="flex items-center gap-1 text-yellow-400">
                                <i class="fas fa-star text-base"></i> {{ number_format($package->avg_rating, 1) }}
                            </span>
                            <span class="text-gray-200 opacity-70">({{ $package->reviews->count() }} reviews)</span>
                        </div>
                    </div>
                    <div class="md:text-right">
                        <p class="text-xl md:text-3xl font-light opacity-80">From</p>
                        <p class="text-3xl md:text-5xl font-extrabold text-emerald-300 drop-shadow-md">
                            ${{ number_format($package->base_price, 2) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Main Content and Booking Widget Section --}}
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-12 mt-12">

        {{-- Left Column: Details and Gallery --}}
        <div class="lg:col-span-2 space-y-12">

            {{-- Package Tabs --}}
            <div x-data="{ activeTab: @entangle('activeTab') }" class="space-y-6">
                <div class="flex flex-wrap gap-4 border-b border-gray-200 pb-2 overflow-x-auto no-scrollbar">
                    <button @click="activeTab = 'overview'"
                            :class="{ 'border-indigo-600 text-indigo-600 font-bold': activeTab === 'overview', 'border-transparent text-gray-500 hover:text-gray-900': activeTab !== 'overview' }"
                            class="pb-2 border-b-2 transition-colors duration-200">Overview</button>
                    <button @click="activeTab = 'gallery'"
                            :class="{ 'border-indigo-600 text-indigo-600 font-bold': activeTab === 'gallery', 'border-transparent text-gray-500 hover:text-gray-900': activeTab !== 'gallery' }"
                            class="pb-2 border-b-2 transition-colors duration-200">Gallery</button>
                    <button @click="activeTab = 'reviews'"
                            :class="{ 'border-indigo-600 text-indigo-600 font-bold': activeTab === 'reviews', 'border-transparent text-gray-500 hover:text-gray-900': activeTab !== 'reviews' }"
                            class="pb-2 border-b-2 transition-colors duration-200">Reviews</button>
                </div>

                {{-- Tab Content --}}
                <div x-show="activeTab === 'overview'" x-cloak>
                    <div class="prose max-w-none text-gray-700 leading-relaxed">
                        <h2 class="text-3xl font-extrabold mb-4">About This Trip</h2>
                        <p class="mb-6">{{ $package->full_description }}</p>

                        <h3 class="text-2xl font-bold mb-3">Key Features</h3>
                        <ul class="grid grid-cols-1 sm:grid-cols-2 gap-4 list-none pl-0">
                            @forelse($package->features as $feature)
                                <li class="flex items-center gap-3 bg-gray-50 p-4 rounded-xl shadow-sm">
                                    <i class="fas fa-check-circle text-emerald-500 text-xl"></i>
                                    <span class="font-medium text-gray-800">{{ $feature->name }}</span>
                                </li>
                            @empty
                                <p class="text-gray-500">No features listed.</p>
                            @endforelse
                        </ul>
                    </div>
                </div>

                <div x-show="activeTab === 'gallery'" x-cloak>
                    <h2 class="text-3xl font-extrabold mb-4">Gallery</h2>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                        @foreach($package->gallery as $url)
                            <img wire:click="showGallery('{{ $url }}')"
                                src="{{ asset('storage/' . $url) }}"
                                alt="Gallery image"
                                class="rounded-xl shadow-lg object-cover h-40 w-full cursor-pointer transition-transform duration-300 hover:scale-105" />
                        @endforeach

                    </div>
                </div>

                <div x-show="activeTab === 'reviews'" x-cloak>
                    <h2 class="text-3xl font-extrabold mb-6">Guest Reviews ({{ $reviews->total() }})</h2>
                    <div class="space-y-6">
                        @forelse($reviews as $review)
                            <div class="bg-white p-6 rounded-2xl shadow-md border border-gray-200">
                                <div class="flex items-start gap-4 mb-4">
                                    <div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center text-lg font-bold text-gray-600">
                                        {{ strtoupper(substr($review->user?->name ?? 'A', 0, 1)) }}
                                    </div>
                                    <div class="flex-1">
                                        <div class="font-bold text-lg text-gray-900">{{ $review->user?->name ?? 'Anonymous' }}</div>
                                        <div class="text-sm text-gray-500">{{ $review->created_at->format('M d, Y') }}</div>
                                    </div>
                                    <div class="flex-shrink-0 text-yellow-500 text-xl">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <i class="{{ $i <= $review->rating ? 'fas fa-star' : 'far fa-star' }}"></i>
                                        @endfor
                                    </div>
                                </div>
                                <p class="text-gray-700 leading-relaxed">{{ $review->comment }}</p>
                            </div>
                        @empty
                            <p class="text-gray-500 text-center py-12 italic">No reviews yet. Be the first to share your experience!</p>
                        @endforelse
                    </div>
                    @if($reviews->hasPages())
                        <div class="mt-8">
                            {{ $reviews->links() }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- Wishlist and Agent Info --}}
            <div class="flex flex-col md:flex-row items-center justify-between p-6 bg-white rounded-2xl shadow-md border-t-4 border-indigo-600">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-full overflow-hidden flex-shrink-0">
                        <img src="{{ $package->agent?->profile_photo_url ?? 'https://www.gravatar.com/avatar/?d=mp' }}" alt="{{ $package->agent?->name }}" class="w-full h-full object-cover">
                    </div>
                    <div>
                        <p class="font-bold text-lg text-gray-900">{{ $package->agent?->name ?? 'HolidayHub Agent' }}</p>
                        <p class="text-sm text-gray-500">Authorized Agent</p>
                    </div>
                </div>
                <button wire:click="toggleWishlist" class="mt-4 md:mt-0 md:ml-4 bg-gray-100 p-3 rounded-full text-2xl text-gray-500 transition-colors duration-200 hover:bg-red-100 hover:text-red-500" title="Add to Wishlist">
                    @if($isWishlisted)
                        <i class="fas fa-heart text-red-500 animate-pulse"></i>
                    @else
                        <i class="far fa-heart"></i>
                    @endif
                </button>
            </div>
        </div>

        {{-- Right Column: Booking Widget (Sticky) --}}
        <aside class="lg:col-span-1">
            <div class="sticky top-10 bg-white p-8 rounded-3xl shadow-2xl space-y-8">
                <h2 class="text-3xl font-extrabold text-gray-900">Book This Trip</h2>

                @if($package)
                    <div wire:loading.flex wire:target="selectPackage" class="flex items-center justify-center p-8">
                        <svg class="animate-spin -ml-1 mr-3 h-8 w-8 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-lg text-gray-500">Loading calendar...</span>
                    </div>

                    <div wire:loading.remove wire:target="selectPackage">
                        <livewire:calendar.calendar-component :bookable="$package" />
                        <livewire:forms.booking-form :bookable="$package" />
                    </div>
                @endif
            </div>
        </aside>
    </section>

    {{-- Full-Screen Gallery Modal --}}
    <div x-data="{ open: @entangle('isGalleryOpen'), activeImage: @entangle('activeImageId'), images: @js($package->images) }"
         x-show="open"
         class="fixed inset-0 z-[100] flex items-center justify-center bg-black bg-opacity-95 p-4"
         x-cloak x-transition.opacity>
        <div class="relative w-full max-w-7xl">
            {{-- Close Button --}}
            <button @click="open = false"
                    class="absolute top-4 right-4 text-white text-3xl p-2 z-10 opacity-70 hover:opacity-100 transition-opacity rounded-full bg-gray-900/50 hover:bg-gray-900/80">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>

            {{-- Image Carousel --}}
            <img :src="images[activeImage]?.url"
                 alt="Full-screen gallery image"
                 class="w-full h-auto max-h-[90vh] object-contain rounded-lg">

            {{-- Navigation Buttons --}}
            <button @click="activeImage = (activeImage > 0) ? activeImage - 1 : images.length - 1"
                    aria-label="Previous Image"
                    class="absolute left-4 top-1/2 -translate-y-1/2 text-white text-5xl opacity-70 hover:opacity-100 transition rounded-full p-2 bg-gray-900/50 hover:bg-gray-900/80">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </button>
            <button @click="activeImage = (activeImage < images.length - 1) ? activeImage + 1 : 0"
                    aria-label="Next Image"
                    class="absolute right-4 top-1/2 -translate-y-1/2 text-white text-5xl opacity-70 hover:opacity-100 transition rounded-full p-2 bg-gray-900/50 hover:bg-gray-900/80">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </button>

            {{-- Image Counter --}}
            <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 text-white bg-black bg-opacity-50 px-4 py-1 rounded-full text-sm">
                <span x-text="images.findIndex(img => img.id === activeImage) + 1"></span> / <span x-text="images.length"></span>
            </div>
        </div>
    </div>

</div>
</div>
