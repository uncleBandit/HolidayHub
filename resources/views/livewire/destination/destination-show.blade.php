<div class="relative min-h-screen">
    {{-- Main Visual Showcase with Manual Controls --}}
    <div class="relative w-full h-[80vh] overflow-hidden group"
         x-data="{ activeIndex: 0, images: @js($destination->gallery ?? []), prev() { this.activeIndex = (this.activeIndex - 1 + this.images.length) % this.images.length; }, next() { this.activeIndex = (this.activeIndex + 1) % this.images.length; } }"
         x-init="
             setInterval(() => {
                 if (images.length > 1) {
                     activeIndex = (activeIndex + 1) % images.length;
                 }
             }, 5000);
         ">

        {{-- Image Carousel with Cross-Fade --}}
        <template x-for="(image, index) in images" :key="index">
            <img :src="image"
                 :alt="'Gallery image ' + (index + 1)"
                 class="absolute inset-0 w-full h-full object-cover transition-opacity duration-1000 ease-in-out"
                 :class="{ 'opacity-100': index === activeIndex, 'opacity-0': index !== activeIndex }"
            >
        </template>

        {{-- Carousel Navigation --}}
        <button @click.prevent="prev()" class="absolute top-1/2 left-4 z-20 -translate-y-1/2 bg-white/30 text-white p-3 rounded-full transition-all duration-300 opacity-0 group-hover:opacity-100 focus:opacity-100 hover:bg-white/50">
            <i class="fas fa-chevron-left"></i>
        </button>
        <button @click.prevent="next()" class="absolute top-1/2 right-4 z-20 -translate-y-1/2 bg-white/30 text-white p-3 rounded-full transition-all duration-300 opacity-0 group-hover:opacity-100 focus:opacity-100 hover:bg-white/50">
            <i class="fas fa-chevron-right"></i>
        </button>

        {{-- Overlay with Heading and Subheading --}}
        <div class="absolute inset-0 bg-gradient-to-t from-gray-900/80 to-transparent flex items-end justify-center text-center">
            <div class="p-8 text-white max-w-4xl animate-fade-in-up">
                <p class="text-xl font-light tracking-wide drop-shadow-md">{{ $destination->city }}, {{ $destination->country }}</p>
                <h1 class="text-5xl md:text-7xl font-extrabold leading-tight drop-shadow-lg mt-2">
                    {{ $destination->name }}
                </h1>
                <p class="text-2xl mt-4 font-light drop-shadow-md">{{ $destination->tagline ?? 'Discover a journey of a lifetime.' }}</p>
            </div>
        </div>
    </div>

    {{-- Content Section --}}
    <div class="relative z-10 -mt-24 px-4">
        <div class="container mx-auto">
            {{-- Quick Facts --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <x-destination.quick-fact-card icon="fas fa-sun" title="Best Season" :value="$destination->best_season ?? 'Year-round'" />
                <x-destination.quick-fact-card icon="fas fa-star" title="Average Rating" :value="number_format($destination->popularity_score, 1) ?? 'N/A'" />
                <x-destination.quick-fact-card icon="fas fa-compass" title="Highlights" :value="implode(', ', array_slice($destination->highlights ?? [], 0, 2)) . '...'" />
                <x-destination.quick-fact-card icon="fas fa-suitcase-rolling" title="Packages" :value="$destination->packages_count ?? '0' . ' available'" />
            </div>

            <div x-data="{ tab: 'overview' }" class="mt-12">
                {{-- Tab Navigation --}}
                <div class="border-b mb-6 flex flex-wrap gap-x-6 justify-center">
                    <button @click="tab = 'overview'" :class="tab === 'overview' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500'" class="pb-2 border-b-2 font-medium transition-colors">Overview</button>
                    <button @click="tab = 'accommodations'" :class="tab === 'accommodations' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500'" class="pb-2 border-b-2 font-medium transition-colors">Accommodations</button>
                    <button @click="tab = 'activities'" :class="tab === 'activities' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500'" class="pb-2 border-b-2 font-medium transition-colors">Activities</button>
                    <button @click="tab = 'packages'" :class="tab === 'packages' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500'" class="pb-2 border-b-2 font-medium transition-colors">Packages</button>
                    <button @click="tab = 'reviews'" :class="tab === 'reviews' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500'" class="pb-2 border-b-2 font-medium transition-colors">Reviews</button>
                </div>

                {{-- Tab Content --}}
                <div x-show="tab === 'overview'" class="space-y-6">
                    <h2 class="text-3xl font-bold text-center mb-6">Explore the Beauty of {{ $destination->name }}</h2>
                    <div class="flex flex-col md:flex-row gap-8 items-center">
                        <div class="md:w-1/2">
                            <p class="text-gray-700 leading-relaxed text-lg">{{ $destination->description }}</p>
                            @if(!empty($destination->highlights))
                                <div class="mt-6">
                                    <h3 class="text-xl font-semibold mb-3">Must-Do Highlights</h3>
                                    <div class="flex flex-wrap gap-3">
                                        @foreach($destination->highlights as $highlight)
                                            <span class="bg-indigo-100 text-indigo-700 text-sm px-4 py-2 rounded-full font-medium">
                                                <i class="fas fa-check-circle mr-1"></i> {{ $highlight }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="md:w-1/2">
                            <img src="{{ $destination->gallery[1] ?? 'https://via.placeholder.com/600x400' }}" alt="Destination Visual" class="rounded-2xl shadow-xl transform rotate-1 transition-transform hover:rotate-0 duration-500">
                        </div>
                    </div>
                </div>

                <div x-show="tab === 'accommodations'" class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                    @forelse($accommodations as $accommodation)
                        <x-accommodation-card :accommodation="$accommodation" />
                    @empty
                        <p class="text-gray-500 col-span-full text-center">No accommodations available yet. Check back soon!</p>
                    @endforelse
                </div>

                <div x-show="tab === 'activities'" class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                    @forelse($activities as $activity)
                        <div class="bg-white rounded-2xl shadow-xl overflow-hidden transform transition-transform hover:scale-105 duration-300">
                            <img src="{{ $activity->cover_image ?? 'https://via.placeholder.com/600x400' }}" alt="{{ $activity->title }}" class="w-full h-48 object-cover">
                            <div class="p-5">
                                <h3 class="text-xl font-bold text-gray-900">{{ $activity->title }}</h3>
                                <p class="text-gray-600 text-sm mt-1">
                                    <i class="far fa-clock"></i> {{ $activity->duration ?? 'Flexible' }} · <i class="fas fa-dollar-sign"></i> {{ $activity->price ?? 'N/A' }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 col-span-full text-center">No activities listed yet.</p>
                    @endforelse
                </div>


                <div x-show="tab === 'packages'" x-cloak>
                    <livewire:package.package-search-list :destinationId="$destination->id" />
                </div>

                <div x-show="tab === 'reviews'" class="space-y-6">
                    @forelse($destination->reviews as $review)
                        <x-destination.review-card :review="$review" />
                    @empty
                        <p class="text-gray-500 text-center col-span-full">No reviews yet. Be the first to share your experience!</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
