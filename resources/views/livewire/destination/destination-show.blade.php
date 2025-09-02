{{-- resources/views/livewire/destination-show.blade.php --}}

<div class="bg-gray-50 min-h-screen">

    {{-- Hero Section remains a simple part of the main view --}}
    <div class="relative h-[60vh] md:h-[80vh] overflow-hidden">
        <img src="{{ $destination->hero_image }}" alt="Hero image of {{ $destination->name }}" class="w-full h-full object-cover brightness-75">
        <div class="absolute inset-0 flex flex-col justify-end p-8 text-white bg-gradient-to-t from-black/80 to-transparent">
            <div class="max-w-4xl mx-auto">
                <h1 class="text-4xl md:text-6xl font-extrabold tracking-tight drop-shadow-lg">{{ $destination->name }}</h1>
                <p class="text-lg md:text-2xl mt-2 drop-shadow-md">{{ $destination->city }}, {{ $destination->country }}</p>
                <p class="mt-4 text-gray-200 text-sm md:text-base leading-relaxed">{{ $destination->description }}</p>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 -mt-16 relative z-10">
        <main class="space-y-12">

            {{-- Photo Gallery & Quick Facts component --}}
            @livewire('destination.gallery-and-details', ['destination' => $destination])

            {{-- Accommodations Section with Filters & Live Data --}}
            @livewire('destination.accommodations-list', ['destinationId' => $destination->id])

            {{-- Activities & Experiences Section with Filters & Live Data --}}
            @livewire('destination.activities-list', ['destinationId' => $destination->id])

            {{-- Reviews Section with Average Rating & Paginaton --}}
            @livewire('destination.reviews', ['destinationId' => $destination->id])

        </main>
    </div>
</div>
