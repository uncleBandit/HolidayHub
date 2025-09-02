<div>
@if($reviews->isNotEmpty())
    <section class="bg-white rounded-3xl shadow-2xl p-6 md:p-10">
        <h2 class="text-3xl font-bold mb-6 text-gray-900">What Travelers Are Saying</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            @foreach($reviews as $review)
                <div class="bg-gray-100 rounded-2xl p-6 shadow-md border border-gray-200">
                    <div class="flex items-center gap-4 mb-4">
                        <img src="{{ $review->user->avatar }}" alt="{{ $review->user->name }}'s avatar" class="w-16 h-16 rounded-full object-cover border-4 border-white shadow-lg">
                        <div>
                            <h4 class="font-bold text-lg text-gray-900">{{ $review->user->name }}</h4>
                            <div class="text-yellow-500 flex items-center">
                                {{-- Star rating component here --}}
                                <span class="ml-1 text-sm text-gray-600">{{ $review->rating }}/5 Rating</span>
                            </div>
                        </div>
                    </div>
                    <p class="text-gray-700 leading-relaxed italic">"{{ $review->comment }}"</p>
                </div>
            @endforeach
        </div>
    </section>
@endif
</div>
