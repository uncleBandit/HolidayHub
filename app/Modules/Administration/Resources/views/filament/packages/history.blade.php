@php
    $entries = collect(data_get($getRecord()->meta_data, 'moderation', []))->reverse()->values();
    $people = \App\Modules\Identity\Domain\Models\User::query()->whereIn('id', $entries->pluck('by')->filter()->unique())->pluck('name', 'id');
@endphp
@if ($entries->isEmpty())
    <p class="text-sm text-gray-500">No decisions yet.</p>
@else
    <ol style="display:flex;flex-direction:column;gap:.6rem">
        @foreach ($entries as $entry)
            <li style="font-size:.875rem">
                <span class="font-medium text-gray-950 dark:text-white">{{ str($entry['action'] ?? '')->headline() }}</span>
                <span class="text-gray-500"> · {{ str($entry['from'] ?? '')->replace('_', ' ') }} → {{ str($entry['to'] ?? '')->replace('_', ' ') }}
                    · {{ $people[$entry['by'] ?? 0] ?? 'the agent or system' }}
                    · {{ isset($entry['at']) ? \Illuminate\Support\Carbon::parse($entry['at'])->format('j M Y, H:i') : '' }}</span>
                @if (filled($entry['reason'] ?? null))
                    <span class="block text-gray-600 dark:text-gray-300">“{{ $entry['reason'] }}”</span>
                @endif
            </li>
        @endforeach
    </ol>
@endif
