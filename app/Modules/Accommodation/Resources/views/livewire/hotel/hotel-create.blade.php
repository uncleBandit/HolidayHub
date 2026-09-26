<div>
{{-- resources/views/livewire/hotel/hotel-create.blade.php --}}
{{-- resources/views/livewire/hotel/hotel-create.blade.php --}}
<div class="min-h-screen bg-gray-100 flex items-center justify-center p-4 sm:p-6">
    <div class="bg-white rounded-3xl shadow-2xl overflow-hidden max-w-5xl w-full">
        <div class="lg:flex">
            {{-- Visual Sidebar --}}
            <div class="hidden lg:block lg:w-1/3 bg-cover bg-center relative" style="background-image: url('https://images.unsplash.com/photo-1542360261-5582c3bdc34a?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w1MDcwODZ8MHwxfHNlYXJjaHw0fHxob3RlbCUyMGJvb2tpbmd8ZW58MHx8fHwxNjk5NTgyMjE1fDA&ixlib=rb-4.0.3&q=80&w=1080');">
            </div>

            <div class="lg:w-2/3 p-8 sm:p-12">
                <h1 class="text-4xl font-extrabold mb-2 text-gray-900 leading-tight">Create Your Hotel Listing</h1>
                <p class="text-gray-600 mb-8">Let's get your hotel ready for travelers. Follow the steps below!</p>

                {{-- Progress Indicator Component --}}
                <div class="flex items-center justify-between mb-12 relative after:absolute after:bottom-1/2 after:left-0 after:right-0 after:h-0.5 after:bg-gray-200 after:z-0">
                    @php
                        $steps = [
                            1 => ['name' => 'Core Info', 'icon' => '🏨'],
                            2 => ['name' => 'Location', 'icon' => '📍'],
                            3 => ['name' => 'Pricing', 'icon' => '💵'],
                            4 => ['name' => 'Media', 'icon' => '📸'],
                            5 => ['name' => 'Policies', 'icon' => '📜'],
                            6 => ['name' => 'Rooms', 'icon' => '🔑']
                        ];
                    @endphp
                    @foreach($steps as $s => $data)
                        <div class="flex-1 text-center z-10 transition-transform duration-300 {{ $step === $s ? 'scale-110' : '' }}">
                            <div class="w-12 h-12 mx-auto rounded-full flex items-center justify-center font-bold text-lg
                                {{ $step >= $s ? 'bg-indigo-600 text-white shadow-lg' : 'bg-white text-gray-500 border-2 border-gray-200' }}">
                                {{ $data['icon'] }}
                            </div>
                            <span class="block mt-2 text-sm font-semibold transition-colors duration-300 {{ $step >= $s ? 'text-indigo-700' : 'text-gray-500' }}">
                                {{ $data['name'] }}
                            </span>
                        </div>
                    @endforeach
                </div>

                <form wire:submit.prevent="save" class="space-y-8">
                    {{-- Form Steps --}}
                    @if($step === 1)
                        <div class="space-y-6 animate-fade-in">
                            <label class="block">
                                <span class="text-gray-700 font-semibold">Hotel Name</span>
                                <input type="text" wire:model.lazy="name"
                                    class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                                @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </label>

                            <label class="block">
                                <span class="text-gray-700 font-semibold">Slug</span>
                                <input type="text" wire:model="slug" class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500" readonly>
                                @error('slug') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </label>

                            <label class="block">
                                <span class="text-gray-700 font-semibold">Description</span>
                                <textarea wire:model.lazy="description" rows="5"
                                    class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition-colors"></textarea>
                                @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </label>
                        </div>
                    @endif

                    @if($step === 2)
                        <div class="space-y-6 animate-fade-in">
                            <label class="block">
                                <span class="text-gray-700 font-semibold">Address</span>
                                <input type="text" wire:model.lazy="address" class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @error('address') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </label>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <label class="block">
                                    <span class="text-gray-700 font-semibold">City</span>
                                    <input type="text" wire:model.lazy="city" class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    @error('city') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </label>
                                <label class="block">
                                    <span class="text-gray-700 font-semibold">Country</span>
                                    <input type="text" wire:model.lazy="country" class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    @error('country') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </label>
                                <label class="block">
                                    <span class="text-gray-700 font-semibold">Latitude / Longitude</span>
                                    <div class="flex gap-4 mt-2">
                                        <input type="text" wire:model.lazy="latitude" placeholder="Lat" class="block w-1/2 rounded-xl border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        <input type="text" wire:model.lazy="longitude" placeholder="Lng" class="block w-1/2 rounded-xl border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    </div>
                                    @error('latitude') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                                    @error('longitude') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                                </label>
                            </div>
                        </div>
                    @endif

                    @if($step === 3)
                        <div class="space-y-6 animate-fade-in">
                            <label class="block">
                                <span class="text-gray-700 font-semibold">Stars</span>
                                <select wire:model="stars" class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    @for($i=1; $i<=5; $i++)
                                        <option value="{{ $i }}">{{ $i }} Star{{ $i>1?'s':'' }}</option>
                                    @endfor
                                </select>
                                @error('stars') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </label>
                            <label class="block">
                                <span class="text-gray-700 font-semibold">Average Price Per Night (USD)</span>
                                <input type="number" wire:model.lazy="avg_price_per_night" class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @error('avg_price_per_night') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </label>
                        </div>
                    @endif

                    @if($step === 4)
                        <div class="space-y-6 animate-fade-in">
                            <label class="block">
                                <span class="text-gray-700 font-semibold">Cover Image</span>
                                <input type="file" wire:model="cover_image" class="mt-2">
                                @error('cover_image') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                @if ($cover_image)
                                    <img src="{{ $cover_image->temporaryUrl() }}" class="mt-4 w-64 h-40 object-cover rounded-2xl shadow-md border-2 border-indigo-200">
                                @endif
                            </label>

                            <label class="block">
                                <span class="text-gray-700 font-semibold">Gallery Images</span>
                                <input type="file" wire:model="gallery" multiple class="mt-2">
                                <div class="flex flex-wrap mt-4 gap-4">
                                    @foreach($gallery as $img)
                                        <img src="{{ $img->temporaryUrl() }}" class="w-32 h-24 object-cover rounded-lg shadow-sm border">
                                    @endforeach
                                </div>
                            </label>
                        </div>
                    @endif

                    @if($step === 5)
                        <div class="space-y-6 animate-fade-in">
                            <label class="block">
                                <span class="text-gray-700 font-semibold">Check-in Time</span>
                                <input type="text" wire:model.lazy="policies.check_in" class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @error('policies.check_in') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </label>
                            <label class="block">
                                <span class="text-gray-700 font-semibold">Check-out Time</span>
                                <input type="text" wire:model.lazy="policies.check_out" class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @error('policies.check_out') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </label>
                            <label class="block">
                                <span class="text-gray-700 font-semibold">Cancellation Policy</span>
                                <textarea wire:model.lazy="policies.cancellation" rows="4" class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                                @error('policies.cancellation') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </label>

                            <label class="block">
                                <span class="text-gray-700 font-semibold">Amenities</span>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-2">
                                    @foreach($amenities as $amenity)
                                        <label class="flex items-center gap-2 border p-3 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                                            <input type="checkbox" wire:model="selectedAmenities" value="{{ $amenity->id }}" class="form-checkbox text-indigo-600 rounded-md">
                                            <span class="text-gray-800">{{ $amenity->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </label>
                        </div>
                    @endif

                    @if($step === 6)
                        <div class="space-y-6 animate-fade-in">
                            <button type="button" wire:click="addRoomType" class="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-full shadow-lg transform transition-transform duration-300 hover:scale-105 hover:bg-indigo-700">Add Room Type</button>

                            @foreach($roomTypes as $index => $room)
                                <div class="relative bg-gray-50 p-6 rounded-2xl shadow-inner space-y-4">
                                    <button type="button" wire:click="removeRoomType({{ $index }})"
                                        class="absolute top-4 right-4 text-red-500 hover:text-red-700 text-2xl font-bold transition-colors">&times;</button>
                                    <label class="block">
                                        <span class="font-semibold text-gray-700">Name</span>
                                        <input type="text" wire:model.lazy="roomTypes.{{ $index }}.name" class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    </label>
                                    <label class="block">
                                        <span class="font-semibold text-gray-700">Price per Night</span>
                                        <input type="number" wire:model.lazy="roomTypes.{{ $index }}.price_per_night" class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    </label>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <label class="block">
                                            <span class="font-semibold text-gray-700">Capacity</span>
                                            <input type="number" wire:model.lazy="roomTypes.{{ $index }}.capacity" class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        </label>
                                        <label class="block">
                                            <span class="font-semibold text-gray-700">Beds</span>
                                            <input type="number" wire:model.lazy="roomTypes.{{ $index }}.beds" class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Navigation Buttons with Loading States --}}
                    <div class="flex justify-between items-center pt-8">
                        @if($step > 1)
                            <button type="button" wire:click="previousStep" wire:loading.attr="disabled" class="px-6 py-3 text-lg font-semibold text-gray-700 rounded-full transition-colors duration-300 hover:bg-gray-200 disabled:opacity-50">
                                <span wire:loading.remove wire:target="previousStep">← Back</span>
                                <span wire:loading wire:target="previousStep" class="flex items-center space-x-2">
                                    <svg class="animate-spin h-5 w-5 text-gray-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span>Processing...</span>
                                </span>
                            </button>
                        @endif

                        @if($step < 6)
                            <button type="button" wire:click="nextStep" wire:loading.attr="disabled" class="ml-auto px-8 py-3 bg-indigo-600 text-white text-lg font-semibold rounded-full shadow-lg transform transition-transform duration-300 hover:scale-105 hover:bg-indigo-700 disabled:opacity-50">
                                <span wire:loading.remove wire:target="nextStep">Next Step →</span>
                                <span wire:loading wire:target="nextStep" class="flex items-center space-x-2">
                                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span>Processing...</span>
                                </span>
                            </button>
                        @else
                            <button type="submit" wire:loading.attr="disabled" class="ml-auto px-8 py-3 bg-green-500 text-white text-lg font-semibold rounded-full shadow-lg transform transition-transform duration-300 hover:scale-105 hover:bg-green-600 disabled:opacity-50">
                                <span wire:loading.remove wire:target="save">Publish Hotel 🎉</span>
                                <span wire:loading wire:target="save" class="flex items-center space-x-2">
                                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span>Publishing...</span>
                                </span>
                            </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</div>
