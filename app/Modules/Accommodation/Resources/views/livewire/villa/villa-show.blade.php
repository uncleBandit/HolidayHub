@php
    $currency = \App\Support\Currencies::symbol($villa->getCurrency());
    $minNights = max(1, (int) $villa->min_nights);
    $staff = collect($villa->staffKeys())->map(fn ($key) => \App\Modules\Accommodation\Domain\Models\Villa::STAFF[$key]);
    $seasons = $villa->seasonalRates->where('active', true)->filter(fn ($rate) => \Carbon\Carbon::parse($rate->end_date)->gte(today()))->sortBy('start_date')->values();
    $price = (float) $villa->getBasePrice();
    $place = collect([$villa->city, $villa->country])->filter()->implode(', ');
    $rating = (float) $villa->avg_rating;
    $images = array_merge([$villa->cover_image], (array) ($villa->gallery ?? []));
    $specs = collect([
        $villa->bedrooms ? ['icon' => 'bed', 'value' => $villa->bedrooms.' '.\Illuminate\Support\Str::plural('bedroom', $villa->bedrooms), 'sub' => 'Private rooms'] : null,
        $villa->bathrooms ? ['icon' => 'waves', 'value' => $villa->bathrooms.' '.\Illuminate\Support\Str::plural('bathroom', $villa->bathrooms), 'sub' => 'Ensuite comfort'] : null,
        $villa->max_guests ? ['icon' => 'users', 'value' => 'Up to '.$villa->max_guests.' guests', 'sub' => 'The whole villa'] : null,
        $villa->size_sqm ? ['icon' => 'grid', 'value' => number_format((int) $villa->size_sqm).' m²', 'sub' => 'Of living space'] : null,
        $villa->has_private_pool ? ['icon' => 'waves', 'value' => 'Private pool', 'sub' => 'Just for your group'] : null,
    ])->filter()->values();
@endphp

<div class="pb-24 lg:pb-0">
    <x-stay-detail.gallery
        :images="$images"
        :video="$villa->cover_video"
        :name="$villa->name"
        kind="villa"
        :wishlisted="$isWishlisted"
    />

    <div class="mx-auto max-w-[1250px] px-4 sm:px-6 lg:px-8">
        <x-stay-detail.heading
            :name="$villa->name"
            kind="Private villa"
            :host="$villa->provider?->company_name"
            :verified="(bool) $villa->is_verified"
            :rating="$rating"
            :reviews="$villa->reviews_count"
            :location="$place ?: $villa->address"
            :facts="[
                ['value' => $rating > 0 ? number_format($rating, 1) : 'New', 'label' => 'Guest rating', 'star' => $rating > 0],
                ['value' => $villa->max_guests ?: '—', 'label' => 'Max guests'],
                ['value' => $minNights.' '.\Illuminate\Support\Str::plural('night', $minNights), 'label' => 'Minimum stay'],
            ]"
        />

        {{-- The reel walk: the host's walk through the house, from the gate to the view. --}}
        <x-stay-detail.reel-walk :scenes="$villa->scenes" :name="$villa->name" class="mt-10 border-b border-line !pb-12" />

        <x-stay-detail.tabs
            :tabs="[
                'overview' => 'Overview',
                'availability' => 'Availability',
                'amenities' => 'Amenities',
                'reviews' => 'Reviews ('.$reviews->total().')',
                'location' => 'Location',
            ]"
            :active="$activeTab"
            wire-tab
        />

        <div class="lg:grid lg:grid-cols-[minmax(0,1.55fr)_minmax(20rem,0.8fr)] lg:gap-14">
            <div class="min-w-0">
                {{-- About --}}
                <x-stay-detail.section id="overview">
                    <div class="grid gap-6 md:grid-cols-[0.9fr_1.1fr] md:gap-10">
                        <div>
                            <p class="dv-eyebrow">About this villa</p>
                            <h2 class="dv-title">{{ $place ? 'A place of your own in '.$villa->city : 'A place of your own' }}</h2>
                        </div>
                        <p class="dv-body whitespace-pre-line">{{ $villa->description ?: 'The host is still writing about this villa. The details below cover what you can expect.' }}</p>
                    </div>

                    @if ($specs->isNotEmpty())
                        <div @class(['mt-10 grid grid-cols-2 gap-x-4 gap-y-6 border-y border-line py-6', 'sm:grid-cols-3' => $specs->count() === 3, 'sm:grid-cols-4' => $specs->count() >= 4])>
                            @foreach ($specs as $spec)
                                <div class="flex items-center gap-3">
                                    <x-line-icon :name="$spec['icon']" :size="22" class="text-clay" />
                                    <p class="flex flex-col text-[13px]"><b class="text-ink">{{ $spec['value'] }}</b><span class="mt-0.5 text-[11.5px] text-muted">{{ $spec['sub'] }}</span></p>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($staff->isNotEmpty())
                        <div class="mt-8">
                            <p class="dv-eyebrow">What makes it special</p>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                @foreach ($staff as [$label, $line])
                                    <div class="flex items-center gap-3 rounded-[0.9rem] bg-sand/60 px-4 py-3">
                                        <x-line-icon name="check" :size="18" class="text-clay" />
                                        <p class="text-[12.5px] leading-snug text-muted"><b class="block text-ink">{{ $label }}</b>{{ $line }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($seasons->isNotEmpty())
                        <div class="mt-8 rounded-[1rem] border border-line p-5">
                            <p class="font-dm-serif text-[1.25rem] text-forest">Seasonal rates</p>
                            <ul class="mt-2 divide-y divide-line text-[13px]">
                                @foreach ($seasons as $season)
                                    <li class="flex flex-wrap items-center justify-between gap-2 py-2.5">
                                        <span><b class="text-ink">{{ $season->description ?: 'Seasonal rate' }}</b> <span class="text-muted">· {{ \Carbon\Carbon::parse($season->start_date)->format('j M') }} – {{ \Carbon\Carbon::parse($season->end_date)->format('j M Y') }}</span></span>
                                        <span class="font-dm-serif text-[1.1rem] text-forest">{{ $currency }} {{ number_format((float) $season->rate) }} <small class="font-sans text-[11px] font-semibold text-muted">/ night</small></span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($rating >= 4.7)
                        <div class="mt-6 flex items-center gap-4 rounded-[0.9rem] bg-sand/60 px-5 py-4">
                            <x-line-icon name="star" :size="24" filled class="text-clay" />
                            <p class="text-[12.5px] leading-snug text-muted"><b class="block text-ink">A guest favourite</b>One of the most loved stays{{ $villa->city ? ' in '.$villa->city : '' }}, based on ratings and verified reviews.</p>
                        </div>
                    @endif
                </x-stay-detail.section>

                {{-- Availability --}}
                <x-stay-detail.section id="availability" eyebrow="Plan your dates" title="Availability">
                    <livewire:calendar.calendar-component :bookable="$villa" />
                </x-stay-detail.section>

                {{-- Amenities --}}
                <x-stay-detail.section id="amenities" eyebrow="What this place offers" title="Everything within reach">
                    <x-stay-detail.amenities :amenities="$villa->amenities" />
                </x-stay-detail.section>

                {{-- Reviews --}}
                <x-stay-detail.section id="reviews" eyebrow="Guest stories" title="What guests are saying">
                    @if ($reviews->isNotEmpty())
                        <div class="grid gap-4 md:grid-cols-2">
                            @foreach ($reviews as $review)
                                <x-stay-detail.review :review="$review" wire:key="review-{{ $review->id }}" />
                            @endforeach
                        </div>
                        @if ($reviews->hasPages())
                            <div class="mt-6 flex items-center justify-center gap-3">
                                <button type="button" wire:click="previousPage" @disabled($reviews->onFirstPage()) class="dv-btn dv-btn-ghost disabled:opacity-40">
                                    <x-line-icon name="chevron" :size="15" class="rotate-180" /> Newer
                                </button>
                                <span class="text-[12px] text-muted">Page {{ $reviews->currentPage() }} of {{ $reviews->lastPage() }}</span>
                                <button type="button" wire:click="nextPage" @disabled(! $reviews->hasMorePages()) class="dv-btn dv-btn-ghost disabled:opacity-40">
                                    Older <x-line-icon name="chevron" :size="15" />
                                </button>
                            </div>
                        @endif
                    @else
                        <x-stay-detail.reviews-empty />
                    @endif
                </x-stay-detail.section>

                {{-- Agent journeys that include this stay (renders nothing when there are none). --}}
                @include('packages::partials.journeys-rail', ['journeysFor' => $villa, 'journeysTitle' => 'Journeys that include '.$villa->name, 'journeysClass' => 'scroll-mt-[150px] border-b border-line py-12 md:py-14'])

                {{-- Location --}}
                <x-stay-detail.section id="location" eyebrow="The neighbourhood" title="Where you'll stay" last>
                    <x-stay-detail.location
                        :address="$villa->address"
                        :city="$villa->city"
                        :country="$villa->country"
                        :latitude="$villa->latitude"
                        :longitude="$villa->longitude"
                        :policies="$villa->policies"
                    />
                </x-stay-detail.section>
            </div>

            <div class="pb-12 lg:self-start lg:pb-16 lg:pt-12">
                <x-stay-detail.booking-card
                    :price="$price"
                    :currency="$currency"
                    :rating="$rating"
                    :reviews="$villa->reviews_count"
                    :guests-label="$villa->max_guests ? 'Up to '.$villa->max_guests.' guests' : null"
                    :note="$minNights > 1 ? 'Minimum stay of '.$minNights.' nights' : null"
                    :cancellation="$villa->policies['cancellation'] ?? null"
                />
            </div>
        </div>
    </div>

    {{-- Shared booking modal, opened by the calendar's book() action. --}}
    <livewire:forms.booking-form :villa="$villa" />
</div>
