@props(['roomType'])

<div class="relative overflow-hidden rounded-3xl shadow-2xl group transition-all duration-500 ease-in-out hover:scale-105 hover:shadow-3xl">
<!-- Hero Image of the Room Type -->
<div class="relative w-full h-72 lg:h-80">
<img
src="{{ $roomType->hero_image_url }}"
alt="Hero image of {{ $roomType->name }}"
class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
/>
<!-- Subtle gradient overlay for text legibility -->
<div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent"></div>
</div>

<!-- Details and Pricing Overlay -->
<div class="absolute bottom-0 left-0 right-0 p-6 text-white">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h3 class="font-extrabold text-2xl md:text-3xl leading-tight">{{ $roomType->name }}</h3>
            <!-- Amenities/Features Icons -->
            <div class="flex items-center space-x-4 mt-2 mb-3">
                <span class="flex items-center text-sm font-semibold text-gray-200">
                    <svg class="w-4 h-4 mr-1 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                    {{ $roomType->capacity }} Guests
                </span>
                <span class="flex items-center text-sm font-semibold text-gray-200">
                    <svg class="w-4 h-4 mr-1 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                    {{ $roomType->beds }} Beds
                </span>
            </div>
            <p class="text-sm font-light text-gray-200 mt-1 hidden sm:block">{{ \Illuminate\Support\Str::limit($roomType->description, 100) }}</p>
        </div>
        <div class="text-left sm:text-right mt-4 sm:mt-0">
            <p class="text-2xl font-bold">${{ number_format($roomType->price_per_night) }}</p>
            <p class="text-xs font-semibold text-gray-300">/ night</p>
        </div>
    </div>
</div>

<!-- Hover Actions: Full-bleed and highly visible -->
<div class="absolute inset-0 bg-black/50 flex flex-col justify-center items-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
    <!-- Gallery button with refined styling -->
    <button
        x-data="{ roomImages: @js($roomType->gallery_images) }"
        @click="$dispatch('open-gallery', { images: roomImages })"
        class="mb-4 px-8 py-4 bg-white text-gray-900 font-extrabold rounded-full shadow-lg transition-all duration-300 hover:bg-gray-200 focus:outline-none focus:ring-4 focus:ring-white focus:ring-opacity-50">
        View Gallery
    </button>
    <!-- The booking button is now a vibrant, primary action -->
    <button
        wire:click="checkAvailability('{{ $roomType->slug }}')"
        wire:loading.attr="disabled"
        class="px-8 py-4 bg-lime-400 text-black font-extrabold rounded-full shadow-lg transition-all duration-300 hover:bg-lime-500 disabled:opacity-50 disabled:cursor-not-allowed">
        <span wire:loading.remove wire:target="checkAvailability">Check Availability</span>
        <span wire:loading wire:target="checkAvailability">Searching...</span>
    </button>
</div>

</div>
