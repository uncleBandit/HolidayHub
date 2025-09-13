@props(['amenities'])

<div class="w-full max-w-5xl mx-auto space-y-6">

<h2 class="text-3xl font-extrabold text-[#111827] text-center mb-4">Explore Our Amenities</h2>
<p class="text-lg text-gray-500 text-center mb-8 max-w-2xl mx-auto">
    These are the amenities available with this specific booking.
</p>

<!-- Amenities Grid -->
<div class="p-6 md:p-8 bg-white rounded-3xl shadow-xl">
    <ul class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach ($amenities as $amenity)
            <li class="flex items-center space-x-4 p-4 rounded-xl transition-all duration-200 bg-[#F3F4F6] hover:bg-[#F2F7FF] border border-gray-100 shadow-sm hover:shadow-md">
                @if ($amenity->icon)
                    <img src="{{ asset('storage/'.$amenity->icon) }}" alt="{{ $amenity->name }}" class="w-7 h-7 flex-shrink-0 text-[#83B3F8]">
                @else
                    <!-- Placeholder for missing icon -->
                    <svg class="w-7 h-7 text-[#83B3F8] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                @endif
                <span class="text-gray-800 font-medium text-lg">{{ $amenity->name }}</span>
            </li>
        @endforeach
    </ul>
</div>

</div>
