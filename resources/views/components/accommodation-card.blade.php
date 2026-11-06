@props(['accommodation'])

{{-- A published stay (the Accommodation wrapper or the hotel / villa / B&B behind
     it) shown as a discovery reel card: its own photograph, house footage on
     hover, price per night and rating. Renders nothing when the stay has no
     detail page to link to. --}}
@php
    $isWrapper = $accommodation instanceof \App\Modules\Accommodation\Domain\Models\Accommodation;
    $bookable = $isWrapper ? $accommodation->bookable : $accommodation;
    $stay = $isWrapper ? $accommodation : null;

    [$route, $type, $label] = match (true) {
        $bookable instanceof \App\Modules\Accommodation\Domain\Models\Hotel => [route('hotel-show', $bookable->slug), 'hotel', 'Hotel'],
        $bookable instanceof \App\Modules\Accommodation\Domain\Models\BedAndBreakfast => [route('bedandbreakfast.show', $bookable->slug), 'bed_and_breakfast', 'B&B'],
        $bookable instanceof \App\Modules\Accommodation\Domain\Models\Villa => [route('villa.show', $bookable->slug), 'villa', 'Villa'],
        default => [null, null, null],
    };

    $card = null;

    if ($route) {
        $image = collect([
            $bookable->getAttribute('cover_image'),
            (array) ($bookable->getAttribute('gallery') ?? []),
            $stay?->getAttribute('cover_image'),
        ])->flatten()->first(fn ($candidate) => is_string($candidate)
            && filled($candidate)
            && ! \Illuminate\Support\Str::contains($candidate, ['placeholder', 'placehold', 'picsum', 'lorempixel']));

        if ($image && ! \Illuminate\Support\Str::startsWith($image, ['http://', 'https://', '/'])) {
            $image = asset('storage/'.ltrim($image, '/'));
        }

        $clips = (array) config('stays.reels.'.$type, []);
        $clip = $clips === [] ? null : config('stays.clips.'.$clips[crc32((string) $bookable->getKey()) % count($clips)]);

        $image = $image ?: ($clip['poster'] ?? null) ?: match ($type) {
            'villa' => 'https://images.unsplash.com/photo-1561501900-3701fa6a0864?auto=format&fit=crop&w=1000&q=84',
            'hotel' => 'https://images.unsplash.com/photo-1574236170880-fbbca132d83d?auto=format&fit=crop&w=1000&q=84',
            default => 'https://images.unsplash.com/photo-1708119063168-4785d1359824?auto=format&fit=crop&w=1000&q=84',
        };

        $rating = (float) ($stay?->avg_rating ?: ($bookable->getAttribute('avg_rating') ?: 0));
        $reviews = (int) ($stay?->reviews_count ?? $bookable->getAttribute('reviews_count') ?? 0);
        if ($reviews === 0 && $bookable->relationLoaded('reviews')) {
            $reviews = $bookable->reviews->count();
        }

        $card = [
            'name' => $bookable->name ?: ($stay?->name ?? 'Stay'),
            'alt' => $bookable->name ?: ($stay?->name ?? 'Stay'),
            'type_label' => $label,
            'place' => collect([$bookable->getAttribute('city') ?: $stay?->city, $bookable->getAttribute('country') ?: $stay?->country])->filter()->implode(', ') ?: ($stay?->destination?->name ?? 'Kenya'),
            'destination' => null,
            'rating' => $rating,
            'reviews' => $reviews,
            'price' => (float) ($bookable->getAttribute('price_per_night') ?? $bookable->getAttribute('avg_price_per_night') ?? $stay?->avg_price_per_night ?? 0),
            'currency' => 'KSh',
            'guests' => (int) ($bookable->getAttribute('max_guests') ?? 0),
            'image' => $image,
            'video' => $clip['video'] ?? null,
            'poster' => $clip['poster'] ?? null,
            'featured' => (bool) ($bookable->getAttribute('is_featured') ?? $stay?->is_featured),
            'url' => $route,
        ];
    }
@endphp

@if ($card)
    <x-stay.reel-card :card="$card" />
@endif
