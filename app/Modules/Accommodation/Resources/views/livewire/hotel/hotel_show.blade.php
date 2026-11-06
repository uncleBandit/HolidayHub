@php
    $currency = \App\Shared\Support\Currencies::symbol($hotel->getCurrency());
    $roomTypes = $hotel->roomTypes->sortBy('price_per_night')->values();
    $fromPrice = $selectedRoomType?->price_per_night
        ?: ($roomTypes->min('price_per_night') ?: $hotel->avg_price_per_night);
    $place = collect([$hotel->city, $hotel->country])->filter()->implode(', ');
    $stars = (int) $hotel->stars;
    $kind = $stars > 0 ? $stars.'-star hotel' : 'Hotel';
    $roomFallback = 'https://images.unsplash.com/photo-1574236170880-fbbca132d83d?auto=format&fit=crop&w=1400&q=84';
    $roomImage = function ($roomType) use ($roomFallback) {
        $url = (string) $roomType->hero_image_url;

        return \Illuminate\Support\Str::startsWith($url, ['http://', 'https://'])
            && ! \Illuminate\Support\Str::contains($url, ['placeholder', 'placehold', 'picsum', 'source.unsplash', 'default-hero'])
            ? $url
            : $roomFallback;
    };
    $rating = (float) $hotel->avg_rating;
@endphp

<div class="pb-24 lg:pb-0">
    <x-stay-detail.gallery
        :images="$hotel->all_images"
        :video="$hotel->cover_video"
        :name="$hotel->name"
        kind="hotel"
        :wishlisted="$isWishlisted"
    />

    <div class="mx-auto max-w-[1250px] px-4 sm:px-6 lg:px-8">
        <x-stay-detail.heading
            :name="$hotel->name"
            :kind="$kind"
            :host="$hotel->provider?->company_name"
            :verified="(bool) $hotel->is_verified"
            :rating="$rating"
            :reviews="$hotel->reviews_count"
            :location="$place ?: $hotel->address"
            :facts="[
                ['value' => $rating > 0 ? number_format($rating, 1) : 'New', 'label' => 'Guest rating', 'star' => $rating > 0],
                ['value' => number_format((int) $hotel->reviews_count), 'label' => 'Verified reviews'],
                ['value' => $roomTypes->count(), 'label' => \Illuminate\Support\Str::plural('Room type', $roomTypes->count())],
            ]"
        />

        <x-stay-detail.tabs :tabs="[
            'overview' => 'Overview',
            'rooms' => 'Rooms',
            'availability' => 'Availability',
            'amenities' => 'Amenities',
            'reviews' => 'Reviews',
            'location' => 'Location',
        ]" />

        <div class="lg:grid lg:grid-cols-[minmax(0,1.55fr)_minmax(20rem,0.8fr)] lg:gap-14">
            <div class="min-w-0">
                {{-- About --}}
                <x-stay-detail.section id="overview">
                    <div class="grid gap-6 md:grid-cols-[0.9fr_1.1fr] md:gap-10">
                        <div>
                            <p class="dv-eyebrow">The stay</p>
                            <h2 class="dv-title">{{ $place ? 'Your base in '.$hotel->city : 'Make yourself at home' }}</h2>
                        </div>
                        <div>
                            <p class="dv-body whitespace-pre-line">{{ $hotel->description ?: 'The hotel is still writing its story. Browse the rooms below to see what each one offers.' }}</p>
                            @if ($stars > 0)
                                <div class="mt-6 flex items-center gap-4 rounded-[0.9rem] bg-sand/60 px-5 py-4">
                                    <span class="flex shrink-0 gap-0.5 text-clay" aria-label="{{ $stars }} star hotel">
                                        @for ($i = 0; $i < $stars; $i++)
                                            <x-line-icon name="star" :size="15" filled />
                                        @endfor
                                    </span>
                                    <p class="text-[12.5px] leading-snug text-muted"><b class="block text-ink">{{ $stars }}-star hospitality</b>{{ $rating >= 4.7 ? 'One of the best-rated stays around, based on verified guest reviews.' : 'Rated by the official hotel classification.' }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </x-stay-detail.section>

                {{-- Rooms --}}
                <x-stay-detail.section id="rooms" eyebrow="Stay a while" title="Rooms made for slowing down">
                    <p class="-mt-3 mb-6 text-[13px] text-muted">Select a room type to check its availability and book.</p>

                    <div class="flex flex-col gap-5">
                        @forelse ($roomTypes as $roomType)
                            @php $isSelected = $selectedRoomType && $selectedRoomType->id === $roomType->id; @endphp
                            <article
                                wire:key="room-type-{{ $roomType->id }}"
                                @class([
                                    'grid overflow-hidden rounded-[1.3rem] bg-sand/50 transition-shadow duration-300 sm:grid-cols-[1.1fr_1fr]',
                                    'ring-2 ring-clay shadow-[0_20px_60px_rgba(20,36,28,0.12)]' => $isSelected,
                                ])
                            >
                                <div class="relative h-56 overflow-hidden sm:h-auto sm:min-h-[19rem]">
                                    @php $roomFilm = collect($roomType->gallery_images ?? [])->first(fn ($path) => \App\Shared\Support\Media::isVideo($path)); @endphp
                                    @if ($roomFilm)
                                        <video src="{{ \App\Shared\Support\Media::url($roomFilm) }}" poster="{{ $roomImage($roomType) }}" class="absolute inset-0 h-full w-full object-cover" muted loop playsinline preload="none" data-autoplay aria-label="{{ $roomType->name }} walk-through"></video>
                                        <span class="pointer-events-none absolute bottom-3 left-3 z-10 inline-flex items-center gap-1 rounded-full bg-black/45 px-2.5 py-1 text-[10px] font-extrabold text-white backdrop-blur-md"><x-line-icon name="play" :size="10" filled /> Room walk-through</span>
                                    @else
                                        <img src="{{ $roomImage($roomType) }}" alt="{{ $roomType->name }}" class="absolute inset-0 h-full w-full object-cover" loading="lazy">
                                    @endif
                                    @if ($loop->first && $roomTypes->count() > 1)
                                        <span class="dv-foryou-reason"><x-line-icon name="star" :size="12" filled /> Best value</span>
                                    @endif
                                </div>
                                <div class="flex flex-col justify-center p-6 sm:p-8">
                                    @if ($isSelected)
                                        <p class="flex items-center gap-1.5 text-[11px] font-extrabold text-clay"><x-line-icon name="check" :size="14" :stroke="2.6" /> Selected room</p>
                                    @endif
                                    <h3 class="mt-1 font-dm-serif text-[1.7rem] leading-tight text-forest">{{ $roomType->name }}</h3>
                                    <p class="mt-1.5 flex flex-wrap gap-x-3 gap-y-1 text-[12px] text-muted">
                                        @if ($roomType->size_sqm)
                                            <span class="inline-flex items-center gap-1"><x-line-icon name="grid" :size="14" /> {{ $roomType->size_sqm }} m²</span>
                                        @endif
                                        @if ($roomType->viewLabel())
                                            <span class="inline-flex items-center gap-1"><x-line-icon name="waves" :size="14" /> {{ $roomType->viewLabel() }}</span>
                                        @endif
                                        <span class="inline-flex items-center gap-1"><x-line-icon name="bed" :size="14" /> {{ $roomType->bedSummary() ?? $roomType->beds.' '.\Illuminate\Support\Str::plural('bed', $roomType->beds) }}</span>
                                        @if ($roomType->capacity)
                                            <span class="inline-flex items-center gap-1"><x-line-icon name="users" :size="14" />
                                                Sleeps {{ $roomType->capacity }}@if ($roomType->max_children) ({{ $roomType->max_adults }} adults, {{ $roomType->max_children }} {{ \Illuminate\Support\Str::plural('child', $roomType->max_children) }})@endif
                                            </span>
                                        @endif
                                    </p>
                                    <div class="mt-3 flex flex-wrap gap-1.5">
                                        @if ($roomType->breakfast_included)
                                            <span class="rounded-full bg-forest/10 px-2.5 py-1 text-[10.5px] font-extrabold text-forest">Breakfast included</span>
                                        @endif
                                        <span @class(['rounded-full px-2.5 py-1 text-[10.5px] font-extrabold', 'bg-forest/10 text-forest' => $roomType->refundable, 'bg-line text-muted' => ! $roomType->refundable])>{{ $roomType->refundable ? 'Free cancellation' : 'Non-refundable' }}</span>
                                        @if ($roomType->accessible)
                                            <span class="rounded-full bg-[#F6EEDD] px-2.5 py-1 text-[10.5px] font-extrabold text-[#8A6A3A]">Accessible</span>
                                        @endif
                                        @foreach ($roomType->amenities->take(4) as $amenity)
                                            <span class="rounded-full border border-line px-2.5 py-1 text-[10.5px] font-bold text-muted">{{ $amenity->name }}</span>
                                        @endforeach
                                        @if ($roomType->amenities->count() > 4)
                                            <span class="rounded-full border border-line px-2.5 py-1 text-[10.5px] font-bold text-muted">+{{ $roomType->amenities->count() - 4 }} more</span>
                                        @endif
                                    </div>
                                    @if (filled($roomType->description))
                                        <p class="mt-3 line-clamp-3 text-[13px] leading-relaxed text-muted">{{ $roomType->description }}</p>
                                    @endif
                                    <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
                                        <p class="font-dm-serif text-[1.3rem] text-forest">{{ $currency }} {{ number_format((float) $roomType->price_per_night) }} <small class="font-sans text-[11px] font-semibold text-muted">/ night</small></p>
                                        <button
                                            type="button"
                                            wire:click="selectRoomType({{ $roomType->id }})"
                                            x-on:click="$nextTick(() => setTimeout(() => document.getElementById('availability')?.scrollIntoView({ behavior: 'smooth', block: 'start' }), 350))"
                                            @class(['dv-btn', 'dv-btn-primary' => ! $isSelected, 'dv-btn-ghost' => $isSelected])
                                        >
                                            <span wire:loading.remove wire:target="selectRoomType({{ $roomType->id }})">{{ $isSelected ? 'See dates' : 'Select room' }}</span>
                                            <span wire:loading wire:target="selectRoomType({{ $roomType->id }})">Loading…</span>
                                            <x-line-icon name="arrow" :size="16" />
                                        </button>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="rounded-[1.2rem] border border-dashed border-line bg-surface px-6 py-10 text-center">
                                <x-line-icon name="bed" :size="24" class="mx-auto text-clay" />
                                <p class="mt-3 font-dm-serif text-[1.35rem] text-forest">Rooms are on their way</p>
                                <p class="mt-1 text-[13px] text-muted">This hotel hasn't listed its room types yet. Save it and check back soon.</p>
                            </div>
                        @endforelse
                    </div>
                </x-stay-detail.section>

                {{-- Availability --}}
                <x-stay-detail.section id="availability" eyebrow="Plan your dates" :title="$selectedRoomType ? 'Availability · '.$selectedRoomType->name : 'Availability'">
                    @if ($selectedRoomType)
                        <div wire:loading.flex wire:target="selectRoomType" class="items-center justify-center gap-3 rounded-[1.2rem] border border-line bg-surface p-10 text-[13px] text-muted">
                            <span class="h-5 w-5 animate-spin rounded-full border-2 border-line border-t-clay"></span>
                            Loading calendar…
                        </div>
                        <div wire:loading.remove wire:target="selectRoomType">
                            @livewire('calendar.calendar-component', ['bookable' => $selectedRoomType], key('calendar-room-type-'.$selectedRoomType->id))
                        </div>
                    @else
                        <div class="flex flex-col items-center rounded-[1.2rem] border border-dashed border-line bg-surface px-6 py-12 text-center">
                            <span class="grid h-12 w-12 place-items-center rounded-full bg-sand/70 text-clay"><x-line-icon name="calendar" :size="20" /></span>
                            <p class="mt-4 font-dm-serif text-[1.4rem] text-forest">Choose a room first</p>
                            <p class="mt-1 max-w-sm text-[13px] text-muted">Select a room type to see the availability calendar and nightly prices.</p>
                            @if ($roomTypes->isNotEmpty())
                                <button type="button" class="dv-btn dv-btn-ghost mt-5" x-on:click="document.getElementById('rooms')?.scrollIntoView({ behavior: 'smooth', block: 'start' })">See rooms</button>
                            @endif
                        </div>
                    @endif
                </x-stay-detail.section>

                {{-- Amenities --}}
                <x-stay-detail.section id="amenities" eyebrow="On the property" title="Everything within reach">
                    <x-stay-detail.amenities :amenities="$hotel->amenities" />
                </x-stay-detail.section>

                {{-- Reviews --}}
                <x-stay-detail.section id="reviews" eyebrow="Guest stories" title="What guests are saying" wire:init="loadReviews">
                    @if ($reviews->isNotEmpty())
                        <div class="grid gap-4 md:grid-cols-2">
                            @foreach ($reviews as $review)
                                <x-stay-detail.review :review="$review" wire:key="review-{{ $review->id }}" />
                            @endforeach
                        </div>
                        @if ($hasMoreReviews && $reviews->count() >= 5)
                            <div class="mt-6 flex justify-center">
                                <button type="button" wire:click="loadReviews" class="dv-btn dv-btn-ghost">
                                    <span wire:loading.remove wire:target="loadReviews">More reviews</span>
                                    <span wire:loading wire:target="loadReviews">Loading…</span>
                                </button>
                            </div>
                        @endif
                    @else
                        <x-stay-detail.reviews-empty />
                    @endif
                </x-stay-detail.section>

                {{-- Agent journeys that include this stay (renders nothing when there are none). --}}
                @include('packages::partials.journeys-rail', ['journeysFor' => $hotel, 'journeysTitle' => 'Journeys that include '.$hotel->name, 'journeysClass' => 'scroll-mt-[150px] border-b border-line py-12 md:py-14'])

                {{-- Location --}}
                <x-stay-detail.section id="location" eyebrow="The neighbourhood" title="Where you'll stay" last>
                    <x-stay-detail.location
                        :address="$hotel->address"
                        :city="$hotel->city"
                        :country="$hotel->country"
                        :latitude="$hotel->latitude"
                        :longitude="$hotel->longitude"
                        :policies="$hotel->policies"
                    />
                </x-stay-detail.section>
            </div>

            <div class="pb-12 lg:self-start lg:pb-16 lg:pt-12">
                <x-stay-detail.booking-card
                    wire:key="hotel-booking-{{ $selectedRoomType?->id ?? 'none' }}"
                    :price="$fromPrice"
                    :currency="$currency"
                    :from="! $selectedRoomType"
                    :rating="$rating"
                    :reviews="$hotel->reviews_count"
                    :target="$selectedRoomType ? 'availability' : 'rooms'"
                    :ready="(bool) $selectedRoomType"
                    :pending-label="$selectedRoomType ? 'Check availability' : 'Choose a room'"
                    :guests-label="$selectedRoomType?->capacity ? 'Up to '.$selectedRoomType->capacity.' guests' : null"
                    :note="$selectedRoomType ? 'Room: '.$selectedRoomType->name : null"
                    :cancellation="$hotel->policies['cancellation'] ?? null"
                />
            </div>
        </div>
    </div>

    <livewire:forms.booking-form />
</div>
