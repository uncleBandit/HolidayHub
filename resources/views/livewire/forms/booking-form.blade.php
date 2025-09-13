<div>
<div
    x-data="{ show: false }"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
    class="w-full max-w-xl mx-auto p-8 rounded-3xl shadow-2xl bg-white space-y-8 mt-8"
>
    <!-- Header with a more inviting tone -->
    <div class="text-center">
        <h1 class="text-3xl font-extrabold text-gray-900 leading-tight">Confirm Your Stay</h1>
        <p class="mt-2 text-gray-500 text-sm">Enter your details to finalize the booking.</p>
    </div>

    <!-- Dynamic Alert Messages -->
    @if ($errors->has('general'))
        <div class="flex items-center gap-3 p-4 bg-red-50 border border-red-200 text-red-600 rounded-xl shadow-inner">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
            </svg>
            <p class="font-medium">{{ $errors->first('general') }}</p>
        </div>
    @endif

    @if ($this->confirmationMessage)
        <div class="flex items-center gap-3 p-4 bg-green-50 border border-green-200 text-green-600 rounded-xl shadow-inner animate-fade-in-down">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
            </svg>
            <p class="font-medium">{{ $this->confirmationMessage }}</p>
        </div>
    @endif

    <form wire:submit.prevent="submit" class="space-y-6">
        <!-- Stay Details (Read-Only) -->
        @if ($checkIn && $checkOut)
            <div class="p-4 bg-gray-50 rounded-xl flex justify-between items-center shadow-inner">
                <span class="font-semibold text-gray-700">Selected Stay</span>
                <span class="font-bold text-gray-900">
                    {{ \Carbon\Carbon::parse($checkIn)->format('M j') }} - {{ \Carbon\Carbon::parse($checkOut)->format('M j') }}
                </span>
            </div>
        @endif

        <!-- Guests Input -->
        <div>
            <label for="guests" class="block text-sm font-medium text-gray-700 mb-1">Guests</label>
            <input type="number" id="guests" wire:model.defer="guests" min="1" class="w-full rounded-xl border-gray-300 shadow-sm p-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-300">
            @error('guests') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
        </div>

        <!-- Price Preview with Loading State -->
        <div class="relative min-h-[4rem]">
            <div wire:loading.class.remove="opacity-100" wire:loading.class="opacity-0" class="opacity-100 transition-opacity duration-300 ease-in-out">
                @if($pricePreview)
                    <div class="p-4 bg-gray-50 rounded-xl flex justify-between items-center shadow-inner">
                        <span class="font-semibold text-gray-700">Estimated Price</span>
                        <span class="font-extrabold text-xl text-green-600">${{ number_format($pricePreview, 2) }}</span>
                    </div>
                @endif
            </div>

            <!-- Price Loading Spinner -->
            <div wire:loading wire:target="checkIn, checkOut, guests" class="absolute inset-0 flex items-center justify-center bg-white/70 backdrop-blur-sm rounded-xl">
                <svg class="animate-spin h-6 w-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </div>

        <!-- Submit Button with Loading State -->
        <button type="submit" wire:loading.attr="disabled" wire:target="submit" class="w-full flex items-center justify-center gap-2 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-bold py-3 px-6 rounded-xl shadow-lg transition-all duration-300 transform active:scale-95 disabled:bg-gray-400 disabled:from-gray-400 disabled:to-gray-500 disabled:cursor-not-allowed">
            <span wire:loading.remove wire:target="submit">Confirm Booking</span>
            <span wire:loading wire:target="submit" class="flex items-center gap-2">
                <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Processing...
            </span>
        </button>
    </form>
</div>

</div>
