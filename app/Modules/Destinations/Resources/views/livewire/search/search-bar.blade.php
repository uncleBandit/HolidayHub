<div>
<div class="w-full flex justify-center">
    <div
        x-data="{ showDropdown: false, highlightIndex: 0, loading: false }"
        x-effect="
            showDropdown = $wire.query.length > 2 &&
                           Object.values($wire.results).some(r => r.length > 0);
            if (!showDropdown) highlightIndex = 0;
        "
        class="relative w-full max-w-2xl"

    >
        {{-- Search input --}}
        <div class="relative">
            <input
                wire:model.live="query"
                x-on:input="loading = true"
                x-on:keydown.arrow-down.prevent="if (showDropdown) highlightIndex++"
                x-on:keydown.arrow-up.prevent="if (showDropdown && highlightIndex > 0) highlightIndex--"
                x-on:keydown.enter.prevent="$refs.dropdown.querySelectorAll('li')[highlightIndex]?.click()"
                type="text"
                placeholder="🔍 Search hotels, packages, destinations..."
                class="w-full rounded-xl border border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 text-sm px-4 py-3 pl-11 transition"
            />

            {{-- Magnifying glass icon --}}
            <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M21 21l-4.35-4.35M16.65 16.65A7.5 7.5 0 1110 2.5a7.5 7.5 0 016.65 14.15z"/>
                </svg>
            </span>

            {{-- Loading spinner --}}
            <span x-show="loading" x-transition.opacity
                  class="absolute inset-y-0 right-3 flex items-center">
                <svg class="animate-spin h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg"
                     fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10"
                            stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor"
                          d="M4 12a8 8 0 018-8v8z"/>
                </svg>
            </span>
        </div>

        {{-- Results dropdown --}}
        <div
            x-show="showDropdown"
            x-ref="dropdown"
            @click.outside="showDropdown = false"
            @keydown.escape.window="showDropdown = false"
            x-transition
            class="absolute z-50 mt-2 w-full bg-white border border-gray-200 rounded-xl shadow-2xl max-h-80 overflow-y-auto"
        >
            {{-- Hotels --}}
            <template x-if="$wire.results.hotels && $wire.results.hotels.length">
                <div class="p-3 border-b bg-gradient-to-r from-blue-50 to-white">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase mb-1">🏨 Hotels</h3>
                    <ul class="space-y-1">
                        <template x-for="(hotel, i) in $wire.results.hotels" :key="hotel.id ?? i">
                            <li
                                @click="$wire.selectResult('hotel', hotel.id)"
                                :class="{'bg-blue-100 text-blue-800 rounded-lg': i === highlightIndex}"
                                class="px-3 py-2 cursor-pointer hover:bg-blue-50 rounded-lg transition"
                            >
                                <span class="font-medium" x-text="hotel.name ?? 'Unnamed Hotel'"></span>
                            </li>
                        </template>
                    </ul>
                </div>
            </template>


            {{-- Packages --}}
            <template x-if="$wire.results.packages && $wire.results.packages.length">
                <div class="p-3 border-b bg-gradient-to-r from-green-50 to-white">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase mb-1">🎒 Packages</h3>
                    <ul class="space-y-1">
                        <template x-for="(pkg, i) in $wire.results.packages" :key="pkg.id ?? i">
                            <li
                                @click="$wire.selectResult('package', pkg.id)"
                                :class="{'bg-green-100 text-green-800 rounded-lg': i === highlightIndex}"
                                class="px-3 py-2 cursor-pointer hover:bg-green-50 rounded-lg transition"
                            >
                                <span class="font-medium" x-text="pkg.title ?? 'Unnamed Package'"></span>
                            </li>
                        </template>
                    </ul>
                </div>
            </template>


            {{-- Destinations --}}
            <template x-if="$wire.results.destinations && $wire.results.destinations.length">
                <div class="p-3 bg-gradient-to-r from-yellow-50 to-white">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase mb-1">🌍 Destinations</h3>
                    <ul class="space-y-1">
                        <template x-for="(dest, i) in $wire.results.destinations" :key="dest.id ?? i">
                            <li
                                @click="$wire.selectResult('destination', dest.id)"
                                :class="{'bg-yellow-100 text-yellow-800 rounded-lg': i === highlightIndex}"
                                class="px-3 py-2 cursor-pointer hover:bg-yellow-50 rounded-lg transition"
                            >
                                <span class="font-medium" x-text="dest.name ?? 'Unnamed Destination'"></span>
                            </li>
                        </template>
                    </ul>
                </div>
            </template>

            {{-- Bed and Breakfast --}}
            <template x-if="$wire.results.bnbs && $wire.results.bnbs.length">
                <div class="p-3 bg-gradient-to-r from-purple-50 to-white">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase mb-1">🏡 B&B</h3>
                    <ul class="space-y-1">
                        <template x-for="(bnb, i) in $wire.results.bnbs" :key="bnb.id ?? i">
                            <li
                                @click="$wire.selectResult('bnb', bnb.id)"
                                :class="{'bg-purple-100 text-purple-800 rounded-lg': i === highlightIndex}"
                                class="px-3 py-2 cursor-pointer hover:bg-purple-50 rounded-lg transition"
                            >
                                <span class="font-medium" x-text="bnb.name ?? 'Unnamed B&B'"></span>
                            </li>
                        </template>
                    </ul>
                </div>
            </template>


            {{-- Villas --}}
            <template x-if="$wire.results.villas && $wire.results.villas.length">
                <div class="p-3 bg-gradient-to-r from-pink-50 to-white">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase mb-1">🏘 Villas</h3>
                    <ul class="space-y-1">
                        <template x-for="(villa, i) in $wire.results.villas" :key="villa.id ?? i">
                            <li
                                @click="$wire.selectResult('villa', villa.id)"
                                :class="{'bg-pink-100 text-pink-800 rounded-lg': i === highlightIndex}"
                                class="px-3 py-2 cursor-pointer hover:bg-pink-50 rounded-lg transition"
                            >
                                <span class="font-medium" x-text="villa.name ?? 'Unnamed Villa'"></span>
                            </li>
                        </template>
                    </ul>
                </div>
            </template>


            {{-- Activities --}}
            <template x-if="$wire.results.activities && $wire.results.activities.length">
                <div class="p-3 bg-gradient-to-r from-orange-50 to-white">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase mb-1">🎯 Activities</h3>
                    <ul class="space-y-1">
                        <template x-for="(activity, i) in $wire.results.activities" :key="activity.id ?? i">
                            <li
                                @click="$wire.selectResult('activity', activity.id)"
                                :class="{'bg-orange-100 text-orange-800 rounded-lg': i === highlightIndex}"
                                class="px-3 py-2 cursor-pointer hover:bg-orange-50 rounded-lg transition"
                            >
                                <span class="font-medium" x-text="activity.name ?? 'Unnamed Activity'"></span>
                            </li>
                        </template>
                    </ul>
                </div>
            </template>



            {{-- No results --}}
            <template x-if="!Object.values($wire.results).some(r => r.length > 0)">
                <div class="p-4 text-center text-gray-500 text-sm">
                    No results found. Try another search ✈️
                </div>
            </template>
        </div>
    </div>
</div>


</div>
