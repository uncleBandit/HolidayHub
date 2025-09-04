@props(['room'])

<div class="relative overflow-hidden rounded-3xl shadow-2xl group transition-transform duration-300 hover:scale-105">
    <div class="relative w-full h-72">
        <img
    src="{{ $room->images[0]['url'] ?? 'https://via.placeholder.com/600x400' }}"
    alt="Photo of {{ $room->name }}"
    class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
/>

        <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent"></div>
    </div>

    <div class="absolute bottom-0 left-0 right-0 p-6 text-white">
        <div class="flex items-end justify-between">
            <div>
                <h3 class="font-extrabold text-3xl leading-tight">{{ $room->name }}</h3>
                <p class="text-sm font-light text-gray-200 mt-1">{{ $room->description }}</p>
            </div>
            <div class="text-right">
                <p class="text-2xl font-bold">${{ $room->price_per_night }}</p>
                <p class="text-xs font-semibold text-gray-300">/ night</p>
            </div>
        </div>
    </div>

    <div class="absolute inset-0 bg-black/40 flex flex-col justify-center items-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
        <button wire:click="$emit('showGallery', {{ $room->images }})"  class="mb-4 px-6 py-3 border border-white text-white font-bold rounded-full backdrop-blur-sm transition-colors duration-300 hover:bg-white hover:text-black">
            View Gallery

        </button>
        <button wire:click="bookNow({{ $room->id }})" wire:loading.attr="disabled" class="px-8 py-4 bg-lime-400 text-black font-extrabold rounded-full shadow-lg transition-all duration-300 hover:bg-lime-500 disabled:opacity-50 disabled:cursor-not-allowed">
            <span wire:loading.remove wire:target="bookNow">Book Now</span>
            <span wire:loading wire:target="bookNow">Booking...</span>
        </button>
    </div>
</div>
