<div>
<div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
<!-- Header with quick stats -->
<div class="mb-8">
<h1 class="text-4xl font-bold text-gray-900 dark:text-white">Your Dashboard</h1>
<p class="mt-2 text-lg text-gray-500 dark:text-gray-400">A quick overview of your latest bookings and performance.</p>
</div>

<!-- Quick Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-xl border border-gray-100 dark:border-gray-700">
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Bookings</h3>
        <p class="mt-1 text-3xl font-semibold text-gray-900 dark:text-white">{{ number_format($stats['totalBookings']) }}</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-xl border border-gray-100 dark:border-gray-700">
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Pending Bookings</h3>
        <p class="mt-1 text-3xl font-semibold text-yellow-500">{{ number_format($stats['pending']) }}</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-xl border border-gray-100 dark:border-gray-700">
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Revenue</h3>
        <p class="mt-1 text-3xl font-semibold text-green-500"> ${{ number_format($stats['todayRevenue']) }}</p>
    </div>
</div>

<!-- Main Content Area -->
<div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl p-6">

    {{-- Success Flash --}}
    @if (session()->has('message'))
        <div class="mb-6 p-4 bg-green-50 dark:bg-green-900 text-green-700 dark:text-green-300 rounded-xl shadow">
            {{ session('message') }}
        </div>
    @endif

    <!-- Actions and Search -->
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 space-y-4 md:space-y-0">
        <div class="relative w-full md:w-1/2">
            <input wire:model.debounce.300ms="search" type="text" placeholder="Search for bookings..." class="w-full pl-10 pr-4 py-2 rounded-full border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition shadow-sm">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </span>
        </div>
        <a href="{{ route('agent.packages') }}"
           class="w-full md:w-auto text-center font-semibold bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-full shadow-lg transition transform hover:scale-105">
            Package Management
        </a>
    </div>
    
    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">Recent Bookings</h2>

    <!-- Bookings Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($bookings as $booking)
            <div class="bg-gray-50 dark:bg-gray-700 p-6 rounded-2xl shadow-md hover:shadow-lg transition-shadow duration-300 border border-gray-100 dark:border-gray-600">
                <div class="flex justify-between items-start mb-4">
                    <div class="flex-grow">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white truncate">
                            {{ $booking->bookable->name ?? $booking->bookable->title ?? 'Unknown Booking' }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Guest: {{ $booking->guest->first_name ?? 'Unknown Guest' }}
                        </p>
                    </div>
                    <span class="px-3 py-1 text-sm font-bold rounded-full uppercase ml-4 shadow-sm
                        {{ $booking->status === 'confirmed' ? 'bg-green-200 text-green-800 dark:bg-green-800 dark:text-green-200' : '' }}
                        {{ $booking->status === 'pending' ? 'bg-yellow-200 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-200' : '' }}
                        {{ $booking->status === 'cancelled' ? 'bg-red-200 text-red-800 dark:bg-red-800 dark:text-red-200' : '' }}">
                        {{ ucfirst($booking->status) }}
                    </span>
                </div>

                <div class="mt-4 border-t pt-4 border-gray-200 dark:border-gray-600">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-gray-500 dark:text-gray-400 text-sm">Price:</span>
                        <span class="font-bold text-gray-900 dark:text-white text-lg">${{ number_format($booking->total_price) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm text-gray-500 dark:text-gray-400">
                        <span>Date:</span>
                        <span>{{ $booking->created_at->format('d M Y') }}</span>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button wire:click="updateStatus({{ $booking->id }}, 'confirmed')"
                            class="px-4 py-2 text-sm font-medium text-white bg-green-500 rounded-full shadow-md hover:bg-green-600 transition">Confirm</button>
                    <button wire:click="updateStatus({{ $booking->id }}, 'cancelled')"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-500 rounded-full shadow-md hover:bg-red-600 transition">Cancel</button>
                </div>
            </div>
        @empty
            <div class="col-span-1 md:col-span-3 text-center py-12 text-gray-500 dark:text-gray-400">
                No bookings found matching your criteria.
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="mt-8">
        {{ $bookings->links() }}
    </div>
</div>

</div>
</div>
