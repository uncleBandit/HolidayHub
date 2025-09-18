<div>

<div
    x-data="{ show: @entangle('showModal') }"
    x-init="$watch('show', (value) => { if (value) document.body.classList.add('overflow-hidden'); else document.body.classList.remove('overflow-hidden'); })"
    x-show="show"
    x-transition:enter="transition ease-out duration-300 transform"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-200 transform"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    class="fixed inset-0 z-[100] p-4 flex items-center justify-center backdrop-blur-sm"
>
    <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-800 bg-opacity-75 transition-opacity" @click="$wire.closeModal()"></div>

    <div x-show="show" class="bg-white rounded-3xl p-8 lg:p-12 shadow-2xl z-50 w-full max-w-4xl relative transform transition-all duration-300 ease-in-out">

        <button type="button" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors duration-200" @click="$wire.closeModal()">
            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>

        @if($bookableType)
            <div class="text-center mb-10">
                <h1 class="text-4xl lg:text-5xl font-extrabold text-gray-900 leading-tight">Finalize Your Booking</h1>
                <p class="mt-3 text-gray-500 text-lg">Your adventure awaits. Just a few more steps!</p>
            </div>


            <div x-data="{ show: @entangle('showModal') }" x-show="show" ...>
            {{-- Add a check here before rendering the modal's contents --}}
            @if ($showModal && $bookableModel)
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                    <div class="order-2 lg:order-1 space-y-6">
                        <h2 class="text-2xl font-bold text-gray-900">Your Trip Details</h2>
                        <div class="flex items-center space-x-4 bg-gray-50 p-4 rounded-xl shadow-sm border border-gray-200">
                            {{-- This line is now safe because we've checked if $bookableModel is not null --}}
                            <img src="{{ $bookableModel->hero_image_url }}" alt="{{ $bookableModel->name }}" class="w-20 h-20 object-cover rounded-xl flex-shrink-0">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">{{ $bookableModel->name }}</h3>
                                {{-- Make sure these properties exist on the model. Use a null coalescing operator if they are not always present. --}}
                                <p class="text-sm text-gray-500">{{ $bookableModel->guests ?? '?' }} guests · {{ $bookableModel->beds ?? '?' }} beds</p>
                            </div>
                        </div>
                    <div class="bg-white p-6 rounded-xl border border-gray-200 space-y-4">
                        <div class="flex justify-between items-center text-gray-800">
                            <span class="font-semibold">Check-in</span>
                            <span class="font-bold">{{ \Carbon\Carbon::parse($checkIn)->format('D, M j, Y') }}</span>
                        </div>
                        <div class="flex justify-between items-center text-gray-800">
                            <span class="font-semibold">Check-out</span>
                            <span class="font-bold">{{ \Carbon\Carbon::parse($checkOut)->format('D, M j, Y') }}</span>
                        </div>
                        <div class="flex justify-between items-center text-gray-800">
                            <span class="font-semibold">Nights</span>
                            <span class="font-bold">{{ \Carbon\Carbon::parse($checkIn)->diffInDays(\Carbon\Carbon::parse($checkOut)) }}</span>
                        </div>
                    </div>
                    <div>
                        <label for="guests" class="block text-sm font-semibold text-gray-700 mb-1">Guests</label>
                        <input type="number" id="guests" wire:model.live="guests" min="1" max="{{ $bookableModel->guests }}" class="w-full rounded-xl border-gray-300 shadow-sm p-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-300">
                        @error('guests') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div class="bg-gray-50 p-6 rounded-xl border border-gray-200 space-y-4">
                        <div class="flex justify-between items-center text-gray-700">
                            <span>Base Price (x{{ \Carbon\Carbon::parse($checkIn)->diffInDays(\Carbon\Carbon::parse($checkOut)) }} nights)</span>
                            <span>${{ number_format($pricePreview, 2) }}</span>
                        </div>
                        <div class="h-px bg-gray-200 my-2"></div>
                        <div class="flex justify-between items-center text-xl font-bold text-gray-900">
                            <span>Total</span>
                            <span wire:loading.remove wire:target="guests" class="text-3xl text-blue-600">${{ number_format($pricePreview, 2) }}</span>
                            <div wire:loading wire:target="guests" class="animate-pulse text-lg text-gray-400">
                                Calculating...
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                <div class="order-1 lg:order-2 space-y-6">
                    <h2 class="text-2xl font-bold text-gray-900">Guest Information</h2>
                    <form wire:submit.prevent="submit" class="space-y-6">
                        <div>
                            <label for="name" class="block text-sm font-semibold text-gray-700 mb-1">Full Name</label>
                            <input type="text" id="name" wire:model.defer="data.name" class="w-full rounded-xl border-gray-300 shadow-sm p-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-300">
                            @error('data.name') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">Email Address</label>
                            <input type="email" id="email" wire:model.defer="data.email" class="w-full rounded-xl border-gray-300 shadow-sm p-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-300">
                            @error('data.email') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div class="bg-gray-50 p-6 rounded-xl border border-gray-200">
                            <h3 class="font-bold text-lg text-gray-800 mb-4">Payment Method</h3>
                            <div class="flex items-center space-x-2">
                                <input type="radio" id="card" name="payment_method" checked class="form-radio text-blue-600">
                                <label for="card" class="text-gray-700 font-medium">Credit Card</label>
                                <span class="text-xs text-gray-400">(Visa, Mastercard, Amex)</span>
                            </div>
                            <div class="mt-4 space-y-2">
                                <input type="text" placeholder="Card Number" class="w-full rounded-md border-gray-300 shadow-sm p-3">
                                <div class="grid grid-cols-2 gap-4">
                                    <input type="text" placeholder="MM / YY" class="rounded-md border-gray-300 shadow-sm p-3">
                                    <input type="text" placeholder="CVC" class="rounded-md border-gray-300 shadow-sm p-3">
                                </div>
                            </div>
                        </div>
                        <button type="submit" wire:loading.attr="disabled" wire:target="submit" class="w-full flex items-center justify-center gap-2 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-bold py-4 px-6 rounded-2xl shadow-lg transition-all duration-300 transform active:scale-95 disabled:from-gray-400 disabled:to-gray-500 disabled:cursor-not-allowed text-lg">
                            <span wire:loading.remove wire:target="submit">Complete Reservation</span>
                            <span wire:loading wire:target="submit" class="flex items-center gap-2">
                                <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Processing...
                            </span>
                        </button>
                        <div class="text-center text-sm text-gray-400 mt-4">
                            By clicking "Complete Reservation," you agree to our <a href="#" class="underline hover:text-gray-600">cancellation policy</a>.
                        </div>
                    </form>
                </div>
            </div>
        @else
            <div class="p-10 text-center text-gray-500">
                <svg class="h-16 w-16 mx-auto mb-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <p>Please select a room type and dates to proceed with your booking.</p>
            </div>
        @endif
    </div>
</div>

</div>
