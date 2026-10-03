<main class="min-h-screen bg-slate-50">
    @php($cover = $activity->thumbnail ?: ($activity->getImages()[0] ?? 'https://placehold.co/1600x900?text=Activity'))

    <section class="relative isolate min-h-[420px] overflow-hidden bg-slate-900 sm:min-h-[560px]">
        <img src="{{ $cover }}" alt="{{ $activity->name }}" class="absolute inset-0 h-full w-full object-cover opacity-75">
        <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/30 to-transparent"></div>
        <div class="relative mx-auto flex min-h-[420px] max-w-7xl flex-col justify-end px-4 py-12 text-white sm:min-h-[560px] sm:px-6 lg:px-8">
            @if($activity->category)
                <span class="mb-4 w-fit rounded-full bg-white/15 px-4 py-1.5 text-sm font-medium backdrop-blur">{{ $activity->category->name }}</span>
            @endif
            <h1 class="max-w-4xl text-4xl font-bold tracking-tight sm:text-6xl">{{ $activity->name }}</h1>
            <p class="mt-4 flex items-center gap-2 text-lg text-white/85">
                <span>{{ $activity->destination?->name }}</span>
                <span aria-hidden="true">·</span>
                <span>{{ $activity->duration_minutes }} minutes</span>
                <span aria-hidden="true">·</span>
                <span>★ {{ number_format((float) $activity->rating, 1) }} ({{ $activity->reviews_count }} reviews)</span>
            </p>
        </div>
    </section>

    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-[minmax(0,1fr)_360px] lg:px-8">
        <div class="space-y-8">
            <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
                <h2 class="text-2xl font-semibold text-slate-900">About this experience</h2>
                @if($activity->short_description)
                    <p class="mt-3 text-lg text-slate-700">{{ $activity->short_description }}</p>
                @endif
                <p class="mt-4 whitespace-pre-line leading-7 text-slate-600">{{ $activity->description }}</p>
                @if($activity->highlights)
                    <ul class="mt-6 grid gap-2 sm:grid-cols-2">
                        @foreach($activity->highlights as $highlight)
                            <li class="flex gap-2 text-sm text-slate-700"><span class="text-teal-700">✓</span>{{ $highlight }}</li>
                        @endforeach
                    </ul>
                @endif
            </section>

            @if($activity->mediaPosts->isNotEmpty())
                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
                    <h2 class="text-2xl font-semibold text-slate-900">Watch this experience</h2>
                    <div class="mt-5 grid gap-5 sm:grid-cols-2">
                        @foreach($activity->mediaPosts->where('provider_id', $activity->provider_id) as $post)
                            @php($video = $post->asset(\App\Modules\Media\Domain\Enums\MediaAssetType::Original))
                            @if($video)
                                <article class="overflow-hidden rounded-xl bg-slate-950">
                                    <video class="aspect-[9/14] max-h-[560px] w-full bg-black object-contain" controls playsinline preload="metadata">
                                        <source src="{{ $video->url }}" type="{{ $video->mime_type }}">
                                    </video>
                                    @if($post->title || $post->caption)
                                        <div class="space-y-1 p-4 text-white">
                                            @if($post->title)<h3 class="font-semibold">{{ $post->title }}</h3>@endif
                                            @if($post->caption)<p class="text-sm text-white/75">{{ $post->caption }}</p>@endif
                                        </div>
                                    @endif
                                </article>
                            @endif
                        @endforeach
                    </div>
                </section>
            @endif

            @if($activity->inclusions || $activity->exclusions)
                <section class="grid gap-5 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:grid-cols-2 sm:p-8">
                    @foreach(['Included' => $activity->inclusions ?? [], 'Not included' => $activity->exclusions ?? []] as $heading => $items)
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">{{ $heading }}</h2>
                            <ul class="mt-3 space-y-2">
                                @forelse($items as $item)
                                    <li class="text-sm text-slate-600">{{ $item }}</li>
                                @empty
                                    <li class="text-sm text-slate-400">Not specified</li>
                                @endforelse
                            </ul>
                        </div>
                    @endforeach
                </section>
            @endif

            @if($activity->itinerary->isNotEmpty())
                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
                    <h2 class="text-2xl font-semibold text-slate-900">Itinerary</h2>
                    <ol class="mt-5 space-y-5">
                        @foreach($activity->itinerary as $item)
                            <li class="flex gap-4">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-teal-50 text-sm font-semibold text-teal-800">{{ $item->sequence }}</span>
                                <div>
                                    <h3 class="font-medium text-slate-900">{{ $item->title }}</h3>
                                    @if($item->description)<p class="mt-1 text-sm text-slate-600">{{ $item->description }}</p>@endif
                                    @if($item->duration_minutes)<p class="mt-1 text-xs text-slate-500">{{ $item->duration_minutes }} minutes</p>@endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif

            @if($activity->locations->isNotEmpty() || $activity->requirements->isNotEmpty())
                <section class="grid gap-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:grid-cols-2 sm:p-8">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Meeting and pickup</h2>
                        <ul class="mt-3 space-y-4">
                            @forelse($activity->locations as $location)
                                <li>
                                    <p class="font-medium text-slate-800">{{ $location->name }}</p>
                                    <p class="text-sm text-slate-600">{{ $location->address }}</p>
                                    @if($location->instructions)<p class="mt-1 text-sm text-slate-500">{{ $location->instructions }}</p>@endif
                                </li>
                            @empty
                                <li class="text-sm text-slate-400">Meeting details provided after booking.</li>
                            @endforelse
                        </ul>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Before you go</h2>
                        <ul class="mt-3 space-y-3">
                            @forelse($activity->requirements as $requirement)
                                <li class="text-sm text-slate-600">
                                    <span class="font-medium text-slate-800">{{ $requirement->title }}</span>
                                    @if($requirement->required)<span class="text-xs text-teal-700">Required</span>@endif
                                    @if($requirement->description)<p class="mt-1">{{ $requirement->description }}</p>@endif
                                </li>
                            @empty
                                @if($activity->safety_instructions)<li class="text-sm text-slate-600">{{ $activity->safety_instructions }}</li>@else<li class="text-sm text-slate-400">No additional requirements listed.</li>@endif
                            @endforelse
                        </ul>
                    </div>
                </section>
            @endif

            <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
                <h2 class="text-2xl font-semibold text-slate-900">Guest reviews</h2>
                <div class="mt-5 divide-y divide-slate-200">
                    @forelse($reviews as $review)
                        <article class="py-5 first:pt-0">
                            <div class="flex items-center justify-between gap-4">
                                <p class="font-medium text-slate-900">{{ $review['user_name'] }}</p>
                                <span class="text-sm text-amber-700">★ {{ $review['rating'] }}/5</span>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">{{ $review['created_at'] }}</p>
                            @if($review['comment'])<p class="mt-3 text-sm leading-6 text-slate-700">{{ $review['comment'] }}</p>@endif
                        </article>
                    @empty
                        <p class="py-5 text-sm text-slate-500">No published reviews yet.</p>
                    @endforelse
                </div>
                @if($hasMoreReviews)
                    <button type="button" wire:click="loadMoreReviews" class="mt-4 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Load more reviews
                    </button>
                @endif
            </section>
        </div>

        <aside class="h-fit rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 lg:sticky lg:top-8">
            <p class="text-sm text-slate-500">From</p>
            <p class="mt-1 text-3xl font-bold text-slate-900">{{ $activity->currency }} {{ number_format((float) $activity->base_price, 2) }} <span class="text-sm font-normal text-slate-500">per person</span></p>
            @if($activity->options->isNotEmpty())
                <div class="mt-5">
                    <h2 class="text-sm font-semibold text-slate-900">Available options</h2>
                    <ul class="mt-2 space-y-2">
                        @foreach($activity->options->where('is_active', true) as $option)
                            <li class="flex justify-between gap-3 text-sm text-slate-600">
                                <span>{{ $option->name }}</span>
                                <span class="font-medium text-slate-800">{{ $option->currency }} {{ number_format((float) $option->base_price, 2) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form wire:submit="bookNow" class="mt-6 space-y-4">
                <label class="block text-sm font-medium text-slate-700">
                    Choose a session
                    <select wire:model="selectedSessionId" required class="mt-1 w-full rounded-lg border-slate-300">
                        <option value="">Select a date and time</option>
                        @foreach($availability as $session)
                            <option value="{{ $session['id'] }}">
                                {{ \Carbon\Carbon::parse($session['starts_at'])->setTimezone($session['timezone'])->format('D, M j · g:i A') }}
                                — {{ $session['slots'] }} places left
                            </option>
                        @endforeach
                    </select>
                    @error('selectedSessionId')<span class="mt-1 block text-sm text-red-700">{{ $message }}</span>@enderror
                    @error('session')<span class="mt-1 block text-sm text-red-700">{{ $message }}</span>@enderror
                </label>
                <label class="block text-sm font-medium text-slate-700">
                    Participants
                    <input wire:model="participants" type="number" min="1" max="1000" required class="mt-1 w-full rounded-lg border-slate-300">
                    @error('participants')<span class="mt-1 block text-sm text-red-700">{{ $message }}</span>@enderror
                </label>
                @error('guest')<p role="alert" class="text-sm text-red-700">{{ $message }}</p>@enderror
                <button type="submit" @disabled($availability === []) class="w-full rounded-xl bg-teal-700 px-5 py-3 font-semibold text-white transition hover:bg-teal-800 disabled:cursor-not-allowed disabled:bg-slate-400">
                    Continue to booking
                </button>
                @if($availability === [])
                    <p class="text-center text-sm text-slate-500">No bookable sessions are currently available.</p>
                @endif
            </form>
            <p class="mt-4 text-xs leading-5 text-slate-500">Times are shown in {{ $activity->timezone }}. Availability is confirmed atomically when you book.</p>
        </aside>
    </div>
</main>
