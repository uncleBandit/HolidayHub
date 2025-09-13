<div>

<div
x-data="packageForm()"
x-init="init()"
class="relative bg-white dark:bg-gray-800 rounded-3xl shadow-2xl p-6 sm:p-10 space-y-8 overflow-hidden transform transition-all duration-300"
>

<div class="absolute inset-0 z-0 opacity-20" style="background-image: radial-gradient(circle at 100% 100%, #3f83f8 0%, transparent 50%);"></div>

<div class="relative z-10">
    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Create a New Package</h2>
    <p class="text-gray-500 dark:text-gray-400">
        Follow the steps to build your next unforgettable holiday package.
    </p>

    <!-- Step Wizard -->
    <div class="mt-8 flex justify-between items-center text-sm font-medium text-center text-gray-500 dark:text-gray-400">
        <template x-for="(step, index) in steps" :key="index">
            <div class="flex-1 flex flex-col items-center">
                <div class="relative flex flex-col items-center group cursor-pointer" @click="currentStep = index">
                    <div
                        class="w-12 h-12 flex items-center justify-center rounded-full border-2 transition-colors duration-300"
                        :class="{
                            'border-indigo-600 text-indigo-600 bg-indigo-50 dark:bg-indigo-900 dark:text-indigo-400': currentStep === index,
                            'border-gray-300 text-gray-500 dark:border-gray-600 dark:text-gray-400 group-hover:border-indigo-500 group-hover:text-indigo-500': currentStep !== index,
                            'bg-indigo-600 text-white dark:bg-indigo-500 dark:text-white': currentStep > index
                        }"
                    >
                        <span x-text="index+1" class="font-bold"></span>
                    </div>
                    <span class="mt-2" x-text="step"></span>
                </div>
                <div class="flex-1 h-1 mx-4 -mt-6"
                     :class="index < steps.length - 1 ? 'bg-gray-300 dark:bg-gray-600' : ''">
                </div>
            </div>
        </template>
    </div>
</div>

<form wire:submit.prevent="store" class="relative z-10 space-y-8 mt-12">

    <!-- Step 1: Package Details -->
    <div x-show="currentStep === 0" x-transition.opacity.duration.500ms>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="name" class="block font-semibold text-gray-700 dark:text-gray-300 mb-2">Package Name</label>
                <input id="name" type="text" wire:model="name" placeholder="e.g., Summer in Bali"
                       class="w-full px-4 py-3 rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm transition" />
                @error('name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="destination" class="block font-semibold text-gray-700 dark:text-gray-300 mb-2">Destination</label>
                <input id="destination" type="text" wire:model="destination" placeholder="e.g., Bali, Indonesia"
                       class="w-full px-4 py-3 rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm transition" />
                @error('destination') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="md:col-span-2">
                <label for="short_description" class="block font-semibold text-gray-700 dark:text-gray-300 mb-2">Short Description</label>
                <textarea id="short_description" wire:model="short_description" placeholder="A brief summary of the package..." rows="3"
                          class="w-full px-4 py-3 rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm transition"></textarea>
                @error('short_description') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="md:col-span-2">
                <label for="full_description" class="block font-semibold text-gray-700 dark:text-gray-300 mb-2">Full Description</label>
                <textarea id="full_description" wire:model="full_description" placeholder="A detailed description of the package and its features..." rows="6"
                          class="w-full px-4 py-3 rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm transition"></textarea>
                @error('full_description') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <!-- Step 2: Pricing & Currency -->
    <div x-show="currentStep === 1" x-transition.opacity.duration.500ms>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="base_price" class="block font-semibold text-gray-700 dark:text-gray-300 mb-2">Base Price</label>
                <input id="base_price" type="number" wire:model="base_price" placeholder="e.g., 1250"
                       class="w-full px-4 py-3 rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm transition" />
                @error('base_price') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="discount_price" class="block font-semibold text-gray-700 dark:text-gray-300 mb-2">Discount Price</label>
                <input id="discount_price" type="number" wire:model="discount_price" placeholder="Optional"
                       class="w-full px-4 py-3 rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm transition" />
                @error('discount_price') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="md:col-span-2">
                <label for="currency" class="block font-semibold text-gray-700 dark:text-gray-300 mb-2">Currency</label>
                <select id="currency" wire:model="currency"
                        class="w-full px-4 py-3 rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm transition">
                    <option value="USD">USD</option>
                    <option value="EUR">EUR</option>
                    <option value="GBP">GBP</option>
                    <option value="KES">KES</option>
                </select>
                @error('currency') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <!-- Step 3: Duration & Features -->
    <div x-show="currentStep === 2" x-transition.opacity.duration.500ms>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="duration_days" class="block font-semibold text-gray-700 dark:text-gray-300 mb-2">Duration (Days)</label>
                <input id="duration_days" type="number" wire:model="duration_days" placeholder="e.g., 7"
                       class="w-full px-4 py-3 rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm transition" />
                @error('duration_days') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="duration_nights" class="block font-semibold text-gray-700 dark:text-gray-300 mb-2">Duration (Nights)</label>
                <input id="duration_nights" type="number" wire:model="duration_nights" placeholder="e.g., 6"
                       class="w-full px-4 py-3 rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm transition" />
                @error('duration_nights') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="md:col-span-2">
                <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-2">Inclusions</label>
                <div class="space-y-4">
                    <template x-for="(inclusion, index) in inclusions" :key="index">
                        <div class="flex items-center space-x-2">
                            <input type="text" x-model="inclusions[index]" placeholder="e.g., Airport transfers"
                                   class="flex-1 px-4 py-3 rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm transition" />
                            <button type="button" @click="removeInclusion(index)"
                                    class="p-2 text-red-600 hover:text-red-800 transition">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    </template>
                    <button type="button" @click="addInclusion"
                            class="w-full flex items-center justify-center px-4 py-2 border border-dashed border-gray-400 dark:border-gray-600 text-gray-600 dark:text-gray-400 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        <svg class="w-5 h-5 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Add Inclusion
                    </button>
                </div>
            </div>
            <div class="md:col-span-2">
                <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-2">Exclusions</label>
                <div class="space-y-4">
                    <template x-for="(exclusion, index) in exclusions" :key="index">
                        <div class="flex items-center space-x-2">
                            <input type="text" x-model="exclusions[index]" placeholder="e.g., Visa fees"
                                   class="flex-1 px-4 py-3 rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm transition" />
                            <button type="button" @click="removeExclusion(index)"
                                    class="p-2 text-red-600 hover:text-red-800 transition">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    </template>
                    <button type="button" @click="addExclusion"
                            class="w-full flex items-center justify-center px-4 py-2 border border-dashed border-gray-400 dark:border-gray-600 text-gray-600 dark:text-gray-400 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        <svg class="w-5 h-5 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Add Exclusion
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 4: Media -->
    <div x-show="currentStep === 3" x-transition.opacity.duration.500ms>
        <div class="space-y-6">
            <div>
                <label for="cover_image" class="block font-semibold text-gray-700 dark:text-gray-300 mb-2">Cover Image</label>
                <input id="cover_image" type="file" wire:model="cover_image" class="w-full text-gray-600 dark:text-gray-400" />
                <div wire:loading wire:target="cover_image" class="text-indigo-600 dark:text-indigo-400 mt-2">Uploading...</div>
                @if ($cover_image)
                    <img src="{{ $cover_image->temporaryUrl() }}"
                         class="mt-4 w-full h-40 object-cover rounded-xl shadow-md border border-gray-200 dark:border-gray-700" />
                @endif
            </div>
            <div>
                <label for="gallery" class="block font-semibold text-gray-700 dark:text-gray-300 mb-2">Gallery Images</label>
                <input id="gallery" type="file" wire:model="gallery" multiple class="w-full text-gray-600 dark:text-gray-400" />
                <div wire:loading wire:target="gallery" class="text-indigo-600 dark:text-indigo-400 mt-2">Uploading...</div>
                <div class="mt-4 flex gap-4 flex-wrap">
                    @foreach ($gallery as $image)
                        <img src="{{ $image->temporaryUrl() }}"
                             class="w-24 h-20 object-cover rounded-xl shadow-sm border border-gray-200 dark:border-gray-700" />
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Step 5: Itinerary -->
    <div x-show="currentStep === 4" x-transition.opacity.duration.500ms>
        <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-2">Itinerary</label>
        <div class="space-y-6">
            <template x-for="(day, index) in itinerary" :key="index">
                <div class="relative p-6 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 transition-colors">
                    <button type="button" @click="removeItineraryDay(index)"
                            class="absolute top-2 right-2 p-1 text-red-600 hover:text-red-800 transition">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                    <h4 class="text-lg font-bold text-indigo-600 dark:text-indigo-400 mb-2">Day <span x-text="index + 1"></span></h4>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title</label>
                            <input type="text" x-model="day.title" placeholder="e.g., Arrive and Settle In"
                                   class="w-full px-4 py-3 rounded-xl border-gray-300 dark:bg-gray-800 dark:border-gray-600 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm transition" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                            <textarea x-model="day.description" placeholder="Details for the day's activities..." rows="3"
                                      class="w-full px-4 py-3 rounded-xl border-gray-300 dark:bg-gray-800 dark:border-gray-600 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm transition"></textarea>
                        </div>
                    </div>
                </div>
            </template>
            <button type="button" @click="addItineraryDay"
                    class="w-full flex items-center justify-center px-4 py-2 border border-dashed border-gray-400 dark:border-gray-600 text-gray-600 dark:text-gray-400 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                <svg class="w-5 h-5 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Add Day to Itinerary
            </button>
        </div>
    </div>

    <!-- Step 6: Availability & Status -->
    <div x-show="currentStep === 5" x-transition.opacity.duration.500ms>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="available_from" class="block font-semibold text-gray-700 dark:text-gray-300 mb-2">Available From</label>
                <input id="available_from" type="date" wire:model="available_from"
                       class="w-full px-4 py-3 rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm transition" />
            </div>
            <div>
                <label for="available_to" class="block font-semibold text-gray-700 dark:text-gray-300 mb-2">Available To</label>
                <input id="available_to" type="date" wire:model="available_to"
                       class="w-full px-4 py-3 rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm transition" />
            </div>
            <div class="flex items-center mt-4">
                <input id="is_featured" type="checkbox" wire:model="is_featured"
                       class="h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 transition" />
                <label for="is_featured" class="ml-2 block font-semibold text-gray-700 dark:text-gray-300">Featured Package</label>
            </div>
            <div class="flex items-center mt-4">
                <input id="active" type="checkbox" wire:model="active"
                       class="h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 transition" />
                <label for="active" class="ml-2 block font-semibold text-gray-700 dark:text-gray-300">Active Package</label>
            </div>
        </div>
    </div>

    <!-- Navigation Buttons -->
    <div class="flex justify-between mt-8">
        <button type="button" @click="prevStep" x-show="currentStep > 0"
                class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-full shadow-md hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
            Back
        </button>

        <button type="button" @click="nextStep" x-show="currentStep < steps.length - 1"
                class="ml-auto px-6 py-3 bg-indigo-600 text-white font-medium rounded-full shadow-lg hover:bg-indigo-700 transition transform hover:-translate-y-1">
            Next
        </button>

        <button type="submit" x-show="currentStep === steps.length - 1"
                class="ml-auto px-6 py-3 bg-green-600 text-white font-medium rounded-full shadow-lg hover:bg-green-700 transition transform hover:-translate-y-1">
            Create Package
        </button>
    </div>
</form>

</div>

<script>
function packageForm() {
return {
currentStep: 0,
steps: ['Details', 'Pricing', 'Features', 'Media', 'Itinerary', 'Status'],
inclusions: @entangle('inclusions'),
exclusions: @entangle('exclusions'),
itinerary: @entangle('itinerary'),
validationRules: {
    0: ['name', 'destination', 'short_description', 'full_description'],
    1: ['base_price', 'currency'],
    2: ['duration_days'], // you can extend with inclusions/exclusions if mandatory
    3: ['cover_image'],   // optional if you want
    4: [],                // itinerary optional
    5: []                 // availability optional
},
init() {
// Initialize dynamic arrays if they are empty
if (this.inclusions === null) this.inclusions = [''];
if (this.exclusions === null) this.exclusions = [''];
if (this.itinerary === null) this.itinerary = [{ title: '', description: '' }];

},
nextStep() {
    let requiredFields = this.validationRules[this.currentStep] || [];
    let isValid = true;

    requiredFields.forEach(field => {
        let value = @this.get(field);
        if (!isValid) {
        @this.call('validateStep', this.currentStep);
        return;
        }



    });

    if (isValid && this.currentStep < this.steps.length - 1) {
        this.currentStep++;
    }
},


prevStep() {
if (this.currentStep > 0) {
this.currentStep--;
}
},
addInclusion() {
this.inclusions.push('');
},
removeInclusion(index) {
this.inclusions.splice(index, 1);
},
addExclusion() {
this.exclusions.push('');
},
removeExclusion(index) {
this.exclusions.splice(index, 1);
},
addItineraryDay() {
this.itinerary.push({ title: '', description: '' });
},
removeItineraryDay(index) {
this.itinerary.splice(index, 1);
}
}
}
</script>
</div>
