<x-filament-widgets::widget>
    <x-filament::section heading="Waiting for review" description="Oldest first, across stays, experiences and journeys.">
        @if ($items->isEmpty())
            <div style="display:flex;align-items:center;gap:.6rem;padding:.4rem 0;color:#275848;font-size:.9rem">
                <x-filament::icon icon="heroicon-o-check-circle" style="width:1.3rem;height:1.3rem" />
                The review queue is clear.
            </div>
        @else
            <ul style="display:flex;flex-direction:column;gap:.15rem;margin:-.25rem 0">
                @foreach ($items as $item)
                    <li>
                        <a href="{{ $item['url'] }}" style="display:flex;align-items:center;gap:.8rem;padding:.6rem .5rem;border-radius:.6rem" class="hover:bg-gray-50 dark:hover:bg-white/5">
                            <span style="flex:none;min-width:5.6rem;text-align:center;padding:.15rem .5rem;border-radius:999px;font-size:.68rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;background:#F6EEDD;color:#8A5A2B">{{ $item['kind'] }}</span>
                            <span style="flex:1;min-width:0">
                                <span style="display:block;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" class="text-gray-950 dark:text-white">{{ $item['name'] }}</span>
                                <span style="display:block;font-size:.78rem" class="text-gray-500 dark:text-gray-400">{{ $item['by'] ?: 'Unknown host' }}</span>
                            </span>
                            <span style="flex:none;font-size:.78rem;white-space:nowrap" class="{{ $item['waiting'] && $item['waiting']->lt(now()->subDays(2)) ? 'text-danger-600' : 'text-gray-500 dark:text-gray-400' }}">
                                {{ $item['waiting'] ? 'waiting '.$item['waiting']->diffForHumans(null, true) : 'not dated' }}
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
            @if ($total > $items->count())
                <p style="margin-top:.6rem;font-size:.78rem" class="text-gray-500">Showing the {{ $items->count() }} oldest of {{ $total }} waiting.</p>
            @endif
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
