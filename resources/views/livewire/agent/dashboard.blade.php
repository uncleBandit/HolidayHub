<div>
<div class="bg-gray-50 dark:bg-gray-900 min-h-screen text-gray-800 dark:text-gray-200 p-4 sm:p-6 lg:p-8">

{{-- Dashboard Header --}}
<div class="max-w-7xl mx-auto mb-10">
    <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-900 dark:text-white mb-2">Bookings Dashboard</h1>
    <p class="text-lg text-gray-600 dark:text-gray-400">
        A comprehensive overview of all your holiday package bookings.
    </p>
</div>

{{-- Dashboard Stats --}}
<div class="max-w-7xl mx-auto grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg flex items-center justify-between transition-transform duration-300 hover:scale-[1.03]">
        <div class="flex flex-col">
            <span class="text-sm text-gray-500 dark:text-gray-400 font-semibold mb-1">Total Bookings</span>
            <span class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['totalBookings'] }}</span>
        </div>
        <div class="p-3 bg-indigo-500 rounded-full text-white">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M18.5 2h-13c-1.38 0-2.5 1.12-2.5 2.5v15c0 1.38 1.12 2.5 2.5 2.5h13c1.38 0 2.5-1.12 2.5-2.5v-15c0-1.38-1.12-2.5-2.5-2.5zm0 17h-13c-.28 0-.5-.22-.5-.5v-13c0-.28.22-.5.5-.5h13c.28 0 .5.22.5.5v13c0 .28-.22.5-.5.5zm-8-3h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2v-2h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2v-2h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2z"/></svg>
        </div>
    </div>
    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg flex items-center justify-between transition-transform duration-300 hover:scale-[1.03]">
        <div class="flex flex-col">
            <span class="text-sm text-gray-500 dark:text-gray-400 font-semibold mb-1">Confirmed</span>
            <span class="text-3xl font-bold text-green-500">{{ $stats['confirmed'] }}</span>
        </div>
        <div class="p-3 bg-green-500 rounded-full text-white">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17l-3.59-3.59-1.41 1.42L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>
        </div>
    </div>
    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg flex items-center justify-between transition-transform duration-300 hover:scale-[1.03]">
        <div class="flex flex-col">
            <span class="text-sm text-gray-500 dark:text-gray-400 font-semibold mb-1">Pending</span>
            <span class="text-3xl font-bold text-yellow-500">{{ $stats['pending'] }}</span>
        </div>
        <div class="p-3 bg-yellow-500 rounded-full text-white">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"/></svg>
        </div>
    </div>
    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg flex items-center justify-between transition-transform duration-300 hover:scale-[1.03]">
        <div class="flex flex-col">
            <span class="text-sm text-gray-500 dark:text-gray-400 font-semibold mb-1">Cancelled</span>
            <span class="text-3xl font-bold text-red-500">{{ $stats['cancelled'] }}</span>
        </div>
        <div class="p-3 bg-red-500 rounded-full text-white">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-xl">
        {{-- Filters --}}
        <div class="flex flex-col md:flex-row items-center gap-4 mb-6">
            <div class="relative w-full md:w-1/3">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                    </svg>
                </div>
                <input type="text" placeholder="Search bookings..." wire:model.debounce.500ms="search"
                       class="w-full pl-12 pr-4 py-3 rounded-full border-none bg-gray-100 dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-colors duration-200">
            </div>
            <select wire:model="status" class="w-full md:w-1/6 rounded-full border-none py-3 px-4 bg-gray-100 dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500 transition-colors duration-200">
                <option value="">All Statuses</option>
                <option value="confirmed">Confirmed</option>
                <option value="pending">Pending</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <input type="date" wire:model="dateFrom" class="w-full md:w-1/6 rounded-full border-none py-3 px-4 bg-gray-100 dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500 transition-colors duration-200" />
            <input type="date" wire:model="dateTo" class="w-full md:w-1/6 rounded-full border-none py-3 px-4 bg-gray-100 dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500 transition-colors duration-200" />
        </div>

        {{-- Loading Indicator --}}
        <div wire:loading class="text-center mb-6 text-indigo-600 dark:text-indigo-400 font-semibold text-lg animate-pulse">
            Loading...
        </div>

        {{-- Bookings Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full table-auto rounded-xl overflow-hidden">
                <thead class="bg-gray-100 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Customer</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Package</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Price</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $booking)
                        <tr class="border-b border-gray-100 dark:border-gray-700 transition-colors duration-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-4 whitespace-nowrap">{{ $booking->customer->name }}</td>
                            <td class="px-4 py-4 whitespace-nowrap">{{ $booking->package->title }}</td>
                            <td class="px-4 py-4 whitespace-nowrap font-medium text-gray-900 dark:text-white">${{ number_format($booking->total_price) }}</td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 text-xs rounded-full font-semibold
                                    {{ $booking->status === 'confirmed' ? 'bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-100' : '' }}
                                    {{ $booking->status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-100' : '' }}
                                    {{ $booking->status === 'cancelled' ? 'bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-100' : '' }}">
                                    {{ ucfirst($booking->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">{{ $booking->created_at->format('d M Y') }}</td>
                            <td class="px-4 py-4 whitespace-nowrap space-x-2">
                                <button class="bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded-full text-sm transition-colors duration-200">View</button>
                                <button class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-full text-sm transition-colors duration-200">Cancel</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                <p class="text-xl">No bookings found matching your criteria.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination Links --}}
        <div class="mt-8">
            {{ $bookings->links() }}
        </div>
    </div>
</div>

</div>
</div>
