<div>
<style>
    .frosted-glass {
        background-color: rgba(255, 255, 255, 0.3); /* Lighter background for transparency */
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(255, 255, 255, 0.5); /* Subtle border for definition */
    }
</style>

<div class="frosted-glass p-8 rounded-3xl shadow-2xl mx-auto w-full px-4 md:px-12 -mt-20 relative z-10">
    <form wire:submit.prevent="search" class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <input type="text" wire:model="destination" placeholder="Destination (e.g., Bali, Maldives...)"
            class="bg-white/50 border-none rounded-full px-6 py-4 w-full focus:ring-2 focus:ring-tropical-blue focus:outline-none transition-colors duration-200 text-lg">

        <input type="date" wire:model="checkIn" placeholder="Check-in Date"
            class="bg-white/50 border-none rounded-full px-6 py-4 w-full focus:ring-2 focus:ring-tropical-blue focus:outline-none transition-colors duration-200 text-lg">

        <input type="date" wire:model="checkOut" placeholder="Check-out Date"
            class="bg-white/50 border-none rounded-full px-6 py-4 w-full focus:ring-2 focus:ring-tropical-blue focus:outline-none transition-colors duration-200 text-lg">

        <div class="flex items-center gap-4">
            <input type="number" wire:model="guests" min="1" placeholder="Guests"
                class="bg-white/50 border-none rounded-full px-6 py-4 w-full focus:ring-2 focus:ring-tropical-blue focus:outline-none transition-colors duration-200 text-lg">

            <button type="submit"
                class="bg-sunset-orange text-white px-8 py-4 rounded-full font-bold shadow-lg hover:bg-opacity-90 transition-all duration-300 transform hover:scale-105">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 inline-block">
                    <path fill-rule="evenodd" d="M10.5 3.75a6.75 6.75 0 1 0 0 13.5 6.75 6.75 0 0 0 0-13.5ZM2.25 10.5a8.25 8.25 0 1 1 14.59 5.28l4.693 4.694a1.5 1.5 0 1 1-2.121 2.121l-4.694-4.693A8.25 8.25 0 0 1 2.25 10.5Z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    </form>
</div>


</div>
