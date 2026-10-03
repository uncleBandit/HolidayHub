<div class="mx-auto max-w-3xl px-3 py-5 sm:px-6">
    <header class="mb-4 flex items-end justify-between gap-4">
        <div>
            <p class="text-sm font-semibold uppercase tracking-widest text-indigo-600">Explore HolidayHub</p>
            <h1 class="mt-1 text-3xl font-bold text-gray-900">Reels</h1>
        </div>
        @guest
            <a href="{{ route('login') }}" class="text-sm font-semibold text-indigo-700 hover:text-indigo-900">Provider? Sign in to post</a>
        @else
            @if (auth()->user()->provider)
                <a href="{{ route('media.provider.media') }}" class="text-sm font-semibold text-indigo-700 hover:text-indigo-900">Manage your media</a>
            @endif
        @endguest
    </header>

    <div class="max-h-[78vh] snap-y snap-mandatory space-y-4 overflow-y-auto rounded-2xl" aria-label="Reel stream">
        @forelse ($posts as $post)
            @php
                $original = $post->asset(\App\Modules\Media\Domain\Enums\MediaAssetType::Original);
                $thumbnail = $post->asset(\App\Modules\Media\Domain\Enums\MediaAssetType::Thumbnail);
            @endphp
            <article wire:key="reel-{{ $post->id }}" class="relative flex min-h-[70vh] snap-start items-end overflow-hidden rounded-2xl bg-gray-950 sm:min-h-[76vh]">
                @if ($original)
                    <video class="absolute inset-0 h-full w-full object-cover" controls playsinline preload="none" @if($thumbnail) poster="{{ $thumbnail->url }}" @endif>
                        <source src="{{ $original->url }}" type="{{ $original->mime_type }}">
                    </video>
                @endif
                <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-black/10"></div>
                <div class="relative z-10 w-full space-y-3 p-5 text-white sm:p-7">
                    <a href="{{ route('media.providers.media', $post->provider) }}" class="pointer-events-auto inline-flex items-center gap-2 rounded-full bg-black/40 px-3 py-2 font-semibold backdrop-blur hover:bg-black/60">
                        <span>{{ $post->provider->company_name }}</span>
                        @if ($post->provider->is_verified)<span aria-label="Verified provider">✓</span>@endif
                    </a>
                    @if ($post->title)<h2 class="text-2xl font-bold">{{ $post->title }}</h2>@endif
                    @if ($post->caption)<p class="max-w-xl text-sm leading-relaxed text-white/90">{{ $post->caption }}</p>@endif
                    @if ($post->provider->city)
                        <p class="text-sm text-white/80">{{ $post->provider->city }}@if($post->provider->country), {{ $post->provider->country }}@endif</p>
                    @endif
                    <a href="{{ route('media.providers.media', $post->provider) }}" class="pointer-events-auto inline-flex rounded-lg bg-white px-4 py-2 text-sm font-bold text-gray-900 hover:bg-indigo-50">
                        View provider
                    </a>
                </div>
            </article>
        @empty
            <div class="rounded-2xl bg-gray-50 p-10 text-center">
                <h2 class="text-xl font-semibold text-gray-900">No reels yet</h2>
                <p class="mt-2 text-gray-600">Approved provider reels will show up here.</p>
            </div>
        @endforelse
    </div>

    @if ($hasMore)
        <div class="py-5 text-center">
            <button type="button" wire:click="loadMore" wire:loading.attr="disabled" class="rounded-lg bg-indigo-600 px-5 py-2.5 font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">
                Load more reels
            </button>
        </div>
    @endif
</div>
