<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        // ✅ Example: ensure user can only update their own packages
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:packages,slug,' . $this->package->id],
            'description' => ['sometimes', 'string'],
            'destination' => ['sometimes', 'string', 'max:255'],
            'country' => ['sometimes', 'string', 'max:100'],

            'price' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'discount' => ['nullable', 'numeric', 'between:0,100'],

            'duration_days' => ['sometimes', 'integer', 'min:1'],

            'available_from' => ['nullable', 'date'],
            'available_to' => ['nullable', 'date', 'after_or_equal:available_from'],

            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'images' => ['nullable', 'array'],
            'images.*' => ['url'],

            'status' => ['sometimes', 'in:draft,active,inactive'],
            'provider_id' => ['nullable', 'exists:users,id'],
        ];
    }
}
