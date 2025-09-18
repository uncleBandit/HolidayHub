<div>
    <div>
    <div class="bg-gray-50 border border-gray-200 rounded-xl p-6 mb-8 shadow-sm">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">My Bookings</h2>

        <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6">
            <div class="relative flex-grow w-full md:w-auto">
                <input wire:model.live.debounce.500ms="search" type="text" placeholder="Search by place name..."
                    class="w-full pl-10 pr-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 ease-in-out">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center">
                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
            </div>

            <div class="flex space-x-2 md:space-x-4">
                <button wire:click="setStatus('all')"
                    class="px-4 py-2 rounded-full text-sm font-medium transition duration-200 ease-in-out {{ $status === 'all' ? 'bg-blue-600 text-white shadow' : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300' }}">All</button>
                <button wire:click="setStatus('upcoming')"
                    class="px-4 py-2 rounded-full text-sm font-medium transition duration-200 ease-in-out {{ $status === 'upcoming' ? 'bg-blue-600 text-white shadow' : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300' }}">Upcoming</button>
                <button wire:click="setStatus('past')"
                    class="px-4 py-2 rounded-full text-sm font-medium transition duration-200 ease-in-out {{ $status === 'past' ? 'bg-blue-600 text-white shadow' : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300' }}">Past</button>
                <button wire:click="setStatus('cancelled')"
                    class="px-4 py-2 rounded-full text-sm font-medium transition duration-200 ease-in-out {{ $status === 'cancelled' ? 'bg-blue-600 text-white shadow' : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300' }}">Cancelled</button>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        @forelse ($bookings as $booking)
            <div class="bg-white rounded-xl shadow-lg overflow-hidden flex flex-col md:flex-row transition-transform duration-300 hover:scale-[1.01]">
                @if ($booking->bookable->image_url)
                    <img src="{{ $booking->bookable->image_url }}" alt="{{ $booking->bookable->name }}"
                        class="w-full md:w-1/3 h-48 md:h-auto object-cover">
                @else
                    <div class="w-full md:w-1/3 h-48 md:h-auto bg-gray-200 flex items-center justify-center">
                        <svg class="w-16 h-16 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2a8 8 0 100 16 8 8 0 000-16zM2 10a8 8 0 0116 0 8 8 0 01-16 0z" />
                        </svg>
                    </div>
                @endif
                <div class="p-6 flex flex-col justify-between w-full md:w-2/3">
                    <div>
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="text-xl font-bold text-gray-900">{{ $booking->bookable->name }}</h3>
                            <span class="text-sm font-semibold px-3 py-1 rounded-full
                                {{ $booking->isCancelled() ? 'bg-red-200 text-red-800' : ($booking->isUpcoming() ? 'bg-green-200 text-green-800' : 'bg-blue-200 text-blue-800') }}">
                                {{ $booking->status }}
                            </span>
                        </div>
                        <p class="text-gray-600 mb-4">{{ $booking->bookable->description }}</p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-2 text-gray-700 text-sm mb-4">
                            <div class="flex items-center"><svg class="w-4 h-4 mr-2 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path d="M6 2a2 2 0 00-2 2v2H3a1 1 0 000 2h1v1a1 1 0 002 0v-1h2v1a1 1 0 002 0v-1h2v1a1 1 0 002 0v-1h1a1 1 0 001-1V5a3 3 0 00-3-3H6zM4 9a1 1 0 011-1h10a1 1 0 011 1v6a2 2 0 01-2 2H6a2 2 0 01-2-2V9z" /></svg>
                                <strong>Check-in:</strong> {{ $booking->check_in->format('M j, Y') }}
                            </div>
                            <div class="flex items-center"><svg class="w-4 h-4 mr-2 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a8 8 0 100 16 8 8 0 000-16zM2 10a8 8 0 0116 0 8 8 0 01-16 0z" /></svg>
                                <strong>Check-out:</strong> {{ $booking->check_out->format('M j, Y') }}
                            </div>
                            <div class="flex items-center"><svg class="w-4 h-4 mr-2 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 9a3 3 0 100-6 3 3 0 000 6zM10 12a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                <strong>Guests:</strong> {{ $booking->guests }}
                            </div>
                            <div class="flex items-center"><svg class="w-4 h-4 mr-2 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path d="M17.485 5.253a1 1 0 00-.707-.291L12.4 4.316l-1.928-3.856a1 1 0 00-1.748 0L6.6 4.316l-4.378.646a1 1 0 00-.555 1.705l3.17 3.09-.75 4.377a1 1 0 001.451 1.054l3.916-2.06a1 1 0 00.916 0l3.916 2.06a1 1 0 001.451-1.054l-.75-4.377 3.17-3.09a1 1 0 00.27-.99z" /></svg>
                                <strong>Price:</strong> ${{ number_format($booking->price) }}
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row justify-end items-center gap-2 mt-4">
                        @if ($booking->isPast() && !$booking->review)
                            <a href="{{ route('booking.review.create', ['booking' => $booking->id]) }}"
                                class="w-full sm:w-auto px-6 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors duration-200 text-center">
                                Leave a Review
                            </a>
                        @endif

                        <button wire:click="rebook({{ $booking->id }})"
                            class="w-full sm:w-auto px-6 py-2 border border-blue-600 text-sm font-medium rounded-md text-blue-600 hover:bg-blue-50 transition-colors duration-200">
                            Rebook
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center p-8 bg-white rounded-xl shadow-lg">
                <p class="text-gray-500 font-medium">You have no bookings that match this criteria.</p>
            </div>
        @endforelse
    </div>

    @if ($bookings->hasPages())
        <div class="mt-8">
            {{ $bookings->links() }}
        </div>
    @endif
</div>
</div>
