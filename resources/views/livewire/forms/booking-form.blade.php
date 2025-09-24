<div>
    <div
        x-data="{ show: @entangle('showModal') }"
        x-init="$watch('show', value => {
            if (value) {
                document.body.classList.add('overflow-hidden');
            } else {
                document.body.classList.remove('overflow-hidden');
            }
        });"
        x-show="show"
        x-trap.noscroll.inert="show" {{-- TRAP FOCUS AND PREVENT SCROLL --}}
        x-on:keydown.escape.window="show = false" {{-- CLOSE ON ESCAPE --}}
        x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200 transform"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="fixed inset-0 z-[100] p-4 flex items-center justify-center backdrop-blur-sm"
    >
        {{-- Backdrop --}}
        <div
            x-show="show"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-gray-900 bg-opacity-70 transition-opacity"
            @click="$wire.closeModal()"
        ></div>

        {{-- Modal Dialog --}}
        <div
            x-show="show"
            x-transition
            class="bg-white rounded-3xl p-8 lg:p-12 shadow-2xl z-50 w-full max-w-4xl relative transform"
        >
            {{-- Close Button --}}
            <button
                type="button"
                class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors duration-200 rounded-full p-2 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                @click="$wire.closeModal()"
            >
                <span class="sr-only">Close modal</span>
                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>

            @if($bookableType)
                <div class="text-center mb-10">
                    <h1 class="text-4xl lg:text-5xl font-extrabold text-gray-900 leading-tight">Finalize Your Booking</h1>
                    <p class="mt-3 text-gray-500 text-lg">Your adventure awaits. Just a few more steps!</p>
                </div>

                @if ($bookableModel)
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                        <div class="order-2 lg:order-1 space-y-8">
                            <h2 class="text-2xl font-bold text-gray-900">Your Trip Details</h2>
                            {{-- Booking Summary --}}
                            <div class="flex items-center space-x-4 bg-gray-50 p-4 rounded-2xl shadow-sm border border-gray-200">
                                <img src="{{ $bookableModel->hero_image_url }}" alt="{{ $bookableModel->name }}" class="w-24 h-24 object-cover rounded-xl flex-shrink-0 border-2 border-white shadow-md">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900">{{ $bookableModel->name }}</h3>
                                    <p class="text-sm text-gray-500">{{ $bookableModel->guests ?? '?' }} guests · {{ $bookableModel->beds ?? '?' }} beds</p>
                                </div>
                            </div>

                            {{-- Booking Dates and Guests --}}
                            <div class="bg-white p-6 rounded-2xl border border-gray-200 space-y-4">
                                <div class="flex justify-between items-center text-gray-800">
                                    <span class="font-semibold">Check-in</span>
                                    <span class="font-bold text-lg">{{ \Carbon\Carbon::parse($checkIn)->format('D, M j, Y') }}</span>
                                </div>
                                <div class="flex justify-between items-center text-gray-800">
                                    <span class="font-semibold">Check-out</span>
                                    <span class="font-bold text-lg">{{ \Carbon\Carbon::parse($checkOut)->format('D, M j, Y') }}</span>
                                </div>
                                <div class="flex justify-between items-center text-gray-800">
                                    <span class="font-semibold">Nights</span>
                                    <span class="font-bold text-lg">{{ \Carbon\Carbon::parse($checkIn)->diffInDays(\Carbon\Carbon::parse($checkOut)) }}</span>
                                </div>
                                <div class="h-px bg-gray-200 my-2"></div>
                                <div class="flex justify-between items-center">
                                    <label for="guests" class="text-gray-700 font-semibold">Guests</label>
                                    <input
                                        type="number"
                                        id="guests"
                                        wire:model.live="guests"
                                        min="1"
                                        max="{{ $bookableModel->guests }}"
                                        class="w-24 text-center rounded-xl border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 transition-all duration-300"
                                    >
                                </div>
                                @error('guests') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            {{-- Price Breakdown --}}
                            <div class="bg-gray-50 p-6 rounded-2xl border border-gray-200 space-y-4">
                                <div class="flex justify-between items-center text-gray-700">
                                    <span>Base Price (x{{ \Carbon\Carbon::parse($checkIn)->diffInDays(\Carbon\Carbon::parse($checkOut)) }} nights)</span>
                                    <span>${{ number_format($bookableModel->price_per_night * \Carbon\Carbon::parse($checkIn)->diffInDays(\Carbon\Carbon::parse($checkOut)), 2) }}</span>
                                </div>
                                <div class="h-px bg-gray-200 my-2"></div>
                                <div class="flex justify-between items-center text-xl font-bold text-gray-900">
                                    <span>Total</span>
                                    <div wire:loading.remove wire:target="guests" class="text-3xl font-extrabold text-blue-600">${{ number_format($pricePreview, 2) }}</div>
                                    <div wire:loading wire:target="guests" class="flex items-center gap-2 text-lg text-gray-400 animate-pulse">
                                        <svg class="animate-spin h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        Calculating...
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Guest Information & Payment --}}
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

                                {{-- Payment Method Section --}}
                                <div class="bg-gray-50 p-6 rounded-2xl border border-gray-200">
                                    <h3 class="font-bold text-lg text-gray-800 mb-4">Payment Method</h3>
                                    <div class="flex items-center space-x-4">
                                        <div class="flex items-center">
                                            <input type="radio" id="card" name="payment_method" checked class="form-radio text-blue-600">
                                            <label for="card" class="ml-2 text-gray-700 font-medium">Credit Card</label>
                                        </div>
                                        <span class="text-xs text-gray-400">(Visa, Mastercard, Amex)</span>
                                    </div>
                                    <div class="mt-4 space-y-2">
                                        {{-- ⚠️ IMPORTANT: In a real app, these inputs would be replaced by a secure payment gateway's elements (e.g., Stripe) --}}
                                        <input type="text" placeholder="Card Number" class="w-full rounded-xl border-gray-300 shadow-sm p-3">
                                        <div class="grid grid-cols-2 gap-4">
                                            <input type="text" placeholder="MM / YY" class="rounded-xl border-gray-300 shadow-sm p-3">
                                            <input type="text" placeholder="CVC" class="rounded-xl border-gray-300 shadow-sm p-3">
                                        </div>
                                    </div>
                                </div>

                                {{-- Submit Button --}}
                                <button
                                    type="submit"
                                    wire:loading.attr="disabled"
                                    wire:target="submit"
                                    class="w-full flex items-center justify-center gap-2 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-bold py-4 px-6 rounded-2xl shadow-lg transition-all duration-300 transform active:scale-95 disabled:from-gray-400 disabled:to-gray-500 disabled:cursor-not-allowed text-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                >
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
                                    By clicking "Complete Reservation," you agree to our <a href="#" class="underline hover:text-gray-600 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">cancellation policy</a>.
                                </div>
                            </form>
                        </div>
                    </div>
                @else
                    {{-- Empty State Content when bookableModel is null --}}
                    <div class="p-10 text-center text-gray-500 flex flex-col items-center">
                        <svg class="h-20 w-20 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                        <p class="text-lg font-medium">Please select a room and dates to finalize your booking.</p>
                        <p class="mt-2 text-sm text-gray-400">Looks like you haven't picked a place yet. Go back and find your perfect spot!</p>
                    </div>
                @endif
            @else
                {{-- Empty State Content when bookableType is null --}}
                <div class="p-10 text-center text-gray-500 flex flex-col items-center">
                    <svg class="h-20 w-20 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                    <p class="text-lg font-medium">Please select a room and dates to finalize your booking.</p>
                    <p class="mt-2 text-sm text-gray-400">Looks like you haven't picked a place yet. Go back and find your perfect spot!</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Toast Notification Script --}}
    <script>
document.addEventListener('livewire:load', () => {

    // ✅ Booking Confirmed handler
    Livewire.on('bookingConfirmed', (bookingId, message) => {
        console.log("📡 Livewire event received:", bookingId, message);

        showToast({
            message: message || 'Booking successful!',
            description: 'Redirecting to your booking details...',
            type: 'success',
        });

        // Redirect after a short delay
        setTimeout(() => {
            window.location.href = `/bookings/${bookingId}`;
        }, 2000);
    });

    // ❌ Booking Failed handler
    Livewire.on('bookingFailed', (message) => {
        showToast({
            message: 'Booking Failed',
            description: message || 'Please try again.',
            type: 'error',
        });
    });

    // 🔔 Toast utility
    function showToast({ message, description, type }) {
        const toast = document.createElement('div');
        toast.className = `fixed top-5 right-5 px-4 py-3 rounded-lg shadow-lg text-white z-[9999]
            ${type === 'success' ? 'bg-green-600' : 'bg-red-600'}`;

        toast.innerHTML = `
            <strong class="block font-semibold">${message}</strong>
            <small>${description}</small>
        `;

        document.body.appendChild(toast);

        // Auto-remove after 3s
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }
});
</script>

</div>
