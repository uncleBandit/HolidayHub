@php
    use App\Shared\Support\Currencies;

    $activity = $getRecord();
    $sessions = $activity->relationLoaded('sessions') ? $activity->sessions : collect();
    $upcoming = $sessions->filter(fn ($session) => $session->starts_at && $session->starts_at->isFuture())->sortBy('starts_at')->values();
    $options = $activity->relationLoaded('options') ? $activity->options : collect();
@endphp

<div style="display:flex;flex-direction:column;gap:1.25rem">
    <div>
        <div style="font-size:.72rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;margin-bottom:.4rem" class="text-gray-500 dark:text-gray-400">Price</div>
        <div style="font-size:1.1rem;font-weight:700" class="text-gray-950 dark:text-white">
            {{ $activity->base_price !== null ? Currencies::format($activity->base_price, $activity->currency, 2) : '—' }}
            <span class="text-gray-500" style="font-size:.8rem;font-weight:500">{{ $activity->currency ?: 'no currency set' }} · per person</span>
        </div>
        @if ($options->isNotEmpty())
            <ul style="margin:.5rem 0 0;padding:0;list-style:none;font-size:.875rem">
                @foreach ($options as $option)
                    <li class="text-gray-700 dark:text-gray-200">
                        {{ $option->name ?? 'Option '.$loop->iteration }}
                        @if ($option->base_price !== null)
                            · {{ Currencies::format($option->base_price, $option->currency ?: $activity->currency, 2) }}
                        @endif
                        @if (! $option->is_active)
                            <span class="text-gray-500">(inactive)</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div>
        <div style="font-size:.72rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;margin-bottom:.4rem" class="text-gray-500 dark:text-gray-400">
            Upcoming sessions ({{ $upcoming->count() }} of {{ $sessions->count() }})
        </div>
        @if ($upcoming->isEmpty())
            <p class="text-sm {{ $sessions->isEmpty() ? 'text-warning-600' : 'text-gray-500 dark:text-gray-400' }}">
                {{ $sessions->isEmpty() ? 'No sessions have been created.' : 'No upcoming sessions.' }}
            </p>
        @else
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;font-size:.875rem">
                    <thead>
                        <tr class="text-gray-500 dark:text-gray-400" style="text-align:left;font-size:.72rem;text-transform:uppercase;letter-spacing:.05em">
                            <th style="padding:.4rem .5rem">Starts</th>
                            <th style="padding:.4rem .5rem">Ends</th>
                            <th style="padding:.4rem .5rem">Timezone</th>
                            <th style="padding:.4rem .5rem;text-align:right">Booked / capacity</th>
                            <th style="padding:.4rem .5rem">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($upcoming->take(20) as $session)
                            <tr class="border-t border-gray-100 dark:border-white/10">
                                <td style="padding:.5rem;white-space:nowrap" class="text-gray-950 dark:text-white">{{ $session->starts_at->format('D j M Y, H:i') }}</td>
                                <td style="padding:.5rem;white-space:nowrap" class="text-gray-600 dark:text-gray-300">{{ $session->ends_at?->format('H:i') ?? '—' }}</td>
                                <td style="padding:.5rem" class="text-gray-600 dark:text-gray-300">{{ $session->timezone ?: $activity->timezone ?: '—' }}</td>
                                <td style="padding:.5rem;text-align:right" class="text-gray-600 dark:text-gray-300">{{ (int) $session->booked_capacity }} / {{ $session->capacity ?? '—' }}</td>
                                <td style="padding:.5rem" class="text-gray-600 dark:text-gray-300">{{ str($session->status instanceof \BackedEnum ? $session->status->value : $session->status)->headline() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($upcoming->count() > 20)
                <p class="text-xs text-gray-500" style="margin-top:.4rem">Showing the next 20.</p>
            @endif
        @endif
    </div>
</div>
