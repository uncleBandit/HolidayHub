<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        // ✅ You can add role-based logic (e.g., only agents/providers can create)
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:packages,slug'],
            'description' => ['required', 'string'],
            'destination' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:100'],

            // Pricing
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'], // e.g., USD, EUR, KES
            'discount' => ['nullable', 'numeric', 'between:0,100'],

            // Duration
            'duration_days' => ['required', 'integer', 'min:1'],

            // Availability
            'available_from' => ['nullable', 'date'],
            'available_to' => ['nullable', 'date', 'after_or_equal:available_from'],

            // Tags & media
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'images' => ['nullable', 'array'],
            'images.*' => ['url'],

            // Status control
            'status' => ['required', 'in:draft,active,inactive'],

            // Provider / Agent
            'provider_id' => ['nullable', 'exists:users,id'],
        ];
    }
}
