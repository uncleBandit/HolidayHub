<div class="mx-auto max-w-6xl space-y-8 px-4 py-8 sm:px-6">
    <header class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-semibold uppercase tracking-widest text-indigo-600">Provider studio</p>
            <h1 class="mt-2 text-3xl font-bold text-gray-900">Videos and reels</h1>
            <p class="mt-2 text-gray-600">Upload a video to showcase your business. Public posts appear after review.</p>
        </div>
        <a href="{{ route('media.reels') }}" class="rounded-lg border border-gray-300 px-4 py-2 font-semibold text-gray-700 hover:bg-gray-50">
            Browse reels
        </a>
    </header>

    @if (session('status'))
        <div role="status" class="rounded-lg bg-green-50 px-4 py-3 text-green-800">{{ session('status') }}</div>
    @endif

    <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
        <h2 class="text-xl font-semibold text-gray-900">Create a post</h2>
        <form wire:submit="publish" class="mt-5 grid gap-5 md:grid-cols-2">
            <div>
                <label for="media-type" class="mb-1 block text-sm font-medium text-gray-700">Format</label>
                <select id="media-type" wire:model="type" class="w-full rounded-lg border-gray-300">
                    <option value="reel">Reel — appears in the reel feed</option>
                    <option value="video">Video — appears on your profile</option>
                </select>
                @error('type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="media-title" class="mb-1 block text-sm font-medium text-gray-700">Title (optional)</label>
                <input id="media-title" type="text" wire:model="title" maxlength="255" class="w-full rounded-lg border-gray-300">
                @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <label for="media-caption" class="mb-1 block text-sm font-medium text-gray-700">Caption</label>
                <textarea id="media-caption" wire:model="caption" rows="3" maxlength="1000" class="w-full rounded-lg border-gray-300"></textarea>
                @error('caption') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="media-video" class="mb-1 block text-sm font-medium text-gray-700">Video file</label>
                <input id="media-video" type="file" wire:model="video" accept="video/mp4,video/webm" class="block w-full text-sm text-gray-700">
                <p class="mt-1 text-xs text-gray-500">MP4 or WebM, up to 10 MB.</p>
                @error('video') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="media-thumbnail" class="mb-1 block text-sm font-medium text-gray-700">Cover image (optional)</label>
                <input id="media-thumbnail" type="file" wire:model="thumbnail" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-gray-700">
                <p class="mt-1 text-xs text-gray-500">JPEG, PNG or WebP, up to 3 MB.</p>
                @error('thumbnail') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center justify-between gap-4 md:col-span-2">
                <span wire:loading wire:target="video,thumbnail,publish" class="text-sm text-gray-500">Uploading or saving…</span>
                <button type="submit" wire:loading.attr="disabled" wire:target="publish" class="ml-auto rounded-lg bg-indigo-600 px-5 py-2.5 font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">
                    Submit for review
                </button>
            </div>
        </form>
    </section>

    <section>
        <h2 class="mb-4 text-xl font-semibold text-gray-900">Your media</h2>
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
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
                    <div class="space-y-3 p-4">
                        <div class="flex items-center justify-between gap-2">
                            <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold uppercase text-indigo-700">{{ $post->type->value }}</span>
                            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">{{ str_replace('_', ' ', $post->status->value) }}</span>
                        </div>
                        @if ($post->title)<h3 class="font-semibold text-gray-900">{{ $post->title }}</h3>@endif
                        @if ($post->caption)<p class="text-sm text-gray-600">{{ $post->caption }}</p>@endif
                        @if ($post->moderation_notes)<p class="text-sm text-red-700">Review note: {{ $post->moderation_notes }}</p>@endif
                        <button type="button" wire:click="deletePost({{ $post->id }})" wire:confirm="Delete this video and its uploaded files?" class="text-sm font-semibold text-red-700 hover:text-red-900">
                            Delete
                        </button>
                    </div>
                </article>
            @empty
                <p class="rounded-xl bg-white p-6 text-gray-600 ring-1 ring-gray-200 sm:col-span-2 lg:col-span-3">You have not uploaded any videos yet.</p>
            @endforelse
        </div>
        <div class="mt-6">{{ $posts->links() }}</div>
    </section>
</div>
