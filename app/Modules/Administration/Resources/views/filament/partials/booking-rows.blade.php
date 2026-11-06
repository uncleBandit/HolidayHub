@php
    use App\Modules\Administration\Filament\Resources\BookingOperationsResource;
    use App\Shared\Support\Currencies;
@endphp
@if ($bookings->isEmpty())
    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $empty }}</p>
@else
    <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;font-size:.875rem">
            <thead>
                <tr class="text-gray-500 dark:text-gray-400" style="text-align:left;font-size:.72rem;text-transform:uppercase;letter-spacing:.05em">
                    <th style="padding:.4rem .5rem">Reference</th>
                    <th style="padding:.4rem .5rem">Listing</th>
                    <th style="padding:.4rem .5rem">Guest</th>
                    <th style="padding:.4rem .5rem">Dates</th>
                    <th style="padding:.4rem .5rem">Status</th>
                    <th style="padding:.4rem .5rem;text-align:right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($bookings as $booking)
                    @php
                        $guest = $booking->guest;
                        $guestName = $guest?->user?->name ?: trim(($guest?->first_name ?? '').' '.($guest?->last_name ?? ''));
                    @endphp
                    <tr class="border-t border-gray-100 dark:border-white/10">
                        <td style="padding:.5rem;font-weight:600;white-space:nowrap">
                            @if ($canOpen)
                                <a href="{{ BookingOperationsResource::getUrl('view', ['record' => $booking]) }}" class="text-primary-600 dark:text-primary-400">{{ $booking->confirmation_code ?: '#'.$booking->id }}</a>
                            @else
                                {{ $booking->confirmation_code ?: '#'.$booking->id }}
                            @endif
                        </td>
                        <td style="padding:.5rem" class="text-gray-700 dark:text-gray-200">{{ $booking->bookable?->name ?? str($booking->bookable_type)->headline() }}</td>
                        <td style="padding:.5rem" class="text-gray-600 dark:text-gray-300">{{ $guestName ?: '—' }}</td>
                        <td style="padding:.5rem;white-space:nowrap" class="text-gray-600 dark:text-gray-300">{{ $booking->check_in_date?->format('j M Y') ?? '—' }}@if ($booking->check_out_date) – {{ $booking->check_out_date->format('j M Y') }}@endif</td>
                        <td style="padding:.5rem" class="text-gray-600 dark:text-gray-300">{{ str($booking->status)->headline() }}</td>
                        <td style="padding:.5rem;text-align:right;white-space:nowrap;font-weight:600" class="text-gray-950 dark:text-white">{{ Currencies::format($booking->total_amount, $booking->currency, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
