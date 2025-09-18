@props(['accommodation'])

@php
    $bookable = $accommodation->bookable;
    $route = '';

    // Determine the correct route based on the model instance
    if ($bookable instanceof \App\Models\Hotel) {
        $route = route('hotel-show', $bookable->slug);
    } elseif ($bookable instanceof \App\Models\BedAndBreakfast) {
        $route = route('bedandbreakfast.show', $bookable->slug);
    } elseif ($bookable instanceof \App\Models\Villa) {
        $route = route('villa.show', $bookable->slug);
    }

    // Determine the type, color, and icon for the badge
    $badgeText = '';
    $badgeClass = '';
    if ($bookable instanceof \App\Models\Hotel) {
        $badgeText = "Hotel";
        $badgeClass = 'bg-yellow-500';
    } elseif ($bookable instanceof \App\Models\BedAndBreakfast) {
        $badgeText = "B&B";
        $badgeClass = 'bg-indigo-500';
    } elseif ($bookable instanceof \App\Models\Villa) {
        $badgeText = "Villa";
        $badgeClass = 'bg-lime-500';
    }
@endphp

{{-- Only render the card if a valid route was found --}}
@if ($route)
    <a href="{{ $route }}"
       class="block relative rounded-3xl overflow-hidden shadow-xl hover:shadow-2xl transition-all duration-500 ease-in-out transform hover:-translate-y-2 group">

        {{-- Main Image with Subtle Gradient Overlay and Animation --}}
        <div class="relative w-full h-72 lg:h-80 overflow-hidden">
            <img
                src="{{ $bookable->main_image ?? 'https://via.placeholder.com/600x400' }}"
                alt="{{ $bookable->name }}"
                class="w-full h-full object-cover transition-transform duration-500 ease-in-out group-hover:scale-110"
            >
            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>

            {{-- Price Badge on the bottom left of the image --}}
            <div class="absolute bottom-4 left-4">
                <span class="text-3xl font-extrabold text-white drop-shadow-lg">
                    ${{ number_format($bookable->base_price, 0) }}
                </span>
                <span class="text-lg font-medium text-white/90"> / night</span>
            </div>

            {{-- Top Right Badge for Accommodation Type --}}
            @if ($badgeText)
                <span class="absolute top-4 right-4 {{ $badgeClass }} text-white text-xs font-bold px-3 py-1.5 rounded-full shadow-lg">
                    {{ $badgeText }}
                </span>
            @endif
        </div>

        {{-- Content Section with Modern Layout and Typography --}}
        <div class="p-6 bg-white">
            <h3 class="text-2xl font-bold text-gray-900 leading-tight mb-1 group-hover:text-indigo-600 transition-colors duration-300">
                {{ $bookable->name }}
            </h3>
            <p class="text-gray-500 text-sm font-medium mb-3">
                <i class="fas fa-map-marker-alt mr-1"></i> {{ $bookable->city }}, {{ $bookable->country }}
            </p>

            {{-- Rating and Details Row --}}
            <div class="flex items-center justify-between">
                <div class="flex items-center text-yellow-500 font-bold text-base">
                    <i class="fas fa-star mr-1"></i>
                    {{ number_format($bookable->average_rating ?? 0, 1) }}
                    <span class="text-gray-500 text-xs font-medium ml-1">
                        ({{ $bookable->reviews->count() }} reviews)
                    </span>
                </div>

                {{-- Dynamic Details based on Accommodation Type --}}
                <div class="flex items-center space-x-3 text-sm text-gray-600 font-medium">
                    @if ($bookable instanceof \App\Models\Villa)
                        <span class="flex items-center">
                            <i class="fas fa-bed mr-1.5 text-lg"></i> {{ $bookable->bedrooms }}
                        </span>
                    @endif
                    <span class="flex items-center">
                        <i class="fas fa-users mr-1.5 text-lg"></i> {{ $bookable->max_guests }}
                    </span>
                </div>
            </div>

            {{-- Animated "View Details" Call to Action at the bottom --}}
            <div class="mt-6">
                <span class="text-indigo-600 font-semibold text-sm inline-flex items-center">
                    Explore Details
                    <span class="ml-2 transform transition-transform duration-300 group-hover:translate-x-1">
                        →
                    </span>
                </span>
            </div>
        </div>
    </a>
@endif
