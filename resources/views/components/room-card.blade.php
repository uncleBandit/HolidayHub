@props(['room'])

<div class="p-6 border rounded-2xl shadow-lg flex flex-col justify-between transition-transform duration-200 hover:scale-[1.01] hover:shadow-xl">
    <div>
        <h3 class="font-extrabold text-2xl text-gray-800">{{ $room->name }}</h3>
        <p class="text-sm text-gray-500 mt-1">{{ $room->description }}</p>
    </div>
    <div class="flex items-center justify-between mt-6">
        <p class="text-xl font-bold text-green-600">${{ $room->price_per_night }} / night</p>
        <button wire:click="bookNow({{ $room->id }})" wire:loading.attr="disabled"
                class="px-6 py-3 bg-blue-600 text-white font-bold rounded-full shadow-lg transition-all duration-300 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
            <span wire:loading.remove wire:target="bookNow">Book Now</span>
            <span wire:loading wire:target="bookNow">Booking...</span>
        </button>
    </div>
</div>
