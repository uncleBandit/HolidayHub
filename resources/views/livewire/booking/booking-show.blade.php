<div>
   <div class="bg-gray-100 min-h-screen py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        {{-- Success/Error Messages --}}
        @if (session()->has('message'))
            <div class="bg-green-100 border border-green-200 text-green-700 px-4 py-3 rounded-lg relative mb-6" role="alert">
                <span class="block sm:inline">{{ session('message') }}</span>
            </div>
        @endif

        {{-- Booking Details Card --}}
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden transform transition-all duration-300 hover:shadow-3xl">

            {{-- Header --}}
            <div class="p-8 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Booking Details</h1>
                        <p class="mt-2 text-sm text-gray-500">Reference: <span class="font-mono text-gray-700">{{ $booking->confirmation_code }}</span></p>
                    </div>
                    <div class="flex-shrink-0">
                        @php
                            $statusColors = [
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'confirmed' => 'bg-green-100 text-green-800',
                                'cancelled' => 'bg-red-100 text-red-800',
                            ];
                        @endphp
                        <span class="inline-flex items-center px-4 py-1.5 rounded-full text-sm font-semibold {{ $statusColors[$booking->status] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ ucfirst($booking->status) }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Main Content Section --}}
            <div class="p-6 md:p-8 grid md:grid-cols-2 gap-8">
                {{-- Trip Summary --}}
                <div class="space-y-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ $booking->bookable->name ?? 'N/A' }}</h2>
                        <p class="text-sm text-gray-500">{{ ucfirst($booking->bookable->bookable_type) }}</p>
                    </div>

                    {{-- Image & Dates --}}
                    <div class="relative">
                        <img src="{{ $booking->bookable->cover_image_url ?? asset('images/placeholder.jpg') }}" alt="{{ $booking->bookable->name ?? 'Bookable' }} image" class="w-full h-48 object-cover rounded-2xl shadow-md">
                        <div class="absolute bottom-0 left-0 right-0 p-4 bg-gradient-to-t from-gray-900 via-transparent to-transparent rounded-b-2xl">
                            <div class="flex justify-between items-end text-white font-semibold">
                                <div>
                                    <p class="text-sm">Check-in</p>
                                    <p class="text-lg">{{ \Carbon\Carbon::parse($booking->check_in_date)->format('M d, Y') }}</p>
                                </div>
                                <div>
                                    <p class="text-sm">Check-out</p>
                                    <p class="text-lg">{{ \Carbon\Carbon::parse($booking->check_out_date)->format('M d, Y') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Guest Information --}}
                    <div>
                        <h3 class="font-semibold text-lg text-gray-700 mb-2">Travelers</h3>
                        <div class="flex items-center space-x-4 text-gray-600">
                            <span class="flex items-center">
                                <svg class="h-5 w-5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" />
                                </svg>
                                <span>{{ $booking->guests_adults ?? 'N/A' }} Adults</span>
                            </span>
                            <span class="flex items-center">
                                <svg class="h-5 w-5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 2a4 4 0 00-4 4v2a4 4 0 008 0V6a4 4 0 00-4-4zm-3 8a3 3 0 016 0v2a3 3 0 01-6 0v-2z" />
                                </svg>
                                <span>{{ $booking->guests_children ?? 'N/A' }} Children</span>
                            </span>
                        </div>
                    </div>

                    {{-- Offer Details (if applicable) --}}
                    @if ($booking->offer)
                        <div class="p-4 bg-green-50 rounded-lg border border-dashed border-green-200">
                            <div class="flex items-center space-x-3 text-green-700">
                                <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.707-10.293a1 1 0 00-1.414-1.414L7 9.586V7a1 1 0 10-2 0v4a1 1 0 001 1h4a1 1 0 100-2H7.414l2.293-2.293z" clip-rule="evenodd" />
                                </svg>
                                <p class="font-semibold">{{ $booking->offer->name ?? 'Special Offer' }}</p>
                            </div>
                            <p class="mt-1 text-sm text-green-600">{{ $booking->offer->description ?? 'You received a special discount on this booking!' }}</p>
                        </div>
                    @endif
                </div>

                {{-- Pricing & Actions --}}
                <div class="space-y-6 md:border-l md:border-gray-200 md:pl-8">
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-500">Booking Total</p>
                        <p class="mt-1 text-4xl font-extrabold text-blue-600 tracking-tight">${{ number_format($booking->total_price, 2) }}</p>
                    </div>

                    <div class="space-y-2">
                        <div class="flex justify-between items-center text-gray-700">
                            <span class="font-medium">Subtotal</span>
                            <span class="text-sm">${{ number_format($booking->base_price, 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center text-gray-700">
                            <span class="font-medium">Taxes & Fees</span>
                            <span class="text-sm">${{ number_format($booking->tax_amount, 2) }}</span>
                        </div>
                        @if ($booking->discount_amount > 0)
                            <div class="flex justify-between items-center text-green-600">
                                <span class="font-medium">Discount</span>
                                <span class="text-sm">- ${{ number_format($booking->discount_amount, 2) }}</span>
                            </div>
                        @endif
                        <div class="border-t border-gray-200 pt-4 flex justify-between items-center font-bold text-gray-900">
                            <span>Total</span>
                            <span>${{ number_format($booking->final_price, 2) }}</span>
                        </div>
                    </div>

                    <div class="space-y-4 pt-6">
                        {{-- Rebook Button --}}
                        <button
                            wire:click="rebook"
                            class="w-full flex justify-center py-3 px-6 border border-transparent rounded-full shadow-lg text-lg font-bold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out transform hover:-translate-y-0.5"
                        >
                            Rebook This Trip
                        </button>

                        {{-- Other Actions --}}
                        <button class="w-full flex justify-center py-3 px-6 border border-gray-300 rounded-full shadow-sm text-lg font-semibold text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out">
                            Download Invoice
                        </button>
                    </div>

                    {{-- Legal/Cancellation Info (Optional) --}}
                    <div class="text-xs text-gray-400 text-center pt-4">
                        Please check the cancellation policy for your booking provider.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
