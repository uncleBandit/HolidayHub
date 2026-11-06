@php
    $currency = \App\Shared\Support\Currencies::symbol($bnb->getCurrency());
    $price = (float) $bnb->price_per_night;
    $place = collect([$bnb->city, $bnb->country])->filter()->implode(', ');
    $rating = (float) $bnb->avg_rating;
    $images = array_merge([$bnb->cover_image], (array) ($bnb->gallery ?? []));
    $policies = (array) ($bnb->policies ?? []);
    $welcome = trim((string) ($policies['host_welcome'] ?? ''));
    $houseRules = \Illuminate\Support\Arr::except($policies, ['host_welcome']);
    $breakfast = trim((string) $bnb->breakfast_details);
    $seasons = $bnb->seasonalRates
        ->filter(fn ($rate) => $rate->active && \Carbon\Carbon::parse($rate->end_date)->endOfDay()->isFuture())
        ->sortBy('start_date')
        ->values();
    $specs = collect([
        $bnb->rooms ? ['icon' => 'bed', 'value' => $bnb->rooms.' '.\Illuminate\Support\Str::plural('room', $bnb->rooms), 'sub' => 'Guest rooms'] : null,
        $bnb->max_guests ? ['icon' => 'users', 'value' => 'Up to '.$bnb->max_guests.' guests', 'sub' => 'Per booking'] : null,
        $bnb->has_breakfast ? ['icon' => 'utensils', 'value' => 'Breakfast included', 'sub' => 'Made every morning'] : ['icon' => 'utensils', 'value' => 'Room only', 'sub' => 'Breakfast not included'],
    ])->filter()->values();
    $tabs = array_filter([
        'overview' => 'Overview',
        'breakfast' => $bnb->has_breakfast ? 'Breakfast' : null,
        'availability' => 'Availability',
        'amenities' => 'Amenities',
        'reviews' => 'Reviews',
        'location' => 'Location',
    ]);
@endphp

<div class="pb-24 lg:pb-0">
    <x-stay-detail.gallery
        :images="$images"
        :video="$bnb->cover_video"
        :name="$bnb->name"
        kind="bnb"
        :wishlisted="$isWishlisted"
    />

    <div class="mx-auto max-w-[1250px] px-4 sm:px-6 lg:px-8">
        <x-stay-detail.heading
            :name="$bnb->name"
            kind="Bed & breakfast"
            :host="$bnb->provider?->company_name"
            :verified="(bool) $bnb->is_verified"
            :rating="$rating"
            :reviews="$bnb->reviews_count"
            :location="$place ?: $bnb->address"
            :facts="[
                ['value' => $rating > 0 ? number_format($rating, 1) : 'New', 'label' => 'Guest rating', 'star' => $rating > 0],
                ['value' => number_format((int) $bnb->reviews_count), 'label' => 'Verified reviews'],
                ['value' => $bnb->rooms ?: '—', 'label' => 'Guest rooms'],
            ]"
        />

        {{-- The reel walk: the host's walk through the house, played like stories. --}}
        <x-stay-detail.reel-walk :scenes="$bnb->scenes" :name="$bnb->name" class="mt-10" />

        <x-stay-detail.tabs
            :tabs="$tabs"
            :active="$activeTab"
            wire-tab
        />

        <div class="lg:grid lg:grid-cols-[minmax(0,1.55fr)_minmax(20rem,0.8fr)] lg:gap-14">
            <div class="min-w-0">
                {{-- About --}}
                <x-stay-detail.section id="overview">
                    <div class="grid gap-6 md:grid-cols-[0.9fr_1.1fr] md:gap-10">
                        <div>
                            <p class="dv-eyebrow">The {{ $bnb->name }} experience</p>
                            <h2 class="dv-title">{{ $bnb->city ? 'A warm welcome in '.$bnb->city : 'A warm welcome' }}</h2>
                        </div>
                        <p class="dv-body whitespace-pre-line">{{ $bnb->description ?: 'Your hosts are still writing about their home. The details below cover what you can expect.' }}</p>
                    </div>

                    @if ($welcome !== '')
                        <figure class="mt-8 rounded-[1.2rem] border border-line bg-surface px-6 py-6 sm:px-8">
                            <p class="dv-eyebrow">A word from your host</p>
                            <blockquote class="mt-3 whitespace-pre-line font-dm-serif text-[clamp(1.25rem,2.2vw,1.6rem)] italic leading-snug text-forest">“{{ $welcome }}”</blockquote>
                            @if ($bnb->provider?->company_name)
                                <figcaption class="mt-3 text-[12px] font-extrabold text-muted">— {{ $bnb->provider->company_name }}</figcaption>
                            @endif
                        </figure>
                    @endif

                    <div class="mt-10 grid grid-cols-2 gap-x-4 gap-y-6 border-y border-line py-6 sm:grid-cols-3">
                        @foreach ($specs as $spec)
                            <div class="flex items-center gap-3">
                                <x-line-icon :name="$spec['icon']" :size="22" class="text-clay" />
                                <p class="flex flex-col text-[13px]"><b class="text-ink">{{ $spec['value'] }}</b><span class="mt-0.5 text-[11.5px] text-muted">{{ $spec['sub'] }}</span></p>
                            </div>
                        @endforeach
                    </div>

                    @if ($offers->isNotEmpty())
                        <div class="mt-6 flex flex-col gap-3">
                            @foreach ($offers as $offer)
                                <div class="flex items-center gap-4 rounded-[0.9rem] bg-sand/60 px-5 py-4" wire:key="offer-{{ $offer->id }}">
                                    <x-line-icon name="ticket" :size="22" class="text-clay" />
                                    <p class="min-w-0 text-[12.5px] leading-snug text-muted">
                                        <b class="block text-ink">{{ $offer->title }}@if ($offer->discount_percent) · {{ rtrim(rtrim(number_format((float) $offer->discount_percent, 2), '0'), '.') }}% off @endif</b>
                                        {{ \Illuminate\Support\Str::limit((string) $offer->description, 140) }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    @elseif ($rating >= 4.7)
                        <div class="mt-6 flex items-center gap-4 rounded-[0.9rem] bg-sand/60 px-5 py-4">
                            <x-line-icon name="star" :size="24" filled class="text-clay" />
                            <p class="text-[12.5px] leading-snug text-muted"><b class="block text-ink">A guest favourite</b>One of the most loved stays{{ $bnb->city ? ' in '.$bnb->city : '' }}, based on ratings and verified reviews.</p>
                        </div>
                    @endif
                </x-stay-detail.section>

                {{-- Breakfast --}}
                @if ($bnb->has_breakfast)
                    <x-stay-detail.section id="breakfast" eyebrow="Mornings here" title="Breakfast is included">
                        <div class="grid gap-5 overflow-hidden rounded-[1.2rem] bg-[#F6EEDD]/70 p-6 sm:grid-cols-[auto_1fr] sm:p-7">
                            <span class="grid h-14 w-14 place-items-center rounded-full bg-white text-clay"><x-line-icon name="utensils" :size="24" /></span>
                            <div class="min-w-0">
                                @if ($breakfast !== '')
                                    <p class="dv-body whitespace-pre-line !text-ink">{{ $breakfast }}</p>
                                @else
                                    <p class="dv-body !text-ink">Breakfast is made every morning and included in your nightly rate. Ask your host about times and any dietary needs.</p>
                                @endif
                                <p class="mt-3 text-[12px] font-bold text-muted">Included in every night’s rate{{ $price > 0 ? ' · from '.$currency.' '.number_format($price).' per room' : '' }}</p>
                            </div>
                        </div>
                    </x-stay-detail.section>
                @endif

                {{-- Availability --}}
                <x-stay-detail.section id="availability" eyebrow="Plan your dates" title="Availability">
                    @if ($bnb && $bnb->id)
                        <livewire:calendar.calendar-component :bookable="$bnb" />
                        @if ($seasons->isNotEmpty())
                            <div class="mt-6 rounded-[1.1rem] border border-line bg-surface p-5">
                                <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-muted">Seasonal rates</p>
                                <div class="mt-2 divide-y divide-line">
                                    @foreach ($seasons as $season)
                                        <div class="flex flex-wrap items-center justify-between gap-2 py-2.5 text-[13px]" wire:key="season-{{ $season->id }}">
                                            <span class="font-extrabold text-ink">{{ $season->description ?: 'Seasonal rate' }}</span>
                                            <span class="text-muted">{{ \Carbon\Carbon::parse($season->start_date)->format('j M') }} – {{ \Carbon\Carbon::parse($season->end_date)->format('j M Y') }}</span>
                                            <span class="font-dm-serif text-[1.1rem] text-forest">{{ $currency }} {{ number_format((float) $season->rate) }}<span class="font-sans text-[11px] text-muted"> / night</span></span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @else
                        <p class="rounded-[1.2rem] border border-dashed border-line bg-surface px-6 py-10 text-center text-[13px] text-muted">The booking calendar is unavailable right now.</p>
                    @endif
                </x-stay-detail.section>

                {{-- Amenities --}}
                <x-stay-detail.section id="amenities" eyebrow="Key features" title="Little things that matter">
                    <x-stay-detail.amenities :amenities="$bnb->amenities" />
                </x-stay-detail.section>

                {{-- Reviews --}}
                <x-stay-detail.section id="reviews" eyebrow="Guest stories" :title="'Guest reviews ('.number_format((int) $bnb->reviews_count).')'">
                    @if ($reviews && $reviews->isNotEmpty())
                        <div class="grid gap-4 md:grid-cols-2">
                            @foreach ($reviews as $review)
                                <x-stay-detail.review :review="$review" wire:key="review-{{ $review->id }}" />
                            @endforeach
                        </div>
                        @if ($hasMoreReviews && $reviews->count() >= 5)
                            <div class="mt-6 flex justify-center">
                                <button type="button" wire:click="loadReviews" class="dv-btn dv-btn-ghost">
                                    <span wire:loading.remove wire:target="loadReviews">Load more reviews</span>
                                    <span wire:loading wire:target="loadReviews">Loading…</span>
                                </button>
                            </div>
                        @endif
                    @else
                        <x-stay-detail.reviews-empty />
                    @endif
                </x-stay-detail.section>

                {{-- Agent journeys that include this stay (renders nothing when there are none). --}}
                @include('packages::partials.journeys-rail', ['journeysFor' => $bnb, 'journeysTitle' => 'Journeys that include '.$bnb->name, 'journeysClass' => 'scroll-mt-[150px] border-b border-line py-12 md:py-14'])

                {{-- Location --}}
                <x-stay-detail.section id="location" eyebrow="The neighbourhood" title="Where you'll stay" last>
                    <x-stay-detail.location
                        :address="$bnb->address"
                        :city="$bnb->city"
                        :country="$bnb->country"
                        :latitude="$bnb->latitude"
                        :longitude="$bnb->longitude"
                        :policies="$houseRules"
                    />
                </x-stay-detail.section>
            </div>

            <div class="pb-12 lg:self-start lg:pb-16 lg:pt-12">
                <x-stay-detail.booking-card
                    :price="$price"
                    :currency="$currency"
                    :rating="$rating"
                    :reviews="$bnb->reviews_count"
                    :cancellation="$bnb->policies['cancellation'] ?? null"
                >
                    <x-slot:guests>
                        <label for="bnb-guests" class="flex items-center justify-between gap-3 p-3">
                            <span class="flex flex-col">
                                <span class="text-[9.5px] font-extrabold uppercase tracking-[0.1em] text-ink">Guests</span>
                                <span class="mt-1 text-[11px] text-muted">{{ $bnb->max_guests ? 'Up to '.$bnb->max_guests : 'How many are coming?' }}</span>
                            </span>
                            <input
                                type="number"
                                id="bnb-guests"
                                wire:model.live="guests"
                                min="1"
                                max="{{ $bnb->max_guests }}"
                                class="w-20 rounded-[0.6rem] border border-line bg-cream px-3 py-2 text-center text-[13px] font-bold text-ink focus:border-forest focus:outline-none focus:ring-0"
                            >
                        </label>
                        @error('guests') <p class="px-3 pb-3 text-[11.5px] font-semibold text-clay-dark">{{ $message }}</p> @enderror
                    </x-slot:guests>
                </x-stay-detail.booking-card>
            </div>
        </div>
    </div>

    <livewire:forms.booking-form />
</div>
