<div>
<div class="bg-gray-100 min-h-screen py-12">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">

        <div class="max-w-4xl mx-auto bg-white rounded-3xl shadow-xl overflow-hidden">

            {{-- Header Section --}}
            <div class="relative bg-gradient-to-br from-indigo-600 to-indigo-800 p-8 sm:p-12 text-center text-white">
                <div class="relative w-28 h-28 mx-auto mb-4 rounded-full border-4 border-white shadow-lg overflow-hidden">
                    <img class="w-full h-full object-cover" src="{{ $user->profile?->avatar_url ?: 'https://www.gravatar.com/avatar/' . md5($user->email) . '?s=200&d=mp' }}" alt="Profile Avatar">
                </div>
                <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight">{{ $user->name }}</h1>
                <p class="text-indigo-200 mt-1">{{ $user->email }}</p>

                {{-- Tab Navigation --}}
                <div class="absolute -bottom-10 left-1/2 -translate-x-1/2 flex items-center bg-white rounded-full shadow-lg p-2 space-x-2">
                    <button wire:click="setTab('account')" class="transition-all duration-300 px-6 py-2 rounded-full text-sm font-semibold {{ $tab === 'account' ? 'bg-indigo-600 text-white shadow' : 'text-gray-600 hover:bg-gray-100' }}">
                        Account
                    </button>
                    <button wire:click="setTab('profile')" class="transition-all duration-300 px-6 py-2 rounded-full text-sm font-semibold {{ $tab === 'profile' ? 'bg-indigo-600 text-white shadow' : 'text-gray-600 hover:bg-gray-100' }}">
                        Profile
                    </button>
                    <button wire:click="setTab('password')" class="transition-all duration-300 px-6 py-2 rounded-full text-sm font-semibold {{ $tab === 'password' ? 'bg-indigo-600 text-white shadow' : 'text-gray-600 hover:bg-gray-100' }}">
                        Security
                    </button>
                </div>
            </div>

            {{-- Main Content Section (Adjusted to account for the overlapping tabs) --}}
            <div class="p-8 sm:p-12 pt-16">

                {{-- Success Notification --}}
                @if (session()->has('success'))
                    <div class="mb-6 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded-lg shadow-sm" role="alert">
                        <div class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 1a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="font-medium">{{ session('success') }}</p>
                        </div>
                    </div>
                @endif

                {{-- Tab Content --}}
                <div class="space-y-8">

                    {{-- Account Tab --}}
                    @if ($tab === 'account')
                        <div class="animate-fade-in-up">
                            <h2 class="text-2xl font-bold text-gray-800 mb-6">Account Information</h2>
                            <form wire:submit.prevent="saveAccount" class="space-y-6">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                                    <input type="text" id="name" wire:model="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @error('name') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                    <input type="email" id="email" wire:model="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @error('email') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <button type="submit" class="w-full sm:w-auto px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg shadow-md transform transition-transform duration-300 hover:scale-105 hover:bg-indigo-700">
                                        <span wire:loading.remove wire:target="saveAccount">Save Changes</span>
                                        <span wire:loading wire:target="saveAccount">Saving...</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif

                    {{-- Profile Tab --}}
                    @if ($tab === 'profile')
                        <div class="animate-fade-in-up">
                            <h2 class="text-2xl font-bold text-gray-800 mb-6">Profile Details</h2>
                            <form wire:submit.prevent="saveProfile" class="space-y-6">
                                <div>
                                    <label for="avatar" class="block text-sm font-medium text-gray-700 mb-2">Avatar</label>
                                    <input type="file" id="avatar" wire:model="avatar" class="block w-full text-sm text-gray-500
                                        file:mr-4 file:py-2 file:px-4
                                        file:rounded-full file:border-0
                                        file:text-sm file:font-semibold
                                        file:bg-indigo-50 file:text-indigo-700
                                        hover:file:bg-indigo-100 transition-colors">
                                    @error('avatar') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror

                                    @if ($avatar)
                                        <div class="mt-4">
                                            <p class="text-gray-500 text-sm">Preview:</p>
                                            <img src="{{ $avatar->temporaryUrl() }}" class="w-24 h-24 rounded-full object-cover mt-2 shadow-md">
                                        </div>
                                    @endif
                                </div>

                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                                    <input type="text" id="phone" wire:model="profileData.phone" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @error('profileData.phone') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                                    <input type="text" id="address" wire:model="profileData.address" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @error('profileData.address') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="bio" class="block text-sm font-medium text-gray-700">Bio</label>
                                    <textarea id="bio" wire:model="profileData.bio" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                    @error('profileData.bio') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <button type="submit" class="w-full sm:w-auto px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg shadow-md transform transition-transform duration-300 hover:scale-105 hover:bg-indigo-700">
                                        <span wire:loading.remove wire:target="saveProfile">Update Profile</span>
                                        <span wire:loading wire:target="saveProfile">Updating...</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif

                    {{-- Password Tab --}}
                    @if ($tab === 'password')
                        <div class="animate-fade-in-up">
                            <h2 class="text-2xl font-bold text-gray-800 mb-6">Change Password</h2>
                            <form wire:submit.prevent="savePassword" class="space-y-6">
                                <div>
                                    <label for="password" class="block text-sm font-medium text-gray-700">New Password</label>
                                    <input type="password" id="password" wire:model.lazy="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @error('password') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                                    <p class="mt-2 text-xs text-gray-500">Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, and one number.</p>
                                </div>
                                <div>
                                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                                    <input type="password" id="password_confirmation" wire:model.lazy="password_confirmation" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <button type="submit" class="w-full sm:w-auto px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg shadow-md transform transition-transform duration-300 hover:scale-105 hover:bg-indigo-700">
                                        <span wire:loading.remove wire:target="savePassword">Update Password</span>
                                        <span wire:loading wire:target="savePassword">Updating...</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif

                </div>
            </div>
        </div>

        {{-- Add a beautiful footer or extra info here if needed --}}

    </div>
</div>
</div>
