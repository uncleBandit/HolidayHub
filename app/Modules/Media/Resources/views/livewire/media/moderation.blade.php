<div class="mx-auto max-w-6xl space-y-6 px-4 py-8 sm:px-6">
    <header>
        <p class="text-sm font-semibold uppercase tracking-widest text-indigo-600">Administration</p>
        <h1 class="mt-2 text-3xl font-bold text-gray-900">Media review queue</h1>
        <p class="mt-2 text-gray-600">Review provider videos before they appear on profiles and in the reel feed.</p>
    </header>

    <div class="space-y-5">
        @forelse ($posts as $post)
            @php
                $original = $post->asset(\App\Modules\Media\Domain\Enums\MediaAssetType::Original);
                $thumbnail = $post->asset(\App\Modules\Media\Domain\Enums\MediaAssetType::Thumbnail);
            @endphp
            <article class="grid gap-5 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200 md:grid-cols-[16rem_1fr]">
                @if ($original)
                    <video class="aspect-[9/14] max-h-[28rem] w-full rounded-xl bg-black object-cover" controls playsinline preload="metadata" @if($thumbnail) poster="{{ $thumbnail->url }}" @endif>
                        <source src="{{ $original->url }}" type="{{ $original->mime_type }}">
                    </video>
                @endif
                <div class="space-y-4">
                    <div>
                        <p class="text-sm font-semibold text-indigo-700">{{ $post->provider->company_name }} · {{ $post->type->value }}</p>
                        @if ($post->title)<h2 class="mt-1 text-xl font-bold text-gray-900">{{ $post->title }}</h2>@endif
                        @if ($post->caption)<p class="mt-2 text-gray-700">{{ $post->caption }}</p>@endif
                        <p class="mt-2 text-xs text-gray-500">Submitted {{ $post->created_at->diffForHumans() }}</p>
                    </div>

                    <button type="button" wire:click="approve({{ $post->id }})" class="rounded-lg bg-green-700 px-4 py-2 font-semibold text-white hover:bg-green-800">
                        Approve and publish
                    </button>

                    <form wire:submit="reject({{ $post->id }})" class="space-y-2 rounded-xl bg-gray-50 p-4">
                        <label for="rejection-reason-{{ $post->id }}" class="block text-sm font-semibold text-gray-800">Reject with a reason</label>
                        <textarea id="rejection-reason-{{ $post->id }}" wire:model="rejectionReason" rows="2" maxlength="1000" class="w-full rounded-lg border-gray-300"></textarea>
                        @error('rejectionReason') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                        <button type="submit" class="rounded-lg bg-red-700 px-4 py-2 font-semibold text-white hover:bg-red-800">Reject</button>
                    </form>
                </div>
            </article>
        @empty
            <p class="rounded-xl bg-gray-50 p-8 text-gray-600">The review queue is empty.</p>
        @endforelse
    </div>

    <div>{{ $posts->links() }}</div>
</div>
