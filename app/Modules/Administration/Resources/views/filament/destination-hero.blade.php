@php
    use App\Shared\Support\Media;
    $record = $getRecord();
    $hero = $record ? Media::url($record->image_url ?: $record->thumbnail) : null;
@endphp
@if ($hero)
    <div style="max-width: 32rem;">
        @if (Media::isVideo($hero))
            <video controls preload="metadata" src="{{ $hero }}" style="width: 100%; border-radius: 0.5rem;"></video>
        @else
            <img src="{{ $hero }}" alt="{{ $record->name }} hero" style="width: 100%; border-radius: 0.5rem; object-fit: cover; max-height: 16rem;">
        @endif
    </div>
@else
    <p style="font-size: 0.875rem; opacity: 0.7;">No hero media yet.</p>
@endif
