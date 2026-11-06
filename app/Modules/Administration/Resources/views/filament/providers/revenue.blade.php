@php
    use App\Modules\Administration\Filament\Resources\ProviderResource;
    use App\Support\Currencies;

    $revenue = ProviderResource::revenueFor($getRecord());
@endphp
@if ($revenue->isEmpty())
    <p class="text-sm text-gray-500 dark:text-gray-400">No earning bookings yet.</p>
@else
    <div style="display:flex;flex-wrap:wrap;gap:.75rem">
        @foreach ($revenue as $row)
            <div style="min-width:11rem;padding:.75rem 1rem;border-radius:.75rem" class="bg-gray-50 dark:bg-white/5 ring-1 ring-gray-950/5 dark:ring-white/10">
                <div style="font-size:.72rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase" class="text-gray-500 dark:text-gray-400">{{ strtoupper($row->currency ?: '—') }}</div>
                <div style="font-size:1.25rem;font-weight:700" class="text-gray-950 dark:text-white">{{ Currencies::format($row->total, $row->currency, 2) }}</div>
                <div style="font-size:.78rem" class="text-gray-500 dark:text-gray-400">{{ $row->bookings }} {{ \Illuminate\Support\Str::plural('booking', (int) $row->bookings) }}</div>
            </div>
        @endforeach
    </div>
@endif
