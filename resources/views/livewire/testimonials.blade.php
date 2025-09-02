<div>
<section class="py-20 bg-gradient-to-b from-sea-foam to-white">
    <div class="container mx-auto px-6">
        <h2 class="text-4xl font-bold text-center mb-12 text-deep-ocean">
            What Our Travelers Say
        </h2>

        <div class="grid md:grid-cols-3 gap-8">
            @forelse($testimonials as $testimonial)
                <div class="bg-white p-8 rounded-3xl shadow-xl transform hover:scale-[1.03] transition-all duration-300">
                    <!-- Quote icon at the top of the card -->
                    <div class="mb-4 text-sunset-orange">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="w-12 h-12 opacity-30" viewBox="0 0 24 24">
                            <path d="M9.25 21a1.5 1.5 0 0 1-1.5-1.5V15.5a1.5 1.5 0 0 1 1.5-1.5h2a1.5 1.5 0 0 1 1.5 1.5v3a1.5 1.5 0 0 1-1.5 1.5h-2ZM16.25 21a1.5 1.5 0 0 1-1.5-1.5V15.5a1.5 1.5 0 0 1 1.5-1.5h2a1.5 1.5 0 0 1 1.5 1.5v3a1.5 1.5 0 0 1-1.5 1.5h-2Z" />
                            <path d="M12.5 12.052a.75.75 0 0 0 .15-.705A4.5 4.5 0 0 0 9.5 5.25a.75.75 0 0 0-1.5 0A6 6 0 0 1 12.052 11h-1.552a.75.75 0 0 0-.75.75v5.5a.75.75 0 0 0 1.5 0v-5a.75.75 0 0 1 .75-.75ZM20.5 12.052a.75.75 0 0 0 .15-.705A4.5 4.5 0 0 0 17.5 5.25a.75.75 0 0 0-1.5 0A6 6 0 0 1 20.052 11h-1.552a.75.75 0 0 0-.75.75v5.5a.75.75 0 0 0 1.5 0v-5a.75.75 0 0 1 .75-.75Z" />
                        </svg>
                    </div>

                    <div class="flex items-center mb-4">
    @if(optional($testimonial->guest?->user)->avatar)
        <img src="{{ $testimonial->guest->user->avatar }}"
             alt="{{ $testimonial->guest->user->name ?? 'Guest' }}"
             class="w-16 h-16 rounded-full mr-4 object-cover border-4 border-sunset-orange ring-1 ring-deep-ocean/20">
    @endif

    <div>
        <h3 class="font-bold text-lg text-tropical-blue">
            {{ $testimonial->guest->user->name ?? 'Anonymous Traveler' }}
        </h3>
        @if($testimonial->location)
            <p class="text-gray-600 text-sm">{{ $testimonial->location }}</p>
        @endif
    </div>
</div>


                    <p class="text-gray-800 italic mb-4">“{{ $testimonial->message }}”</p>

                    <div class="flex text-sunset-orange">
                        <!-- Star Rating using an icon instead of text -->
                        @for($i = 0; $i < $testimonial->rating; $i++)
                            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="w-5 h-5" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M10.788 3.212a.75.75 0 0 1 1.424 0l2.67 5.764a.75.75 0 0 0 .524.382l5.772.585a.75.75 0 0 1 .425 1.354l-4.32 3.82a.75.75 0 0 0-.256.634l1.29 5.617a.75.75 0 0 1-1.091.794L12 18.293l-4.757 2.815a.75.75 0 0 1-1.09-.794l1.29-5.617a.75.75 0 0 0-.257-.634l-4.32-3.82a.75.75 0 0 1 .425-1.354l5.772-.585a.75.75 0 0 0 .524-.382l2.67-5.764Z" clip-rule="evenodd" />
                            </svg>
                        @endfor
                    </div>
                </div>
            @empty
                <p class="text-gray-500 col-span-full text-center">
                    <span class="font-semibold text-lg">No testimonials available yet.</span>
                    <br>
                    Be the first to share your experience!
                </p>
            @endforelse
        </div>
    </div>
</section>

</div>
