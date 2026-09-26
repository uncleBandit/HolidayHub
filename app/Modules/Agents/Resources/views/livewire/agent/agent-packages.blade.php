<div>
<div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
<!-- Header and Actions -->
<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 space-y-4 md:space-y-0">
<div class="flex-1">
<h1 class="text-4xl font-bold text-gray-900 dark:text-white">My Holiday Packages</h1>
<p class="mt-2 text-lg text-gray-500 dark:text-gray-400">Manage your created holiday packages and their status.</p>
</div>
<a href="{{ route('agent.packages.create') }}"
class="w-full md:w-auto text-center font-semibold bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-full shadow-lg transition transform hover:scale-105">
+ Create New Package
</a>
</div>

<!-- Filters and Search -->
<div class="mb-8 flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
    <div class="relative flex-1">
        <input wire:model.debounce.300ms="search" type="text" placeholder="Search by title or destination..." class="w-full pl-10 pr-4 py-2 rounded-full border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition shadow-sm">
        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </span>
    </div>
    <div class="relative w-full sm:w-1/4">
        <select wire:model="statusFilter" class="w-full px-4 py-2 rounded-full border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition shadow-sm">
            <option value="">All Statuses</option>
            <option value="published">Published</option>
            <option value="draft">Draft</option>
        </select>
    </div>
</div>

<!-- Packages Grid -->
@if (session('success'))
    <div class="mb-6 p-4 bg-green-50 dark:bg-green-900 text-green-700 dark:text-green-300 rounded-xl shadow">
        {{ session('success') }}
    </div>
@endif

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
    @forelse ($packages as $package)
        <div class="relative bg-white dark:bg-gray-800 rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-100 dark:border-gray-700 p-6 flex flex-col">
            <!-- Package Image (Placeholder) -->
            <div class="w-full h-40 bg-gray-200 dark:bg-gray-700 rounded-2xl overflow-hidden mb-4">
                <img src="https://placehold.co/600x400/{{ str_replace('#', '', 'e0e7ff') }}/{{ str_replace('#', '', '4f46e5') }}?text={{ urlencode($package->title) }}" alt="Package Image" class="w-full h-full object-cover">
            </div>

            <!-- Package Info -->
            <div class="flex-1">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white truncate">{{ $package->title }}</h3>
                    <span class="px-3 py-1 text-xs font-semibold rounded-full uppercase ml-4 shadow-sm
                        {{ $package->status === 'published' ? 'bg-green-200 text-green-800 dark:bg-green-800 dark:text-green-200' : 'bg-yellow-200 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-200' }}">
                        {{ ucfirst($package->status) }}
                    </span>
                </div>
                <p class="text-gray-500 dark:text-gray-400 text-sm mb-4">
                    <svg class="inline w-4 h-4 mr-1 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    {{ $package->destination }}
                </p>
                <div class="text-2xl font-extrabold text-indigo-600 dark:text-indigo-400">
                    ${{ number_format($package->price) }}
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-3">
                <a href="{{ route('agent.packages.edit', $package->id) }}"
                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-indigo-600 dark:text-indigo-400 rounded-full hover:bg-indigo-50 dark:hover:bg-indigo-900 transition-colors">
                   <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                   Edit
                </a>
                <button wire:click="confirmDelete({{ $package->id }})"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-red-600 dark:text-red-400 rounded-full hover:bg-red-50 dark:hover:bg-red-900 transition-colors">
                    <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    Delete
                </button>
            </div>
        </div>
    @empty
        <div class="col-span-1 sm:col-span-2 lg:col-span-3 text-center py-12 text-gray-500 dark:text-gray-400">
            <svg class="mx-auto w-16 h-16 text-gray-400 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <h3 class="text-xl font-semibold mb-2">No Packages Found</h3>
            <p>It looks like you haven't created any packages yet.</p>
            <a href="{{ route('agent.packages.create') }}"
               class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-full shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
               Create your first package
            </a>
        </div>
    @endforelse
</div>

{{-- Pagination --}}
<div class="mt-8">
    {{ $packages->links() }}
</div>

<!-- Delete Confirmation Modal -->
@if ($confirmingDeleteId)
    <div class="fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center p-4 z-50 transition-opacity duration-300">
        <div class="bg-white dark:bg-gray-800 rounded-3xl p-8 w-full max-w-sm shadow-2xl transform scale-100 transition-transform duration-300">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Confirm Deletion</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">Are you sure you want to delete this package? This action cannot be undone.</p>
            <div class="flex justify-end space-x-3">
                <button wire:click="$set('confirmingDeleteId', null)"
                        class="px-6 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full font-medium hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                    Cancel
                </button>
                <button wire:click="delete({{ $confirmingDeleteId }})"
                        class="px-6 py-2 bg-red-600 text-white rounded-full font-medium hover:bg-red-700 transition-colors">
                    Delete
                </button>
            </div>
        </div>
    </div>
@endif

</div>

</div>
