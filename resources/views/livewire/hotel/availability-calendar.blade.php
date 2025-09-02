<div>
<div class="grid grid-cols-7 gap-4 text-center">
    @foreach($availability as $day)
        <div
            class="flex flex-col items-center justify-center p-4 rounded-xl shadow-lg transition-all duration-200 ease-in-out cursor-pointer
                @if($day['is_available'])
                    bg-white text-gray-800 ring-1 ring-green-400 hover:bg-green-50 hover:ring-green-500
                @else
                    bg-gray-100 text-gray-500 line-through cursor-not-allowed
                @endif
            ">
            <div class="font-bold text-lg mb-1">
                {{ \Carbon\Carbon::parse($day['date'])->format('D') }}
            </div>
            <div class="text-3xl font-extrabold leading-none mb-2">
                {{ \Carbon\Carbon::parse($day['date'])->format('j') }}
            </div>
            @if($day['is_available'])
                <div class="text-green-600 font-semibold text-sm">
                    ${{ number_format($day['price'], 0) }}
                </div>
            @else
                <div class="text-sm font-medium text-gray-400">
                    Booked
                </div>
            @endif
        </div>
    @endforeach
</div>
</div>
