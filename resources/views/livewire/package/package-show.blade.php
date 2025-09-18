<div>
    <div class="max-w-8xl mx-auto p-4 md:p-10 space-y-12 font-sans text-gray-800">

    {{-- Hero Section with Title and Action --}}
    <section class="relative h-[70vh] md:h-[85vh] rounded-3xl overflow-hidden shadow-2xl">
        <img src="{{ $package->main_image }}" alt="{{ $package->name }}" class="w-full h-full object-cover object-center transition-transform duration-500 hover:scale-105" />
        <div class="absolute inset-0 bg-gradient-to-t from-gray-900/90 to-transparent flex items-end">
            <div class="p-6 md:p-12 text-white w-full">
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                    <div class="space-y-2">
                        <p class="text-sm md:text-lg font-light tracking-wide">{{ $package->destination?->name }}</p>
                        <h1 class="text-4xl md:text-6xl font-extrabold tracking-tight leading-none drop-shadow-lg">{{ $package->name }}</h1>
                        <div class="flex items-center gap-4 text-sm font-semibold">
                            <span class="bg-white/20 px-3 py-1 rounded-full">{{ $package->duration_label }}</span>
                            <span class="flex items-center gap-1 text-yellow-400">
                                <i class="fas fa-star text-base"></i> {{ number_format($package->average_rating, 1) }} / 5
                            </span>
                            <span class="text-gray-200">({{ $package->reviews->count() }} reviews)</span>
                        </div>
                    </div>
                    <div class="md:text-right">
                        <p class="text-xl md:text-3xl font-light">From</p>
                        <p class="text-3xl md:text-5xl font-extrabold text-emerald-300 drop-shadow-md">{{ $package->formatted_price }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Main Content and Booking Widget Section --}}
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-12">

        {{-- Left Column: Details and Gallery --}}
        <div class="lg:col-span-2 space-y-12">

            {{-- Package Tabs --}}
            <div x-data="{ activeTab: @entangle('activeTab') }" class="space-y-6">
                <div class="flex flex-wrap gap-4 border-b border-gray-200 pb-2 overflow-x-auto no-scrollbar">
                    <button @click="activeTab = 'overview'" :class="{ 'border-indigo-600 text-indigo-600 font-bold': activeTab === 'overview', 'border-transparent text-gray-500 hover:text-gray-900': activeTab !== 'overview' }" class="pb-2 border-b-2 transition-colors duration-200">Overview</button>
                    <button @click="activeTab = 'gallery'" :class="{ 'border-indigo-600 text-indigo-600 font-bold': activeTab === 'gallery', 'border-transparent text-gray-500 hover:text-gray-900': activeTab !== 'gallery' }" class="pb-2 border-b-2 transition-colors duration-200">Gallery</button>
                    <button @click="activeTab = 'reviews'" :class="{ 'border-indigo-600 text-indigo-600 font-bold': activeTab === 'reviews', 'border-transparent text-gray-500 hover:text-gray-900': activeTab !== 'reviews' }" class="pb-2 border-b-2 transition-colors duration-200">Reviews</button>
                </div>

                {{-- Tab Content --}}
                <div x-show="activeTab === 'overview'" class="prose max-w-none text-gray-700 leading-relaxed">
                    <h2 class="text-3xl font-extrabold mb-4">About This Trip</h2>
                    <p class="mb-6">{{ $package->description }}</p>

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

                <div x-show="activeTab === 'gallery'" x-cloak>
                    <h2 class="text-3xl font-extrabold mb-4">Gallery</h2>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                        @foreach($package->images as $image)
                            <img wire:click="showGallery({{ $image->id }})" src="{{ $image->url }}" alt="" class="rounded-xl shadow-lg object-cover h-40 w-full cursor-pointer transition-transform duration-300 hover:scale-105" />
                        @endforeach
                    </div>
                </div>

                <div x-show="activeTab === 'reviews'" x-cloak>
                    <h2 class="text-3xl font-extrabold mb-6">Guest Reviews ({{ $reviews->total() }})</h2>
                    <div class="space-y-6">
                        @forelse($reviews as $review)
                            <div class="bg-gray-50 p-6 rounded-2xl shadow-md border border-gray-200">
                                <div class="flex items-center gap-4 mb-2">
                                    <div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center text-lg font-bold text-gray-600">{{ strtoupper(substr($review->user?->name, 0, 1)) }}</div>
                                    <div>
                                        <div class="font-bold text-lg">{{ $review->user?->name ?? 'Anonymous' }}</div>
                                        <div class="text-sm text-gray-500">{{ $review->created_at->format('M d, Y') }}</div>
                                    </div>
                                    <span class="ml-auto text-yellow-500 text-xl">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <i class="{{ $i <= $review->rating ? 'fas fa-star' : 'far fa-star' }}"></i>
                                        @endfor
                                    </span>
                                </div>
                                <p class="text-gray-700 leading-relaxed">{{ $review->comment }}</p>
                            </div>
                        @empty
                            <p class="text-gray-500 text-center py-12">No reviews yet. Be the first to share your experience!</p>
                        @endforelse
                    </div>
                    <div class="mt-8">
                        {{ $reviews->links() }}
                    </div>
                </div>
            </div>

            {{-- Wishlist and Agent Info --}}
            <div class="flex items-center justify-between p-6 bg-white rounded-2xl shadow-md border-t-4 border-indigo-600">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-full overflow-hidden">
                        <img src="{{ $package->agent?->profile_photo_url }}" alt="{{ $package->agent?->name }}" class="w-full h-full object-cover">
                    </div>
                    <div>
                        <p class="font-bold text-lg">{{ $package->agent?->name ?? 'HolidayHub Agent' }}</p>
                        <p class="text-sm text-gray-500">Authorized Agent</p>
                    </div>
                </div>
                <button wire:click="toggleWishlist" class="bg-gray-100 p-3 rounded-full text-2xl text-gray-500 transition-colors duration-200 hover:bg-red-100 hover:text-red-500" title="Add to Wishlist">
                    @if($isWishlisted)
                        <i class="fas fa-heart text-red-500"></i>
                    @else
                        <i class="far fa-heart"></i>
                    @endif
                </button>
            </div>
        </div>

        {{-- Right Column: Booking Widget --}}
        <aside class="lg:col-span-1">
            <div class="sticky top-10 bg-white p-8 rounded-3xl shadow-2xl space-y-8">
                <h2 class="text-3xl font-extrabold text-gray-900">Book This Trip</h2>

                {{-- Use the Calendar + Booking Form like in hotels --}}
                @if($package)
                    <div wire:loading.flex wire:target="selectPackage" class="flex items-center justify-center p-8">
                        <svg class="animate-spin -ml-1 mr-3 h-8 w-8 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.37 0 0 5.37 0 12h4zm2 5.29A7.96 7.96 0 014 12H0c0 3.04 1.13 5.82 3 7.94l3-2.65z"></path>
                        </svg>
                        <span class="text-lg text-gray-500">Loading calendar...</span>
                    </div>

                    <div wire:loading.remove wire:target="selectPackage">
                        {{-- Calendar (availability) --}}
                        @livewire('calendar.calendar-component', ['bookable' => $package])

                        {{-- Shared Booking Form --}}
                        <livewire:forms.booking-form :bookable="$package"/>
                    </div>
                @endif
            </div>
        </aside>

    </section>

    {{-- Full-Screen Gallery Modal --}}
    <div x-data="{ open: @entangle('isGalleryOpen') }" x-show="open" class="fixed inset-0 z-[100] flex items-center justify-center bg-black bg-opacity-95 p-4" x-cloak x-transition.opacity>
        <div class="relative w-full max-w-7xl">
            <button @click="open = false" class="absolute top-4 right-4 text-white text-4xl z-50 transition-transform hover:scale-125">&times;</button>
            <img src="{{ $package->images->firstWhere('id', $activeImageId)->url ?? '' }}" alt="Full-screen gallery image" class="w-full h-auto max-h-[90vh] object-contain rounded-lg">
        </div>
    </div>
</div>
</div>
