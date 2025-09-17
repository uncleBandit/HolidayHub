@props(['review'])

<div class="bg-white p-6 rounded-2xl shadow-lg border border-gray-100 transition-all duration-300 transform hover:scale-[1.01]">
    <div class="flex items-start justify-between">
        <div>
            <h3 class="font-bold text-gray-800">{{ $review->guest?->first_name ?? 'Guest' }}</h3>
            <p class="text-sm text-gray-500">{{ $review->created_at->diffForHumans() }}</p>
        </div>
        <div class="flex items-center space-x-1 text-yellow-500 text-lg">
            @for ($i = 0; $i < 5; $i++)
                <i class="fas fa-star {{ $i < $review->rating ? 'text-yellow-500' : 'text-gray-300' }}"></i>
            @endfor
        </div>
    </div>
    <p class="mt-4 text-gray-700 leading-relaxed">{{ $review->comment }}</p>
    @if(!empty($review->photos))
        <div class="flex gap-2 mt-4 overflow-x-auto pb-2">
            @foreach($review->photos as $photo)
                <img src="{{ $photo }}" alt="Review photo" class="w-20 h-20 object-cover rounded-lg shadow-sm hover:opacity-90 transition-opacity cursor-pointer">
            @endforeach
        </div>
    @endif
</div>
