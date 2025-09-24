<div>
<div class="bg-gray-100 min-h-screen py-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">

        {{-- Header & Title --}}
        <div class="text-center mb-12">
            <h1 class="text-4xl sm:text-5xl font-extrabold text-gray-900 leading-tight tracking-tight">
                My Bookings ✈️
            </h1>
            <p class="mt-3 text-lg text-gray-500">
                Manage your upcoming adventures and past trips.
            </p>
        </div>

        {{-- Search & Filters --}}
        <div class="bg-white rounded-3xl p-6 shadow-xl mb-8 border border-gray-200">
            <div class="flex flex-col md:flex-row items-center gap-4">
                {{-- Search Input --}}
                <div class="flex-grow w-full">
                    <label for="search" class="sr-only">Search bookings</label>
                    <div class="relative rounded-full shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input
                            wire:model.live.debounce.300ms="search"
                            id="search"
                            type="text"
                            placeholder="Search by name or code..."
                            class="block w-full rounded-full border-gray-300 pl-12 pr-4 py-3 focus:border-blue-500 focus:ring-blue-500 transition duration-150 ease-in-out sm:text-sm"
                        >
                    </div>
                </div>

                {{-- Status Filter --}}
                <div class="w-full md:w-auto flex-shrink-0">
                    <label for="status" class="sr-only">Filter by status</label>
                    <select
                        wire:model.live="status"
                        id="status"
                        class="block w-full rounded-full border-gray-300 py-3 pl-4 pr-10 text-gray-700 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition duration-150 ease-in-out sm:text-sm"
                    >
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Booking List --}}
        <div class="bg-white rounded-3xl shadow-xl border border-gray-200 overflow-hidden">
            @if($bookings->isEmpty())
                <div class="py-20 text-center text-gray-500">
                    <svg class="h-16 w-16 mx-auto text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-xl font-medium">No bookings found</p>
                    <p class="mt-2 text-sm text-gray-400">Try adjusting your search or filters.</p>
                </div>
            @else
                <div class="min-w-full overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Trip Details
                                </th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Dates
                                </th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    <button wire:click="sortBy('status')" class="flex items-center space-x-1">
                                        <span>Status</span>
                                        <svg class="h-4 w-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                            @if ($sortField === 'status' && $sortDirection === 'asc')
                                                <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                                            @else
                                                <path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"/>
                                            @endif
                                        </svg>
                                    </button>
                                </th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    <button wire:click="sortBy('created_at')" class="flex items-center space-x-1">
                                        <span>Booked On</span>
                                        <svg class="h-4 w-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                            @if ($sortField === 'created_at' && $sortDirection === 'asc')
                                                <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                                            @else
                                                <path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"/>
                                            @endif
                                        </svg>
                                    </button>
                                </th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($bookings as $booking)
                                <tr class="hover:bg-gray-50 transition duration-150 ease-in-out">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-16 w-16">
                                                <img class="h-16 w-16 rounded-lg object-cover" src="{{ $booking->bookable->cober_image_url ?? asset('images/placeholder.jpg') }}" alt="">
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-base font-semibold text-gray-900 truncate max-w-xs">{{ $booking->bookable->name ?? 'Trip' }}</div>
                                                <div class="text-sm text-gray-500 mt-1">Ref: #{{ $booking->confirmation_code }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <div class="font-medium">
                                            {{ \Carbon\Carbon::parse($booking->check_in_date)->format('M d, Y') }} -
                                            {{ \Carbon\Carbon::parse($booking->check_out_date)->format('M d, Y') }}
                                        </div>
                                        <div class="text-xs text-gray-400 mt-1">
                                            {{ \Carbon\Carbon::parse($booking->check_in_date)->diffInDays(\Carbon\Carbon::parse($booking->check_out_date)) }} nights
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $statusColors = [
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'confirmed' => 'bg-green-100 text-green-800',
                                                'cancelled' => 'bg-red-100 text-red-800',
                                                'completed' => 'bg-blue-100 text-blue-800',
                                            ];
                                        @endphp
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$booking->status] ?? 'bg-gray-100 text-gray-800' }}">
                                            {{ ucfirst($booking->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $booking->created_at->format('M d, Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('bookings-show', $booking) }}" class="text-blue-600 hover:text-blue-900 transition duration-150 ease-in-out font-semibold">View Details</a>                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{-- Pagination --}}
                <div class="px-6 py-4">
                    {{ $bookings->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
</div>
