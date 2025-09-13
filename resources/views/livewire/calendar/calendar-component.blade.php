@php
    use Carbon\Carbon;
@endphp
<div>
<div class="p-6 bg-white rounded-3xl shadow-xl border border-gray-100 font-inter">
    <!-- Calendar Header and Navigation -->
    <div class="flex items-center justify-between mb-6">
        <button wire:click="previousMonth" class="p-2 -ml-2 text-gray-400 rounded-full hover:bg-gray-100 hover:text-gray-600 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
        </button>
        <h2 class="text-xl font-bold text-gray-900">{{ $this->currentMonth->format('F Y') }}</h2>
        <button wire:click="nextMonth" class="p-2 -mr-2 text-gray-400 rounded-full hover:bg-gray-100 hover:text-gray-600 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
        </button>
    </div>

    <!-- Days of the week header -->
    <div class="grid grid-cols-7 text-center font-semibold text-sm text-gray-500 mb-2">
        @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)
            <span class="py-2">{{ $day }}</span>
        @endforeach
    </div>

    <!-- The main calendar grid -->
    <div class="grid grid-cols-7 gap-1">
        @foreach($calendarDays as $day)
            <div
                class="relative h-16 sm:h-20 md:h-24 lg:h-28 flex flex-col items-center justify-center p-1 rounded-lg transition-all duration-200 ease-in-out cursor-pointer group
                @if(!$day['is_bookable'])
                    bg-gray-100 text-gray-400 opacity-60 cursor-not-allowed
                @else
                    hover:bg-blue-50 hover:border-blue-300
                    @if($day['is_bookable'] && !$day['is_available'])
                        bg-red-100 text-red-600 cursor-not-allowed
                    @endif
                    @if($checkinDate && $day['date_string'] === $checkinDate->toDateString())
                        bg-blue-500 text-white shadow-md transform scale-105 z-10
                    @elseif($checkoutDate && $day['date_string'] === $checkoutDate->toDateString())
                        bg-blue-500 text-white shadow-md transform scale-105 z-10
                    @elseif($checkinDate && $checkoutDate && (Carbon::parse($day['date_string'])->between($checkinDate, $checkoutDate)))
                        bg-blue-100 text-blue-800
                    @else
                        bg-white text-gray-800 border border-gray-200 hover:bg-blue-50
                    @endif
                @endif
                "
                @if($day['is_bookable'])
                    wire:click="selectDate('{{ $day['date_string'] }}')"
                @endif
            >
                <!-- Day Number -->
                <span class="font-bold text-lg md:text-xl">{{ $day['day_number'] }}</span>
                <!-- Price -->
                @if($day['is_available'])
                    <span class="text-xs font-semibold text-green-600 mt-1">${{ number_format($day['price'], 0) }}</span>
                @endif

                <!-- Today marker -->
                @if($day['date_string'] === now()->toDateString())
                    <span class="absolute top-1 right-1 h-2 w-2 bg-blue-500 rounded-full"></span>
                @endif
            </div>
        @endforeach
    </div>

    <!-- Selected dates and book button -->
    @if ($checkin && $checkout)
        <div class="mt-8 p-4 bg-blue-50 border-l-4 border-blue-500 rounded-lg shadow-inner">
            <p class="text-blue-800 font-semibold mb-2">Selected stay:
                <span class="text-gray-900 font-bold">{{ Carbon::parse($checkin)->format('M d') }}</span> to
                <span class="text-gray-900 font-bold">{{ Carbon::parse($checkout)->format('M d') }}</span>
            </p>
            <p class="text-gray-600 text-sm">You have selected a stay of <span class="font-bold">{{ Carbon::parse($checkin)->diffInDays(Carbon::parse($checkout)) }}</span> nights.</p>
            <button wire:click="book" class="w-full mt-4 py-3 px-6 bg-blue-600 text-white font-bold rounded-full shadow-lg hover:bg-blue-700 transition-colors">
                Book Now
            </button>
        </div>
    @endif

    <!-- Error Messages -->
    <div class="mt-4">
        @error('stay')
            <div class="p-3 mb-2 text-sm font-medium text-red-700 bg-red-100 rounded-lg">
                {{ $message }}
            </div>
        @enderror
        @error('dates')
            <div class="p-3 mb-2 text-sm font-medium text-red-700 bg-red-100 rounded-lg">
                {{ $message }}
            </div>
        @enderror
    </div>
</div>

<script src="https://cdn.tailwindcss.com"></script>

</div>
