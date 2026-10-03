<div class="mx-auto max-w-6xl space-y-7 px-4 py-8 sm:px-6">
    <header class="rounded-2xl bg-gradient-to-br from-indigo-950 to-indigo-700 p-7 text-white sm:p-10">
        <p class="text-sm font-semibold uppercase tracking-widest text-indigo-200">Provider media</p>
        <h1 class="mt-2 text-3xl font-bold sm:text-4xl">{{ $provider->company_name }}</h1>
        @if ($provider->city || $provider->country)
            <p class="mt-2 text-indigo-100">{{ $provider->city }}@if($provider->city && $provider->country), @endif{{ $provider->country }}</p>
        @endif
        @if ($provider->bio)<p class="mt-4 max-w-3xl text-indigo-50">{{ $provider->bio }}</p>@endif
    </header>

    <nav class="flex flex-wrap gap-2" aria-label="Filter provider media">
        @foreach (['all' => 'All media', 'reel' => 'Reels', 'video' => 'Videos'] as $value => $label)
            <button type="button" wire:click="setFilter('{{ $value }}')" @class([
                'rounded-full px-4 py-2 text-sm font-semibold',
                'bg-indigo-700 text-white' => $filter === $value,
                'bg-white text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50' => $filter !== $value,
            ])>
                {{ $label }}
            </button>
        @endforeach
    </nav>

    <section class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($posts as $post)
            @php
                $original = $post->asset(\App\Modules\Media\Domain\Enums\MediaAssetType::Original);
                $thumbnail = $post->asset(\App\Modules\Media\Domain\Enums\MediaAssetType::Thumbnail);
            @endphp
            <article class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                @if ($original)
                    <video class="aspect-[9/14] w-full bg-black object-cover" controls playsinline preload="metadata" @if($thumbnail) poster="{{ $thumbnail->url }}" @endif>
                        <source src="{{ $original->url }}" type="{{ $original->mime_type }}">
                    </video>
                @endif
                <div class="space-y-2 p-4">
                    <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold uppercase text-indigo-700">{{ $post->type->value }}</span>
                    @if ($post->title)<h2 class="pt-2 font-semibold text-gray-900">{{ $post->title }}</h2>@endif
                    @if ($post->caption)<p class="text-sm text-gray-600">{{ $post->caption }}</p>@endif
                </div>
            </article>
        @empty
            <p class="rounded-xl bg-gray-50 p-8 text-gray-600 sm:col-span-2 lg:col-span-3">No published media matches this filter yet.</p>
        @endforelse
    </section>

    <div>{{ $posts->links() }}</div>
</div>
