<div>
<section class="relative py-24 overflow-hidden">
    <div class="absolute inset-0 bg-gray-50 -z-10 opacity-70"></div>
    <div class="absolute inset-0 z-0 bg-[url('/img/pattern.svg')] opacity-10 pointer-events-none"></div>

    <div class="container mx-auto px-6 max-w-7xl">
        <h2 class="text-5xl font-extrabold text-center tracking-tight text-gray-900 mb-4">
            Hear It from Our Travelers
        </h2>
        <p class="text-lg text-center text-gray-600 mb-16 max-w-2xl mx-auto">
            Real stories from real people who discovered their perfect getaway with us.
        </p>

        <div class="relative w-full overflow-hidden">
            <div class="hidden md:grid md:grid-cols-3 gap-8">
                @forelse($testimonials as $testimonial)
                    <div class="bg-white p-8 rounded-3xl shadow-2xl border border-gray-100 transform hover:scale-[1.02] transition-all duration-300 relative group">
                        <div class="absolute top-8 right-8 text-gray-200 group-hover:text-amber-400 transition-colors duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="w-16 h-16 opacity-60" viewBox="0 0 24 24">
                                <path d="M9.25 21a1.5 1.5 0 0 1-1.5-1.5V15.5a1.5 1.5 0 0 1 1.5-1.5h2a1.5 1.5 0 0 1 1.5 1.5v3a1.5 1.5 0 0 1-1.5 1.5h-2ZM16.25 21a1.5 1.5 0 0 1-1.5-1.5V15.5a1.5 1.5 0 0 1 1.5-1.5h2a1.5 1.5 0 0 1 1.5 1.5v3a1.5 1.5 0 0 1-1.5 1.5h-2Z" />
                                <path d="M12.5 12.052a.75.75 0 0 0 .15-.705A4.5 4.5 0 0 0 9.5 5.25a.75.75 0 0 0-1.5 0A6 6 0 0 1 12.052 11h-1.552a.75.75 0 0 0-.75.75v5.5a.75.75 0 0 0 1.5 0v-5a.75.75 0 0 1 .75-.75ZM20.5 12.052a.75.75 0 0 0 .15-.705A4.5 4.5 0 0 0 17.5 5.25a.75.75 0 0 0-1.5 0A6 6 0 0 1 20.052 11h-1.552a.75.75 0 0 0-.75.75v5.5a.75.75 0 0 0 1.5 0v-5a.75.75 0 0 1 .75-.75Z" />
                            </svg>
                        </div>

                        <div class="flex items-center mb-6">
                            @if(optional($testimonial->guest?->user)->avatar)
                                <img src="{{ $testimonial->guest->user->avatar }}"
                                     alt="{{ $testimonial->guest->user->name ?? 'Guest' }}"
                                     class="w-16 h-16 rounded-full mr-4 object-cover border-4 border-white ring-2 ring-gray-200">
                            @endif
                            <div>
                                <h3 class="font-bold text-lg text-gray-800">
                                    {{ $testimonial->guest->user->name ?? 'Anonymous Traveler' }}
                                </h3>
                                @if($testimonial->location)
                                    <p class="text-sm text-gray-500">{{ $testimonial->location }}</p>
                                @endif
                            </div>
                        </div>

                        <p class="text-gray-700 italic mb-4 leading-relaxed">“{{ $testimonial->comment }}”</p>

                        <div class="flex text-amber-400">
                            @for($i = 0; $i < $testimonial->rating; $i++)
                                <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="w-5 h-5 mr-1" viewBox="0 0 24 24">
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
    </div>
</section>
</div>
