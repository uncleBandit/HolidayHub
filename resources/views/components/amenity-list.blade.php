@props(['amenities'])

<!-- Premium Amenity Showcase Container -->

<div class="w-full max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

<!-- Heading Section with a touch of elegance -->
<div class="text-center mb-12">
    <h2 class="text-4xl font-extrabold text-gray-900 leading-tight tracking-tight sm:text-5xl">
        Unveiling Your Stay's Signature Amenities
    </h2>
    <p class="mt-4 text-xl text-gray-500 max-w-3xl mx-auto">
        Experience the curated collection of features designed to make your visit unforgettable.
    </p>
</div>

<!-- The Masterpiece Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 md:gap-8">
    @foreach ($amenities as $amenity)
        <div class="bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 hover:scale-[1.02] p-6 border border-gray-100 flex flex-col items-center text-center">
            <!-- Icon: Larger and more prominent -->
            @if ($amenity->icon)
                <div class="w-16 h-16 flex items-center justify-center rounded-full bg-gray-50 mb-4 transition-all duration-300 group-hover:bg-blue-50">
                    <img src="{{ asset('storage/'.$amenity->icon) }}" alt="{{ $amenity->name }}" class="w-10 h-10 flex-shrink-0 text-[#83B3F8]">
                </div>
            @else
                <!-- Placeholder SVG for a truly digital marvel -->
                <div class="w-16 h-16 flex items-center justify-center rounded-full bg-blue-100 text-blue-600 mb-4 transition-all duration-300 group-hover:bg-blue-500 group-hover:text-white">
                    <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 14c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm-4-6c0-1.1.9-2 2-2s2 .9 2 2-.9 2-2 2-2-.9-2-2zm8 0c0-1.1-.9-2-2-2s-2 .9-2 2 .9 2 2 2 2-.9 2-2z"/>
                    </svg>
                </div>
            @endif

            <!-- Amenity Name -->
            <h3 class="text-lg font-bold text-gray-900 mt-2">{{ $amenity->name }}</h3>

            <!-- Amenity Description -->
            @if ($amenity->description)
                <p class="text-sm text-gray-500 mt-1">{{ $amenity->description }}</p>
            @endif
        </div>
    @endforeach
</div>

</div>
