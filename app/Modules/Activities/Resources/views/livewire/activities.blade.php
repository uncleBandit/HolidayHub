<main class="mx-auto min-h-screen max-w-7xl space-y-8 px-4 py-10 sm:px-6 lg:px-8">
    <header class="space-y-5">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-teal-700">Find your next experience</p>
            <h1 class="mt-2 text-3xl font-bold text-slate-900 sm:text-4xl">Activities and local experiences</h1>
        </div>

        <div class="grid gap-3 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:grid-cols-2 lg:grid-cols-4">
            <label class="sm:col-span-2">
                <span class="sr-only">Search activities</span>
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search tours, food, culture..."
                    class="w-full rounded-xl border-slate-300 focus:border-teal-600 focus:ring-teal-600">
            </label>
            <label>
                <span class="sr-only">Destination</span>
                <select wire:model.live="destinationId" class="w-full rounded-xl border-slate-300 focus:border-teal-600 focus:ring-teal-600">
                    <option value="">All destinations</option>
                    @foreach($destinations as $destination)
                        <option value="{{ $destination->id }}">{{ $destination->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span class="sr-only">Category</span>
                <select wire:model.live="categoryId" class="w-full rounded-xl border-slate-300 focus:border-teal-600 focus:ring-teal-600">
                    <option value="">All categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </label>
        </div>
    </header>

    <div class="flex items-center justify-between">
        <p class="text-sm text-slate-600">{{ $activities->total() }} activities</p>
        <label class="text-sm text-slate-600">
            <span class="sr-only">Sort activities</span>
            <select wire:model.live="sortBy" class="rounded-lg border-slate-300 py-2 focus:border-teal-600 focus:ring-teal-600">
                <option value="rating">Top rated</option>
                <option value="newest">Newest</option>
                <option value="price">Price: low to high</option>
            </select>
        </label>
    </div>

    <section class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($activities as $activity)
            <a href="{{ route('activity.show', $activity->slug) }}" class="group overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-1 hover:shadow-lg">
                <div class="relative aspect-[4/3] bg-slate-100">
                    <img src="{{ $activity->thumbnail ?: ($activity->getImages()[0] ?? 'https://placehold.co/800x600?text=Activity') }}"
                        alt="{{ $activity->name }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                    @if($activity->category)
                        <span class="absolute left-4 top-4 rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-slate-800">{{ $activity->category->name }}</span>
                    @endif
                </div>
                <div class="space-y-3 p-5">
                    <div class="flex items-start justify-between gap-3">
                        <h2 class="text-lg font-semibold text-slate-900">{{ $activity->name }}</h2>
                        <span class="shrink-0 text-sm text-amber-700">★ {{ number_format((float) $activity->rating, 1) }}</span>
                    </div>
                    <p class="line-clamp-2 text-sm text-slate-600">{{ $activity->short_description ?: $activity->description }}</p>
                    <div class="flex items-end justify-between text-sm">
                        <span class="text-slate-500">{{ $activity->destination?->name }} · {{ $activity->duration_minutes }} min</span>
                        <span class="font-semibold text-slate-900">{{ $activity->currency }} {{ number_format((float) $activity->base_price, 2) }}</span>
                    </div>
                </div>
            </a>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-slate-300 p-12 text-center">
                <h2 class="text-lg font-semibold text-slate-900">No activities found</h2>
                <p class="mt-2 text-sm text-slate-600">Try a different search or destination.</p>
            </div>
        @endforelse
    </section>

    {{ $activities->links() }}
</main>
