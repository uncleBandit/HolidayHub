{{-- A PackagePricing quote as a list of lines and totals. `agentView` also
     shows the provider subtotal and the margin; guests see one price per line
     (margin spread proportionally) and the total. --}}
@php
    use App\Shared\Support\Currencies;

    $agentView = $agentView ?? false;
    $code = $quote['currency'];
    $fmt = fn ($amount, $c = null) => Currencies::format($amount, $c ?? $code, (float) $amount >= 1000 ? 0 : 2);
    $share = (! $agentView && $quote['listing_subtotal'] > 0) ? 1 + $quote['margin'] / $quote['listing_subtotal'] : 1;
    $icons = ['stay' => 'bed', 'experience' => 'compass', 'transfer' => 'arrow', 'free' => 'leaf', 'note' => 'bookmark'];
@endphp

<div>
    <ul class="divide-y divide-line">
        @foreach ($quote['lines'] as $line)
            @continue($line['kind'] === 'note')
            <li class="flex items-start gap-3 px-4 py-3 sm:px-5" wire:key="ql-{{ $line['component_id'] }}">
                <span @class(['mt-0.5 grid h-8 w-8 flex-none place-items-center rounded-full', 'bg-cream text-clay' => $line['available'], 'bg-[#F6D6C9] text-clay-dark' => ! $line['available']])><x-line-icon :name="$icons[$line['kind']] ?? 'bookmark'" :size="14" /></span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-[13px] font-extrabold text-ink">{{ $line['title'] }}</p>
                    <p class="text-[11px] text-muted">
                        Day {{ $line['day'] }}
                        · {{ \Carbon\Carbon::parse($line['date'])->format('D j M') }}
                        @if ($line['nights']) · {{ $line['nights'] }} {{ Str::plural('night', $line['nights']) }}@endif
                        @if (! empty($line['starts_at'])) · {{ \Carbon\Carbon::parse($line['starts_at'])->setTimezone($line['timezone'] ?? config('app.timezone'))->format('g:i A') }}@endif
                        @if ($agentView && $line['converted'] && $line['listing_amount'] !== null) · {{ $fmt($line['listing_amount'], $line['listing_currency']) }} converted @endif
                    </p>
                    @unless ($line['available'])
                        <p class="mt-0.5 text-[11.5px] font-bold text-clay-dark">{{ $line['reason'] }}</p>
                    @endunless
                </div>
                <span class="whitespace-nowrap text-right text-[13px] font-extrabold {{ $line['available'] ? 'text-ink' : 'text-muted line-through' }}">
                    @if ($line['amount'] === null)
                        —
                    @elseif ((float) $line['amount'] <= 0)
                        <span class="text-[11.5px] font-bold text-forest">Included</span>
                    @else
                        {{ $fmt($line['listing'] ? $line['amount'] * $share : $line['amount']) }}
                        @if ($line['converted'])<span class="block text-[9.5px] font-bold uppercase tracking-[0.08em] text-muted">≈ converted</span>@endif
                    @endif
                </span>
            </li>
        @endforeach
    </ul>

    <dl class="space-y-1.5 border-t border-line bg-cream/40 px-4 py-3 text-[12.5px] sm:px-5">
        @if ($agentView)
            <div class="flex justify-between"><dt class="text-muted">Listings, at live prices</dt><dd class="font-bold text-ink">{{ $fmt($quote['listing_subtotal']) }}</dd></div>
            @if ($quote['extras_subtotal'] > 0)
                <div class="flex justify-between"><dt class="text-muted">Your extras</dt><dd class="font-bold text-ink">{{ $fmt($quote['extras_subtotal']) }}</dd></div>
            @endif
            <div class="flex justify-between"><dt class="text-muted">Provider subtotal</dt><dd class="font-bold text-ink">{{ $fmt($quote['subtotal']) }}</dd></div>
            <div class="flex justify-between"><dt class="font-bold text-clay">Your margin (commission)</dt><dd class="font-extrabold text-clay">+ {{ $fmt($quote['margin']) }}</dd></div>
        @endif
        <div class="flex items-baseline justify-between border-t border-line pt-2">
            <dt class="text-[13px] font-extrabold text-ink">Total for {{ $quote['guests'] }} {{ Str::plural('guest', $quote['guests']) }}</dt>
            <dd class="font-dm-serif text-[1.45rem] leading-none text-forest">{{ $fmt($quote['total']) }}</dd>
        </div>
        <div class="flex justify-between text-[11.5px] text-muted"><dt>Per person</dt><dd>{{ $fmt($quote['per_person']) }}</dd></div>
    </dl>

    @if ($quote['errors'] || $quote['unavailable'])
        <p class="border-t border-line px-4 py-2.5 text-[11.5px] font-bold text-clay-dark sm:px-5">
            {{ $quote['errors'][0] ?? ($quote['unavailable'].' '.Str::plural('part', $quote['unavailable']).' can’t be booked on these dates. Try another start date.') }}
        </p>
    @endif
    @if ($quote['converted'])
        <p class="border-t border-line px-4 py-2.5 text-[11px] text-muted sm:px-5">≈ Some prices are converted from the listing’s currency at an approximate rate; each host is paid in their own currency.</p>
    @endif
</div>
