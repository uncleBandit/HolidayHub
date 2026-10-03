@props([
    'label',
    'type' => 'text',
])

{{-- A form field in the discovery language: small-caps label, soft surface,
     forest focus. Errors are read from the field name so the markup stays
     declarative. --}}

@php
    $name = $attributes->get('name') ?? $attributes->get('wire:model');
    $id = $attributes->get('id') ?? $name;
    $hasError = $name && $errors->has($name);
@endphp

<div class="w-full">
    @if (filled($label))
        <label for="{{ $id }}" class="auth-label">{{ $label }}</label>
    @endif

    <input
        id="{{ $id }}"
        type="{{ $type }}"
        {{ $attributes->merge(['class' => 'auth-input '.($hasError ? 'auth-input-error' : '')]) }}
    >

    @if ($hasError)
        <span class="auth-error" role="alert">{{ $errors->first($name) }}</span>
    @endif
</div>