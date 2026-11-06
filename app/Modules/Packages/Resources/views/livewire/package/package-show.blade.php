{{-- A package as guests see it: film-first hero, the journey reel, the
     itinerary day by day (each part links to its listing), what's included,
     and a booking card that quotes the whole journey live. --}}
@php
    use App\Support\Currencies;
    use App\Support\Media;

    $images = collect([$p->cover_image])->concat((array) ($p->gallery ?? []))->filter()->values()->all();
    $code = $p->getCurrency();
    $days = max(1, (int) $p->duration_days);
    $nights = (int) $p->components->where('kind', 'stay')->sum(fn ($c) => max(1, (int) $c->nights));
    $listings = $p->components->filter->isListing()->count();
    $agency = $p->agent?->agency_name ?: trim(($p->agent?->first_name ?? '').' '.($p->agent?->last_name ?? ''));
    $from = (float) $p->base_price;
    $icons = ['stay' => 'bed', 'experience' => 'compass', 'transfer' => 'arrow', 'free' => 'leaf', 'note' => 'bookmark'];
    $fmt = fn ($amount) => Currencies::format($amount, $code, (float) $amount >= 1000 ? 0 : 2);
@endphp

<div class="pb-24 lg:pb-0">
    @if ($preview)
        <div class="bg-[#F6EEDD] px-4 py-2.5 text-center text-[12.5px] font-bold text-[#6B5230]">
            Preview · this journey is {{ str_replace('_', ' ', $p->status) }} and guests can’t see or book it yet.
        </div>
    @endif

    <x-stay-detail.gallery :images="$images" :video="$p->cover_video" :name="$p->name" kind="villa" :back-url="route('dashboard')" />

    <div class="mx-auto max-w-[1250px] px-4 sm:px-6 lg:px-8">
        <x-stay-detail.heading
            :name="$p->name"
            :kind="'A '.$days.'-day journey'.($agency ? ' curated by '.$agency : '')"
            :verified="(bool) $p->agent?->isVerified()"
            :rating="(float) $p->avg_rating"
            :reviews="(int) $p->reviews_count"
            :location="$destinations->pluck('name')->implode(' · ')"
            :facts="[
                ['value' => $days, 'label' => \Illuminate\Support\Str::plural('Day', $days)],
                ['value' => $nights, 'label' => \Illuminate\Support\Str::plural('Night', $nights).' of stays'],
                ['value' => $listings, 'label' => 'Places to stay & do'],
            ]"
        />

        <div class="lg:grid lg:grid-cols-[minmax(0,1.55fr)_minmax(20rem,0.8fr)] lg:gap-14">
            <div class="min-w-0">
                {{-- The story --}}
                <x-stay-detail.section id="overview" eyebrow="The journey" :title="$p->short_description">
                    @if (filled($p->full_description))
                        <p class="dv-body whitespace-pre-line">{{ $p->full_description }}</p>
                    @endif
                    @if ($destinations->isNotEmpty())
                        <div class="mt-6 flex flex-wrap gap-2">
                            @foreach ($destinations as $destination)
                                <a href="{{ route('destination.show', $destination) }}" class="inline-flex items-center gap-1.5 rounded-full bg-sand/70 px-3 py-1.5 text-[12px] font-bold text-forest hover:bg-sand"><x-line-icon name="map" :size="13" /> {{ $destination->name }}</a>
                            @endforeach
                        </div>
                    @endif
                </x-stay-detail.section>

                {{-- The journey reel --}}
                @if ($p->scenes->isNotEmpty())
                    <section id="reel" class="scroll-mt-[150px] border-b border-line py-12 md:py-14">
                        <x-stay-detail.reel-walk
                            :scenes="$p->scenes"
                            :name="$p->name"
                            eyebrow="The journey reel"
                            heading="Travel it before you book"
                            noun="stop"
                            cta="Play the journey"
                            class="!py-0"
                        />
                    </section>
                @endif

                {{-- Day by day --}}
                <x-stay-detail.section id="itinerary" eyebrow="Day by day" title="The itinerary">
                    <ol class="relative space-y-8 before:absolute before:bottom-4 before:left-[1.2rem] before:top-4 before:w-px before:bg-line">
                        @foreach ($itinerary as $day)
                            <li class="relative flex gap-4 sm:gap-5" wire:key="it-day-{{ $day['day'] }}">
                                <span class="relative z-10 grid h-[2.4rem] w-[2.4rem] flex-none place-items-center rounded-full bg-forest font-dm-serif text-[1.05rem] text-white">{{ $day['day'] }}</span>
                                <div class="min-w-0 flex-1 pt-1">
                                    <p class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-clay">
                                        Day {{ $day['day'] }}@if ($quote) · {{ \Carbon\Carbon::parse($quote['start'])->addDays($day['day'] - 1)->format('D j M') }}@endif
                                    </p>
                                    <div class="mt-3 space-y-3">
                                        @forelse ($day['parts'] as $part)
                                            @if ($part['url'])
                                                <a href="{{ $part['url'] }}" class="group flex gap-4 overflow-hidden rounded-[1.1rem] border border-line bg-white p-3 transition-shadow hover:shadow-[0_20px_44px_-24px_rgba(20,36,28,0.45)]">
                                                    <img src="{{ $part['image'] }}" alt="{{ $part['title'] }}" class="h-24 w-28 flex-none rounded-[0.8rem] object-cover sm:h-28 sm:w-36" loading="lazy">
                                                    <div class="min-w-0 flex-1 py-0.5">
                                                        <p class="text-[10px] font-extrabold uppercase tracking-[0.12em] text-clay">{{ $part['label'] }}@if ($part['place']) · <span class="text-muted">{{ $part['place'] }}</span>@endif</p>
                                                        <p class="mt-0.5 font-dm-serif text-[1.25rem] leading-tight text-ink group-hover:text-forest">{{ $part['title'] }}</p>
                                                        <p class="mt-0.5 text-[12px] font-bold text-forest">
                                                            {{ collect([$part['detail'], $part['nights'] ? $part['nights'].' '.\Illuminate\Support\Str::plural('night', $part['nights']) : null, $part['minutes'] ? ($part['minutes'] >= 60 ? rtrim(rtrim(number_format($part['minutes'] / 60, 1), '0'), '.').' hours' : $part['minutes'].' minutes') : null])->filter()->implode(' · ') }}
                                                        </p>
                                                        @if ($part['text'])<p class="mt-1 line-clamp-2 text-[12.5px] text-muted">{{ $part['text'] }}</p>@endif
                                                        <span class="mt-1.5 inline-flex items-center gap-1 text-[11px] font-extrabold text-clay">See the {{ \Illuminate\Support\Str::lower($part['label']) }} <x-line-icon name="arrow-up-right" :size="12" /></span>
                                                    </div>
                                                </a>
                                            @else
                                                <div class="flex items-start gap-3 rounded-[1rem] bg-cream/70 px-4 py-3">
                                                    <span class="grid h-9 w-9 flex-none place-items-center rounded-full bg-white text-clay"><x-line-icon :name="$icons[$part['kind']] ?? 'bookmark'" :size="15" /></span>
                                                    <div class="min-w-0">
                                                        <p class="text-[13.5px] font-extrabold text-ink">{{ $part['title'] }}</p>
                                                        @if ($part['text'])<p class="text-[12.5px] text-muted">{{ $part['text'] }}</p>@endif
                                                    </div>
                                                </div>
                                            @endif
                                        @empty
                                            <p class="text-[13px] text-muted">A day at your own pace.</p>
                                        @endforelse
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </x-stay-detail.section>

                {{-- What's included --}}
                <x-stay-detail.section id="included" eyebrow="What’s covered" title="Included, and not">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="dv-panel p-6">
                            <h3 class="text-[12px] font-extrabold uppercase tracking-[0.1em] text-forest">Included</h3>
                            <ul class="mt-4 space-y-3">
                                @forelse ($included['in'] as $item)
                                    <li class="flex items-start gap-3 text-[14px] text-ink"><span class="mt-0.5 text-forest-light"><x-line-icon name="check" :size="16" :stroke="2.4" /></span>{{ $item }}</li>
                                @empty
                                    <li class="text-[13px] text-muted">Ask the agent for details.</li>
                                @endforelse
                            </ul>
                        </div>
                        <div class="dv-panel p-6">
                            <h3 class="text-[12px] font-extrabold uppercase tracking-[0.1em] text-clay">Not included</h3>
                            <ul class="mt-4 space-y-3">
                                @forelse ($included['out'] as $item)
                                    <li class="flex items-start gap-3 text-[14px] text-ink"><span class="mt-0.5 text-clay"><x-line-icon name="close" :size="16" :stroke="2.4" /></span>{{ $item }}</li>
                                @empty
                                    <li class="text-[13px] text-muted">Flights and anything not listed as included.</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </x-stay-detail.section>

                {{-- The agent --}}
                @if ($p->agent)
                    <x-stay-detail.section id="agent" eyebrow="Your agent" title="Who put it together" last>
                        <div class="flex items-start gap-4 rounded-[1.1rem] border border-line bg-white p-5">
                            <span class="grid h-14 w-14 flex-none place-items-center rounded-full bg-clay font-dm-serif text-[1.2rem] text-white">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($agency ?: 'HH', 0, 2)) }}</span>
                            <div class="min-w-0">
                                <p class="flex items-center gap-2 text-[15px] font-extrabold text-ink">{{ $agency }}
                                    @if ($p->agent->isVerified())<span class="inline-flex items-center gap-1 rounded-full bg-forest/10 px-2 py-0.5 text-[10px] font-extrabold text-forest"><x-line-icon name="check" :size="10" /> Verified agent</span>@endif
                                </p>
                                <p class="text-[12px] text-muted">{{ collect([$p->agent->city, $p->agent->country, $p->agent->specialization])->filter()->implode(' · ') }}</p>
                                @if ($p->agent->bio)<p class="mt-2 text-[13px] leading-relaxed text-ink">{{ $p->agent->bio }}</p>@endif
                                <p class="mt-2 text-[12px] text-muted">Every stay and experience is booked directly with its host on HolidayHub; your agent stays your one point of contact for the journey.</p>
                            </div>
                        </div>
                    </x-stay-detail.section>
                @endif
            </div>

            {{-- ─────────────────────────────  Booking card  ────────────────────── --}}
            <div class="pb-12 lg:self-start lg:pb-16 lg:pt-12">
                <aside id="book" class="scroll-mt-28 lg:sticky lg:top-[112px]">
                    <div class="dv-panel dv-panel-shadow overflow-hidden">
                        <div class="p-6 pb-4">
                            <p class="font-dm-serif text-[1.8rem] leading-none text-ink">
                                @if ($quote && $quote['total'] > 0)
                                    {{ $fmt($quote['per_person']) }} <small class="font-sans text-[12px] text-muted">/ person</small>
                                @elseif ($from > 0)
                                    <span class="font-sans text-[12px] font-bold text-muted">From</span> {{ $fmt($from) }} <small class="font-sans text-[12px] text-muted">/ person</small>
                                @else
                                    {{ $days }} days
                                @endif
                            </p>
                            <p class="mt-1 text-[11.5px] text-muted">Live prices of every stay and experience for your dates, in {{ $code }}.</p>

                            <div class="mt-5 overflow-hidden rounded-[0.8rem] border border-line">
                                <label class="block border-b border-line px-4 py-3 focus-within:bg-cream/60">
                                    <span class="flex items-center gap-1.5 text-[10px] font-extrabold uppercase tracking-[0.1em] text-ink"><x-line-icon name="calendar" :size="13" /> Start date</span>
                                    <input type="date" wire:model.live="startDate" min="{{ today()->addDay()->toDateString() }}" class="mt-1 w-full border-0 bg-transparent p-0 text-[14px] font-bold text-ink focus:ring-0">
                                </label>
                                <div class="flex items-center justify-between px-4 py-3">
                                    <span>
                                        <span class="flex items-center gap-1.5 text-[10px] font-extrabold uppercase tracking-[0.1em] text-ink"><x-line-icon name="users" :size="13" /> Guests</span>
                                        <span class="text-[11px] text-muted">{{ (int) $p->min_guests }}–{{ (int) $p->max_guests }} per booking</span>
                                    </span>
                                    <span class="flex items-center gap-2">
                                        <button type="button" wire:click="setGuests({{ $guests - 1 }})" @disabled($guests <= max(1, (int) $p->min_guests)) class="grid h-8 w-8 place-items-center rounded-full border border-line text-[16px] font-extrabold text-ink hover:border-forest disabled:opacity-40" aria-label="Fewer guests">−</button>
                                        <b class="w-6 text-center text-[15px] text-ink">{{ $guests }}</b>
                                        <button type="button" wire:click="setGuests({{ $guests + 1 }})" @disabled($guests >= (int) $p->max_guests) class="grid h-8 w-8 place-items-center rounded-full border border-line text-[16px] font-extrabold text-ink hover:border-forest disabled:opacity-40" aria-label="More guests">+</button>
                                    </span>
                                </div>
                            </div>

                            @if ($departures)
                                <div class="mt-3 flex flex-wrap items-center gap-1.5 text-[11px] text-muted">
                                    Next dates with everything free:
                                    @foreach ($departures as $departure)
                                        <button type="button" wire:click="pickDate('{{ $departure['date'] }}')" @class(['rounded-full px-2.5 py-1 font-extrabold', 'bg-forest text-white' => $startDate === $departure['date'], 'bg-cream text-forest hover:bg-sand' => $startDate !== $departure['date']])>{{ \Carbon\Carbon::parse($departure['date'])->format('D j M') }}</button>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div wire:loading.class="opacity-60" wire:target="startDate,setGuests,pickDate">
                            @if ($quote)
                                @include('packages::partials.quote-table', ['quote' => $quote, 'agentView' => false])
                            @endif
                        </div>

                        <div class="p-6 pt-4">
                            @error('booking')<p role="alert" class="auth-alert auth-alert-error mb-3">{{ $message }}</p>@enderror
                            @error('startDate')<p class="auth-error mb-2">{{ $message }}</p>@enderror
                            @error('guests')<p class="auth-error mb-2">{{ $message }}</p>@enderror
                            <button type="button" wire:click="book" @disabled($preview || ! $quote || ! $quote['available']) wire:loading.attr="disabled" wire:target="book" class="dv-btn dv-btn-primary w-full !min-h-[3.3rem] !text-[13px] disabled:cursor-not-allowed disabled:!bg-muted/50">
                                <span wire:loading.remove wire:target="book">{{ $quote && $quote['available'] ? 'Book the journey · '.$fmt($quote['total']) : 'Choose another date' }}</span>
                                <span wire:loading wire:target="book">Booking every stay and experience…</span>
                            </button>
                            <p class="mt-3 text-center text-[11px] text-muted">One booking, one payment. If any part isn’t free, nothing is booked.</p>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>

    {{-- ─────────────────────────────  Mobile book bar  ─────────────────── --}}
    <div class="fixed inset-x-0 bottom-[4.8rem] z-40 flex items-center justify-between gap-3 border-t border-line bg-surface/95 px-4 py-3 backdrop-blur-xl md:bottom-0 lg:hidden" data-no-reveal>
        <div class="min-w-0">
            <span class="text-[11px] text-muted">{{ $days }} days · {{ $guests }} {{ \Illuminate\Support\Str::plural('guest', $guests) }}</span>
            <p class="font-dm-serif text-[1.15rem] leading-tight text-ink">{{ $quote && $quote['total'] > 0 ? $fmt($quote['total']) : ($from > 0 ? 'From '.$fmt($from).' pp' : '') }}</p>
        </div>
        <a href="#book" class="dv-btn dv-btn-primary flex-none">Book the journey</a>
    </div>
</div>
