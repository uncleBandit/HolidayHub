@props(['rating'])

<div class="flex text-yellow-500">
    @for ($i = 1; $i <= 5; $i++)
        <x-heroicon-s-star class="w-6 h-6 {{ $rating >= $i ? 'text-yellow-500' : 'text-gray-300' }}" />
    @endfor
</div>
