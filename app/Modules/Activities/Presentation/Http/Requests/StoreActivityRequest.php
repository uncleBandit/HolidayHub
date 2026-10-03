<?php

namespace App\Modules\Activities\Presentation\Http\Requests;

use App\Modules\Activities\Domain\Models\Activity;
use Illuminate\Foundation\Http\FormRequest;

class StoreActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Activity::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'destination_id' => ['required', 'integer', 'exists:destinations,id'],
            'category_id' => ['required', 'integer', 'exists:activity_categories,id'],
            'hotel_id' => ['nullable', 'integer', 'exists:hotels,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:100'],
            'description' => ['required', 'string', 'min:30', 'max:20000'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'highlights' => ['sometimes', 'array', 'max:20'],
            'highlights.*' => ['string', 'max:255'],
            'tags' => ['sometimes', 'array', 'max:20'],
            'tags.*' => ['string', 'max:50'],
            'base_price' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'currency' => ['required', 'string', 'size:3'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:10080'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'min_age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'max_age' => ['nullable', 'integer', 'gte:min_age', 'max:120'],
            'age_policy' => ['nullable', 'array'],
            'age_policy.minimum_age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'age_policy.maximum_age' => ['nullable', 'integer', 'gte:age_policy.minimum_age', 'max:120'],
            'age_policy.requires_adult' => ['nullable', 'boolean'],
            'attributes' => ['nullable', 'array'],
            'inclusions' => ['nullable', 'array', 'max:50'],
            'inclusions.*' => ['string', 'max:255'],
            'exclusions' => ['nullable', 'array', 'max:50'],
            'exclusions.*' => ['string', 'max:255'],
            'accessibility' => ['nullable', 'array'],
            'accessibility.*' => ['boolean'],
            'booking_mode' => ['sometimes', 'string', 'in:shared,private,request,instant'],
            'booking_cutoff_minutes' => ['nullable', 'integer', 'min:0', 'max:525600'],
            'minimum_notice_minutes' => ['sometimes', 'integer', 'min:0', 'max:525600'],
            'timezone' => ['required', 'timezone'],
            'weather_dependent' => ['sometimes', 'boolean'],
            'weather_cancellation_policy' => ['nullable', 'string', 'max:2000'],
            'safety_instructions' => ['nullable', 'string', 'max:5000'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:1000'],
            'is_featured' => ['prohibited'],
            'is_active' => ['prohibited'],
            'provider_id' => ['prohibited'],
            'status' => ['prohibited'],
            'verification_status' => ['prohibited'],
            'rating' => ['prohibited'],
            'reviews_count' => ['prohibited'],
            'bookings_count' => ['prohibited'],
            'slug' => ['prohibited'],
        ];
    }
}
