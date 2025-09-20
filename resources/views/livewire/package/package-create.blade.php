<div>

<div x-data
     x-init="Livewire.on('scrollToTop', () => window.scrollTo({top:0, behavior:'smooth'}))"
     class="max-w-6xl mx-auto py-8 px-4 sm:px-6 lg:px-8">

    {{-- Header with Title and Subtitle --}}
    <div class="flex items-center gap-4 mb-8">
        <div class="p-3 bg-blue-100 rounded-full">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m4 4v10l-8 4m8-4V7m8 4v10l-8 4" />
            </svg>
        </div>
        <div>
            <h2 class="text-3xl font-extrabold text-gray-900">Create a New Package</h2>
            <p class="text-base text-gray-500 mt-1">Fill in the details below to create a new travel package for your site.</p>
        </div>
    </div>

    {{-- Progress Stepper --}}
    <div class="flex items-center justify-between mb-12">
        @foreach (range(1, $maxSteps) as $i)
            <div class="flex-1 flex items-center justify-center">
                <button type="button" wire:click="$set('step', {{ $i }})" class="flex flex-col items-center group focus:outline-none">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center transition-all duration-300
                                {{ $step === $i ? 'bg-blue-600 text-white shadow-lg scale-110' :
                                   ($step > $i ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-500') }}">
                        @if($step > $i)
                           <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                           </svg>
                        @else
                           <span class="font-bold">{{ $i }}</span>
                        @endif
                    </div>
                    <div class="mt-2 text-sm text-center font-medium
                                {{ $step === $i ? 'text-blue-600 font-bold' : 'text-gray-500' }}">
                        @if ($i === 1) Core Info @endif
                        @if ($i === 2) Pricing @endif
                        @if ($i === 3) Details @endif
                        @if ($i === 4) Media @endif
                        @if ($i === 5) Availability @endif
                    </div>
                </button>
                @if ($i < $maxSteps)
                    <div class="flex-1 h-1 mx-4 transition-colors duration-300
                                {{ $step > $i ? 'bg-blue-600' : 'bg-gray-300' }}"></div>
                @endif
            </div>
        @endforeach
        <div class="ml-auto text-sm text-gray-500">Draft saved: <span class="font-medium {{ $draftId ? 'text-green-500' : 'text-red-500' }}">{{ $draftId ? 'Yes' : 'No' }}</span></div>
    </div>

    <form wire:submit.prevent="save" class="space-y-8">

        {{-- Step 1: Core Info --}}
        @if($step === 1)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Name & Slug (aligned) --}}
                <div class="col-span-full grid md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-semibold text-gray-700">Name</label>
                        <input wire:model.debounce.300ms="name" type="text" id="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="e.g., A Week in the Amazon" />
                        @error('name') <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="slug" class="block text-sm font-semibold text-gray-700">Slug</label>
                        <input wire:model.defer="slug" type="text" id="slug" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="e.g., a-week-in-the-amazon" />
                        @error('slug') <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="md:col-span-2">
                    <label for="short_description" class="block text-sm font-semibold text-gray-700">Short Description</label>
                    <textarea wire:model.lazy="short_description" rows="2" id="short_description" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="A brief, engaging summary of the package."></textarea>
                    @error('short_description') <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="full_description" class="block text-sm font-semibold text-gray-700">Full Description</label>
                    <textarea wire:model.lazy="full_description" rows="5" id="full_description" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="A comprehensive overview of what the package includes."></textarea>
                    @error('full_description') <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span> @enderror
                </div>

                {{-- Typeahead Destinations & Agents (improved) --}}
                <div>
                    <label for="destination" class="block text-sm font-semibold text-gray-700">Destination</label>
                    <div class="relative mt-1">
                        <input
                            wire:model.debounce.300ms="searchDestination"
                            id="destination"
                            placeholder="Search for a destination..."
                            type="text"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                        />

                        {{-- Results dropdown --}}
                        @if (!empty($searchDestination) && $destinations->count() > 0)
                            <ul class="absolute z-10 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-40 overflow-y-auto">
                                @foreach($destinations as $dest)
                                    <li
                                        wire:click="
                                            $set('destination_id', {{ $dest->id }});
                                            $set('searchDestination', '{{ $dest->name }}')
                                        "
                                        class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-gray-100"
                                    >
                                        <span class="block truncate">{{ $dest->name }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    @error('destination_id')
                        <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span>
                    @enderror
                </div>


        @endif

        {{-- Step 2: Pricing --}}
        @if($step === 2)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="base_price" class="block text-sm font-semibold text-gray-700">Base Price</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">{{ $currencySymbol }}</span>
                        </div>
                        <input wire:model.lazy="base_price" type="number" step="0.01" id="base_price" class="block w-full pl-7 rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="0.00" />
                    </div>
                    @error('base_price') <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="discount_price" class="block text-sm font-semibold text-gray-700">Discount Price</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">{{ $currencySymbol }}</span>
                        </div>
                        <input wire:model.lazy="discount_price" type="number" step="0.01" id="discount_price" class="block w-full pl-7 rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="0.00" />
                    </div>
                    @error('discount_price') <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="currency" class="block text-sm font-semibold text-gray-700">Currency</label>
                    <select wire:model="currency" id="currency" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        <option value="USD">USD ($)</option>
                        <option value="EUR">EUR (€)</option>
                        <option value="GBP">GBP (£)</option>
                        <option value="KES">KES (KSh)</option>
                    </select>
                    @error('currency') <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="duration_days" class="block text-sm font-semibold text-gray-700">Duration (days)</label>
                    <input wire:model.lazy="duration_days" type="number" id="duration_days" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                    @error('duration_days') <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>
        @endif

        {{-- Step 3: Details --}}
        @if($step === 3)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Inclusions & Exclusions --}}
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Inclusions</label>
                    <div class="space-y-3">
                        @foreach($inclusions as $i => $inc)
                            <div class="flex items-center gap-2">
                                <input wire:model.defer="inclusions.{{ $i }}" type="text" class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="e.g., 3 nights accommodation" />
                                <button type="button" wire:click="unset('inclusions.{{ $i }}')" class="p-2 text-red-600 hover:text-red-800 transition-colors duration-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm6 0a1 1 0 112 0v6a1 1 0 11-2 0V8z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" wire:click="addInclusion" class="mt-4 text-sm font-medium text-blue-600 hover:text-blue-800 transition-colors duration-200">+ Add another inclusion</button>
                </div>

                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Exclusions</label>
                    <div class="space-y-3">
                        @foreach($exclusions as $i => $exc)
                            <div class="flex items-center gap-2">
                                <input wire:model.defer="exclusions.{{ $i }}" type="text" class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="e.g., International flights" />
                                <button type="button" wire:click="unset('exclusions.{{ $i }}')" class="p-2 text-red-600 hover:text-red-800 transition-colors duration-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm6 0a1 1 0 112 0v6a1 1 0 11-2 0V8z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" wire:click="addExclusion" class="mt-4 text-sm font-medium text-blue-600 hover:text-blue-800 transition-colors duration-200">+ Add another exclusion</button>
                </div>

                {{-- Itinerary Builder (enhanced card design) --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700">Itinerary</label>
                    <div class="space-y-4 mt-2">
                        @foreach($itinerary as $index => $day)
                            <div class="border border-gray-200 p-4 rounded-lg shadow-sm bg-white">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="text-sm font-bold text-gray-800">Day {{ $day['day'] }}</div>
                                    <button type="button" wire:click="removeItineraryDay({{ $index }})" class="p-1 text-red-600 hover:text-red-800 transition-colors duration-200">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm6 0a1 1 0 112 0v6a1 1 0 11-2 0V8z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <input wire:model.defer="itinerary.{{ $index }}.title" placeholder="Title of the day's events" class="w-full rounded-md border-gray-300" />
                                        @error("itinerary.{$index}.title") <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
                                    </div>
                                    <div>
                                        <input wire:model.defer="itinerary.{{ $index }}.day" type="number" placeholder="Day number" class="w-full rounded-md border-gray-300" />
                                    </div>
                                    <div class="md:col-span-2">
                                        <textarea wire:model.defer="itinerary.{{ $index }}.description" rows="3" class="w-full rounded-md border-gray-300" placeholder="Description of activities for the day"></textarea>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <button type="button" wire:click="addItineraryDay()" class="mt-4 text-sm font-medium text-blue-600 hover:text-blue-800 transition-colors duration-200">+ Add another day to the itinerary</button>
                    </div>
                </div>
            </div>
        @endif

        {{-- Step 4: Media --}}
        @if($step === 4)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Cover Image --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700">Cover Image</label>
                    <div class="mt-2">
                        @if ($cover_image)
                            <img src="{{ $cover_image->temporaryUrl() }}" class="h-48 w-full object-cover rounded-lg shadow-lg" alt="Cover Image Preview" />
                        @elseif($draftId && optional(\App\Models\Package::find($draftId))->cover_image)
                            <img src="{{ asset('storage/' . \App\Models\Package::find($draftId)->cover_image) }}" class="h-48 w-full object-cover rounded-lg shadow-lg" alt="Existing Cover Image" />
                        @else
                            <div class="flex flex-col justify-center items-center h-48 w-full rounded-lg border-2 border-dashed border-gray-300 text-center text-gray-500">
                                <p>Drag & drop or</p>
                                <label for="cover-image-upload" class="font-medium text-blue-600 cursor-pointer hover:underline">browse</label>
                            </div>
                        @endif
                        <input type="file" wire:model="cover_image" id="cover-image-upload" class="sr-only" />
                    </div>
                    @error('cover_image') <div class="text-red-600 text-sm mt-2">{{ $message }}</div> @enderror
                </div>

                {{-- Gallery --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700">Gallery Images</label>
                    <div class="mt-2">
                        <input type="file" wire:model="gallery" multiple class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach($gallery as $g)
                            <img src="{{ $g->temporaryUrl() }}" class="h-24 w-24 object-cover rounded shadow" />
                        @endforeach
                    </div>
                    @error('gallery.*') <div class="text-red-600 text-sm mt-2">{{ $message }}</div> @enderror
                </div>
            </div>
        @endif

        {{-- Step 5: Availability & publish --}}
        @if($step === 5)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="available_from" class="block text-sm font-semibold text-gray-700">Available From</label>
                    <input type="date" wire:model.lazy="available_from" id="available_from" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                    @error('available_from') <div class="text-red-600 text-sm mt-1 block">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="available_to" class="block text-sm font-semibold text-gray-700">Available To</label>
                    <input type="date" wire:model.lazy="available_to" id="available_to" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                    @error('available_to') <div class="text-red-600 text-sm mt-1 block">{{ $message }}</div> @enderror
                </div>

                <div class="flex items-center gap-3">
                    <input type="checkbox" wire:model="is_featured" id="is_featured" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" />
                    <label for="is_featured" class="text-sm font-medium text-gray-700">Featured</label>
                </div>

                <div class="flex items-center gap-3">
                    <input type="checkbox" wire:model="active" id="active" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" />
                    <label for="active" class="text-sm font-medium text-gray-700">Publish (Active)</label>
                </div>
            </div>
        @endif

        {{-- Navigation Footer --}}
        <div class="flex items-center justify-between mt-8 pt-6 border-t border-gray-200">
            <div>
                @if($step > 1)
                    <button type="button" wire:click="previousStep" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-gray-700 bg-gray-200 hover:bg-gray-300 transition-colors duration-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Back
                    </button>
                @endif
            </div>

            <div class="flex gap-4">
                <button type="button" wire:click="saveDraft(false)" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-yellow-800 bg-yellow-100 hover:bg-yellow-200 transition-colors duration-200">
                    Save Draft
                </button>

                @if($step < $maxSteps)
                    <button type="button" wire:click="nextStep" class="inline-flex items-center px-4 py-2 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 transition-colors duration-200">
                        Next Step
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                @else
                    <button type="submit" class="inline-flex items-center px-6 py-2 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Save & Publish
                    </button>
                @endif
            </div>
        </div>
    </form>
</div>

</div>
