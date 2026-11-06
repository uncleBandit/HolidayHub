@php
    use App\Modules\Accommodation\Domain\Models\Accommodation;
    use App\Support\Media;

    $record = $getRecord();
    $owner = $record instanceof Accommodation ? $record->bookable : $record;

    // Gallery entries are plain paths, though older rows may hold {path|url} arrays.
    $pathOf = fn ($item) => is_array($item) ? ($item['path'] ?? $item['url'] ?? null) : $item;

    $cover = $owner ? ($owner->cover_image ?? $owner->thumbnail ?? null) : null;
    $coverFilm = $owner?->cover_video;
    $promoVideo = $owner?->video_url ?? null;

    $gallery = collect(is_array($owner?->gallery) ? $owner->gallery : (json_decode((string) ($owner?->gallery ?? '[]'), true) ?: []))
        ->map($pathOf)
        ->filter()
        ->values();

    $uploaded = $owner && $owner->relationLoaded('images')
        ? $owner->images->map(function ($image) {
            try {
                return $image->url;
            } catch (\Throwable) {
                return Media::url($image->path);
            }
        })->filter()->values()
        : collect();

    $galleryItems = $gallery->concat($uploaded)->unique()->reject(fn ($path) => $path === $cover)->values();
    $photos = $galleryItems->reject(fn ($path) => Media::isVideo($path))->values();
    $films = $galleryItems->filter(fn ($path) => Media::isVideo($path))->values();

    $scenes = $owner && $owner->relationLoaded('scenes') ? $owner->scenes : collect();

    $label = 'font-size:.72rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;margin-bottom:.4rem';
    $frame = 'width:100%;border-radius:.6rem;background:#111;object-fit:cover;display:block';
@endphp

@if (! $owner)
    <p class="text-sm text-danger-600">The listing details could not be found, so there is no media to preview.</p>
@else
    <div style="display:flex;flex-direction:column;gap:1.25rem">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(16rem,1fr));gap:1rem">
            <div>
                <div style="{{ $label }}" class="text-gray-500 dark:text-gray-400">Cover photo</div>
                @if ($cover && ! Media::isVideo($cover))
                    <a href="{{ Media::url($cover) }}" target="_blank" rel="noopener">
                        <img src="{{ Media::url($cover) }}" alt="Cover photo" loading="lazy" style="{{ $frame }};aspect-ratio:16/10">
                    </a>
                @elseif ($cover)
                    <video controls preload="metadata" src="{{ Media::url($cover) }}" style="{{ $frame }};aspect-ratio:16/10"></video>
                @else
                    <p class="text-sm text-warning-600">No cover photo.</p>
                @endif
            </div>
            <div>
                <div style="{{ $label }}" class="text-gray-500 dark:text-gray-400">Cover film</div>
                @if ($coverFilm)
                    <video controls preload="metadata" src="{{ Media::url($coverFilm) }}" style="{{ $frame }};aspect-ratio:16/10"></video>
                @elseif ($promoVideo && Media::isVideo($promoVideo))
                    <video controls preload="metadata" src="{{ Media::url($promoVideo) }}" style="{{ $frame }};aspect-ratio:16/10"></video>
                @elseif ($promoVideo)
                    <a href="{{ $promoVideo }}" target="_blank" rel="noopener" class="text-primary-600 text-sm" style="font-weight:600">Open external video ↗</a>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">No cover film.</p>
                @endif
            </div>
        </div>

        <div>
            <div style="{{ $label }}" class="text-gray-500 dark:text-gray-400">Gallery photos ({{ $photos->count() }})</div>
            @if ($photos->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">No gallery photos.</p>
            @else
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(9rem,1fr));gap:.5rem">
                    @foreach ($photos as $photo)
                        <a href="{{ Media::url($photo) }}" target="_blank" rel="noopener">
                            <img src="{{ Media::url($photo) }}" alt="Gallery photo {{ $loop->iteration }}" loading="lazy" style="{{ $frame }};aspect-ratio:1/1">
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        @if ($films->isNotEmpty())
            <div>
                <div style="{{ $label }}" class="text-gray-500 dark:text-gray-400">Gallery films ({{ $films->count() }})</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(14rem,1fr));gap:.5rem">
                    @foreach ($films as $film)
                        <video controls preload="metadata" src="{{ Media::url($film) }}" style="{{ $frame }};aspect-ratio:9/16;max-height:22rem"></video>
                    @endforeach
                </div>
            </div>
        @endif

        <div>
            <div style="{{ $label }}" class="text-gray-500 dark:text-gray-400">Reel walk ({{ $scenes->count() }} {{ \Illuminate\Support\Str::plural('scene', $scenes->count()) }})</div>
            @if ($scenes->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">No reel-walk scenes.</p>
            @else
                <ol style="display:grid;grid-template-columns:repeat(auto-fill,minmax(12rem,1fr));gap:.75rem;list-style:none;padding:0;margin:0">
                    @foreach ($scenes as $scene)
                        <li style="border-radius:.75rem;overflow:hidden" class="ring-1 ring-gray-950/5 dark:ring-white/10">
                            @if ($scene->isVideo())
                                <video controls preload="metadata" src="{{ $scene->url() }}" style="{{ $frame }};border-radius:0;aspect-ratio:9/16;max-height:20rem"></video>
                            @elseif ($scene->url())
                                <img src="{{ $scene->url() }}" alt="{{ $scene->title ?: 'Scene '.$loop->iteration }}" loading="lazy" style="{{ $frame }};border-radius:0;aspect-ratio:9/16;max-height:20rem">
                            @endif
                            <div style="padding:.6rem .7rem">
                                <div style="font-size:.7rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase" class="text-gray-500 dark:text-gray-400">
                                    {{ $loop->iteration }} · {{ $scene->spaceLabel() ?? 'No space' }}
                                </div>
                                <div style="font-weight:600" class="text-gray-950 dark:text-white">{{ $scene->title ?: 'Untitled scene' }}</div>
                                @if ($scene->caption)
                                    <div style="font-size:.82rem" class="text-gray-600 dark:text-gray-300">{{ $scene->caption }}</div>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>
    </div>
@endif
