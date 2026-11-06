@php
    $tones = [
        'published' => 'background:#DCF2E6;color:#1F6B45',
        'approved' => 'background:#DCF2E6;color:#1F6B45',
        'pending_review' => 'background:#FBEFD5;color:#8A5A2B',
        'submitted' => 'background:#FBEFD5;color:#8A5A2B',
        'under_review' => 'background:#E3ECFA;color:#2B4F8A',
        'rejected' => 'background:#F8DADA;color:#9B2C2C',
        'suspended' => 'background:#F8DADA;color:#9B2C2C',
    ];
@endphp
@if ($rows->isEmpty())
    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $empty }}</p>
@else
    <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;font-size:.875rem">
            <thead>
                <tr class="text-gray-500 dark:text-gray-400" style="text-align:left;font-size:.72rem;text-transform:uppercase;letter-spacing:.05em">
                    <th style="padding:.4rem .5rem">Listing</th>
                    <th style="padding:.4rem .5rem">Type</th>
                    <th style="padding:.4rem .5rem">Status</th>
                    @if ($rows->contains(fn ($row) => array_key_exists('price', $row)))
                        <th style="padding:.4rem .5rem;text-align:right">Price</th>
                    @endif
                    <th style="padding:.4rem .5rem;text-align:right">Links</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr class="border-t border-gray-100 dark:border-white/10">
                        <td style="padding:.5rem;font-weight:600" class="text-gray-950 dark:text-white">{{ $row['name'] }}</td>
                        <td style="padding:.5rem" class="text-gray-600 dark:text-gray-300">{{ $row['kind'] }}</td>
                        <td style="padding:.5rem">
                            <span style="display:inline-block;padding:.1rem .55rem;border-radius:999px;font-size:.72rem;font-weight:600;{{ $tones[$row['status']] ?? 'background:#EEE;color:#555' }}">{{ str($row['status'] ?? 'unknown')->headline() }}</span>
                        </td>
                        @if (array_key_exists('price', $row))
                            <td style="padding:.5rem;text-align:right;white-space:nowrap" class="text-gray-600 dark:text-gray-300">{{ $row['price'] ?? '—' }}</td>
                        @endif
                        <td style="padding:.5rem;text-align:right;white-space:nowrap">
                            @if ($row['moderation'])
                                <a href="{{ $row['moderation'] }}" class="text-primary-600 dark:text-primary-400" style="font-weight:600">Review</a>
                            @endif
                            @if ($row['public'])
                                <a href="{{ $row['public'] }}" target="_blank" rel="noopener" class="text-primary-600 dark:text-primary-400" style="font-weight:600;margin-left:.75rem">Public page ↗</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
