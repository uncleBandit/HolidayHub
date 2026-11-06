@php
    $th = 'text-align: start; padding: 0.5rem 0.75rem; font-weight: 600; font-size: 0.8125rem; border-bottom: 1px solid rgba(127,127,127,0.25); white-space: nowrap;';
    $td = 'padding: 0.5rem 0.75rem; font-size: 0.875rem; border-bottom: 1px solid rgba(127,127,127,0.12);';
    $num = $td.' text-align: end; font-variant-numeric: tabular-nums; white-space: nowrap;';
    $thNum = $th.' text-align: end;';
@endphp

<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Period</x-slot>
        <x-slot name="description">
            Every amount is per currency; amounts in different currencies are never added together.
            Package trips count once in gross value (their component bookings are excluded) and their components count towards each provider's payable.
        </x-slot>

        <div style="display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr));">
            <label>
                <span style="display:block; font-size: 0.875rem; margin-bottom: 0.25rem;">From</span>
                <x-filament::input.wrapper>
                    <x-filament::input type="date" wire:model.live="from" />
                </x-filament::input.wrapper>
            </label>
            <label>
                <span style="display:block; font-size: 0.875rem; margin-bottom: 0.25rem;">To</span>
                <x-filament::input.wrapper>
                    <x-filament::input type="date" wire:model.live="to" />
                </x-filament::input.wrapper>
            </label>
            <label>
                <span style="display:block; font-size: 0.875rem; margin-bottom: 0.25rem;">Dates apply to</span>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="basis">
                        <option value="created_at">When the booking was made</option>
                        <option value="check_in_date">Check-in date</option>
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </label>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Gross booking value</x-slot>
        <x-slot name="description">Top-level bookings that are not cancelled.</x-slot>
        @if ($gross->isEmpty())
            <p style="opacity: 0.7;">No bookings in this period.</p>
        @else
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead><tr><th style="{{ $th }}">Currency</th><th style="{{ $thNum }}">Bookings</th><th style="{{ $thNum }}">Gross value</th><th style="{{ $thNum }}">Of which paid</th></tr></thead>
                    <tbody>
                        @foreach ($gross as $row)
                            <tr>
                                <td style="{{ $td }}">{{ $row->currency }}</td>
                                <td style="{{ $num }}">{{ number_format($row->bookings) }}</td>
                                <td style="{{ $num }} font-weight: 600;">{{ $money($row->gross, $row->currency) }}</td>
                                <td style="{{ $num }}">{{ $money($row->paid, $row->currency) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Provider payables</x-slot>
        <x-slot name="description">Confirmed, in-progress and completed bookings each provider fulfils, including package components. Select a provider to see its bookings.</x-slot>
        @if ($payables->isEmpty())
            <p style="opacity: 0.7;">Nothing payable in this period.</p>
        @else
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead><tr><th style="{{ $th }}">Provider</th><th style="{{ $th }}">Currency</th><th style="{{ $thNum }}">Bookings</th><th style="{{ $thNum }}">Package components</th><th style="{{ $thNum }}">Payable</th><th style="{{ $thNum }}">Paid in by guests</th></tr></thead>
                    <tbody>
                        @foreach ($payables as $row)
                            <tr wire:key="payable-{{ $row->provider_id ?? 'none' }}-{{ $row->currency }}" @style(['background: rgba(127,127,127,0.08)' => $provider && $provider === (int) $row->provider_id])>
                                <td style="{{ $td }}">
                                    @if ($row->provider_id)
                                        <x-filament::link tag="button" wire:click="selectProvider({{ (int) $row->provider_id }})">
                                            {{ $row->company_name ?? 'Provider #'.$row->provider_id }}
                                        </x-filament::link>
                                    @else
                                        <span style="opacity: 0.7;">Unresolved listing owner</span>
                                    @endif
                                </td>
                                <td style="{{ $td }}">{{ $row->currency }}</td>
                                <td style="{{ $num }}">{{ number_format($row->bookings) }}</td>
                                <td style="{{ $num }}">{{ number_format($row->package_components) }}</td>
                                <td style="{{ $num }} font-weight: 600;">{{ $money($row->payable, $row->currency) }}</td>
                                <td style="{{ $num }}">{{ $money($row->paid_in, $row->currency) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>

    @if ($provider)
        <x-filament::section>
            <x-slot name="heading">{{ $providerName ?? 'Provider #'.$provider }}: payable bookings</x-slot>
            <x-slot name="afterHeader">
                <x-filament::link :href="$providerBookingsUrl" icon="heroicon-m-arrow-top-right-on-square">Open in bookings</x-filament::link>
            </x-slot>
            @if ($providerBookings->isEmpty())
                <p style="opacity: 0.7;">No payable bookings in this period.</p>
            @else
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead><tr><th style="{{ $th }}">Reference</th><th style="{{ $th }}">Type</th><th style="{{ $th }}">Dates</th><th style="{{ $th }}">Status</th><th style="{{ $th }}">Payment</th><th style="{{ $thNum }}">Amount</th></tr></thead>
                        <tbody>
                            @foreach ($providerBookings as $booking)
                                <tr wire:key="pb-{{ $booking->id }}">
                                    <td style="{{ $td }}">
                                        <x-filament::link :href="\App\Modules\Administration\Filament\Resources\BookingOperationsResource::getUrl('view', ['record' => $booking->id])">{{ $booking->confirmation_code }}</x-filament::link>
                                        @if ($booking->package_booking_id)
                                            <div style="font-size: 0.75rem; opacity: 0.7;">part of {{ $booking->packageBooking?->confirmation_code }}</div>
                                        @endif
                                    </td>
                                    <td style="{{ $td }}">{{ \App\Modules\Administration\Application\Services\BookingLedger::LISTING_TYPES[$booking->bookable_type] ?? $booking->bookable_type }}</td>
                                    <td style="{{ $td }} white-space: nowrap;">{{ $booking->check_in_date?->toFormattedDateString() }} – {{ $booking->check_out_date?->toFormattedDateString() }}</td>
                                    <td style="{{ $td }}">{{ $booking->status }}</td>
                                    <td style="{{ $td }}">{{ $booking->payment_status }}</td>
                                    <td style="{{ $num }}">{{ $money($booking->total_amount, $booking->currency) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    @endif

    <x-filament::section>
        <x-slot name="heading">Agent commission</x-slot>
        <x-slot name="description">From bookings.agent_commission on bookings that are not cancelled.</x-slot>
        @if ($commission->isEmpty())
            <p style="opacity: 0.7;">No agent commission in this period.</p>
        @else
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead><tr><th style="{{ $th }}">Agent</th><th style="{{ $th }}">Currency</th><th style="{{ $thNum }}">Bookings</th><th style="{{ $thNum }}">Booked value</th><th style="{{ $thNum }}">Commission</th></tr></thead>
                    <tbody>
                        @foreach ($commission as $row)
                            <tr>
                                <td style="{{ $td }}">{{ $row->agent_name ?: 'Unresolved agent' }}</td>
                                <td style="{{ $td }}">{{ $row->currency }}</td>
                                <td style="{{ $num }}">{{ number_format($row->bookings) }}</td>
                                <td style="{{ $num }}">{{ $money($row->booked, $row->currency) }}</td>
                                <td style="{{ $num }} font-weight: 600;">{{ $money($row->commission, $row->currency) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Cancellations and refunds</x-slot>
        <x-slot name="description">Top-level bookings. Refunds are recorded by operators; no payment gateway is connected.</x-slot>
        @if ($cancellations->isEmpty())
            <p style="opacity: 0.7;">No cancellations or refunds in this period.</p>
        @else
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead><tr><th style="{{ $th }}">Currency</th><th style="{{ $thNum }}">Cancelled</th><th style="{{ $thNum }}">Cancelled value</th><th style="{{ $thNum }}">Refunded</th><th style="{{ $thNum }}">Refunded value</th></tr></thead>
                    <tbody>
                        @foreach ($cancellations as $row)
                            <tr>
                                <td style="{{ $td }}">{{ $row->currency }}</td>
                                <td style="{{ $num }}">{{ number_format($row->cancelled) }}</td>
                                <td style="{{ $num }}">{{ $money($row->cancelled_value, $row->currency) }}</td>
                                <td style="{{ $num }}">{{ number_format($row->refunded) }}</td>
                                <td style="{{ $num }}">{{ $money($row->refunded_value, $row->currency) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
