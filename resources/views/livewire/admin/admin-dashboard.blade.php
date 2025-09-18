<div>
<div class="bg-gray-50 min-h-screen py-10 px-4 sm:px-6 lg:px-8">
<div class="max-w-7xl mx-auto">
{{-- Dashboard Header --}}
<div class="flex items-center justify-between mb-8">
<h1 class="text-4xl font-extrabold text-gray-900 tracking-tight">Admin Dashboard</h1>
<div class="flex items-center space-x-4">
<div class="flex-shrink-0">
<span class="inline-flex items-center rounded-full bg-red-100 px-3 py-1.5 text-sm font-medium text-red-800">
<i class="fas fa-exclamation-triangle mr-2"></i>
Pending: {{ $pendingCount }}
</span>
</div>
</div>
</div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
        <div class="bg-white rounded-2xl shadow-lg p-6 flex flex-col items-start transition-all duration-300 transform hover:scale-105">
            <div class="flex-shrink-0">
                <i class="fas fa-users text-4xl text-indigo-500"></i>
            </div>
            <p class="mt-4 text-gray-500 text-sm font-medium uppercase tracking-wide">Total Users</p>
            <p class="mt-1 text-4xl font-bold text-gray-900">{{ $stats['users_total'] }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-lg p-6 flex flex-col items-start transition-all duration-300 transform hover:scale-105">
            <div class="flex-shrink-0">
                <i class="fas fa-hotel text-4xl text-purple-500"></i>
            </div>
            <p class="mt-4 text-gray-500 text-sm font-medium uppercase tracking-wide">Total Hotels</p>
            <p class="mt-1 text-4xl font-bold text-gray-900">{{ $stats['hotels_total'] }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-lg p-6 flex flex-col items-start transition-all duration-300 transform hover:scale-105">
            <div class="flex-shrink-0">
                <i class="fas fa-calendar-check text-4xl text-green-500"></i>
            </div>
            <p class="mt-4 text-gray-500 text-sm font-medium uppercase tracking-wide">Bookings (Last 30 days)</p>
            <p class="mt-1 text-4xl font-bold text-gray-900">{{ $stats['bookings_30d'] }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-lg p-6 flex flex-col items-start transition-all duration-300 transform hover:scale-105">
            <div class="flex-shrink-0">
                <i class="fas fa-user-tie text-4xl text-yellow-500"></i>
            </div>
            <p class="mt-4 text-gray-500 text-sm font-medium uppercase tracking-wide">Verified Providers</p>
            <p class="mt-1 text-4xl font-bold text-gray-900">{{ $stats['providers_total'] }}</p>
        </div>
    </div>

    {{-- User Management Section --}}
    <div class="bg-white rounded-2xl shadow-lg p-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-900">User Management</h2>
            <div class="mt-4 md:mt-0 flex items-center space-x-4">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search users..." class="flex-1 min-w-0 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-all">
                <button wire:click="resetFilters" class="text-sm text-gray-500 hover:text-gray-700 transition-colors">
                    Reset Filters
                </button>
            </div>
        </div>

        {{-- Filters --}}
        <div class="flex flex-wrap items-center space-x-4 mb-6 text-sm">
            <span class="text-gray-500">Filter by:</span>
            <select wire:model.live="filterRole" class="rounded-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
                <option value="all">All Roles</option>
                <option value="agent">Agent</option>
                <option value="provider">Provider</option>
                <option value="guest">Guest</option>
            </select>
            <select wire:model.live="status" class="rounded-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
                <option value="all">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="verified">Verified</option>
                <option value="suspended">Suspended</option>
            </select>
        </div>

        {{-- Users Table --}}
        <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" wire:click="toggleSort('name')">
                            User
                            @if($sortField === 'name')
                                <i class="ml-2 fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Role
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" wire:click="toggleSort('created_at')">
                            Joined
                            @if($sortField === 'created_at')
                                <i class="ml-2 fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($users as $user)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <img class="h-10 w-10 rounded-full" src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&color=7F9CF5&background=EBF4FF" alt="">
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-indigo-100 text-indigo-800">{{ ucfirst($user->role) }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($user->is_active && $user->verified_at)
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Verified</span>
                                @elseif($user->is_active && !$user->verified_at)
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Suspended</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $user->created_at->diffForHumans() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                @if($user->is_active && !$user->verified_at)
                                    <button wire:click="verifyUser({{ $user->id }})" class="text-indigo-600 hover:text-indigo-900 transition-colors mr-2">
                                        Verify
                                    </button>
                                @endif
                                @if($user->is_active)
                                    <button wire:click="suspendUser({{ $user->id }})" class="text-red-600 hover:text-red-900 transition-colors">
                                        Suspend
                                    </button>
                                @else
                                    <button wire:click="suspendUser({{ $user->id }})" class="text-green-600 hover:text-green-900 transition-colors">
                                        Unsuspend
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-12 text-gray-500 text-lg">
                                No users found matching your criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-8">
            {{ $users->links() }}
        </div>
    </div>
</div>

</div>
</div>
