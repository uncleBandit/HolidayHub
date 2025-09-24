<div>
<div>
    <div class="bg-white rounded-xl shadow-lg p-8 sm:p-12 max-w-2xl mx-auto my-10 border border-green-200">
        <div class="text-center">
            <svg class="h-16 w-16 text-green-500 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h1 class="text-3xl font-extrabold text-gray-900 mb-2">Booking Confirmed!</h1>
            <p class="text-gray-600 mb-8">Your trip has been successfully booked. Details are below.</p>
        </div>

        <div class="border-t border-b border-gray-200 py-6">
            <dl class="space-y-4">
                <div class="flex items-center justify-between">
                    <dt class="text-sm font-medium text-gray-500">Bookable</dt>
                    <dd class="text-sm text-gray-900 font-semibold">{{ $booking->bookable->name ?? 'N/A' }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-sm font-medium text-gray-500">Booking Ref</dt>
                    <dd class="text-sm text-gray-900 font-semibold">#{{ $booking->confirmation_code }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-sm font-medium text-gray-500">Check-in</dt>
                    <dd class="text-sm text-gray-900 font-semibold">{{ $booking->check_in_date->format('M d, Y') }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-sm font-medium text-gray-500">Check-out</dt>
                    <dd class="text-sm text-gray-900 font-semibold">{{ $booking->check_out_date->format('M d, Y') }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-sm font-medium text-gray-500">Total Amount</dt>
                    <dd class="text-sm text-gray-900 font-semibold">{{ $booking->currency }} {{ number_format($booking->total_amount, 2) }}</dd>
                </div>
            </dl>
        </div>

        <div class="mt-8 flex justify-center">
            <button
                wire:click="redirectToDashboard"
                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-full shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
            >
                OK
            </button>
        </div>
    </div>
</div>
</div>
