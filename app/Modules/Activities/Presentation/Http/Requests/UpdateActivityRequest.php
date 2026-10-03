<?php

namespace App\Modules\Activities\Presentation\Http\Requests;

use App\Modules\Activities\Domain\Models\Activity;
use Illuminate\Foundation\Http\FormRequest;

class UpdateActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $activity = $this->route('activity');

        return $activity instanceof Activity
            && ($this->user()?->can('update', $activity) ?? false);
    }

    public function rules(): array
    {
        return [
            'destination_id' => ['sometimes', 'required', 'integer', 'exists:destinations,id'],
            'category_id' => ['sometimes', 'required', 'integer', 'exists:activity_categories,id'],
            'hotel_id' => ['sometimes', 'nullable', 'integer', 'exists:hotels,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', 'nullable', 'string', 'max:100'],
            'description' => ['sometimes', 'required', 'string', 'min:30', 'max:20000'],
            'short_description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'highlights' => ['sometimes', 'array', 'max:20'],
            'highlights.*' => ['string', 'max:255'],
            'tags' => ['sometimes', 'array', 'max:20'],
            'tags.*' => ['string', 'max:50'],
            'base_price' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:10000000'],
            'currency' => ['sometimes', 'required', 'string', 'size:3'],
            'duration_minutes' => ['sometimes', 'required', 'integer', 'min:1', 'max:10080'],
            'capacity' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:10000'],
            'min_age' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:120'],
            'max_age' => ['sometimes', 'nullable', 'integer', 'gte:min_age', 'max:120'],
            'age_policy' => ['sometimes', 'nullable', 'array'],
            'age_policy.minimum_age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'age_policy.maximum_age' => ['nullable', 'integer', 'gte:age_policy.minimum_age', 'max:120'],
            'age_policy.requires_adult' => ['nullable', 'boolean'],
            'attributes' => ['sometimes', 'nullable', 'array'],
            'inclusions' => ['sometimes', 'nullable', 'array', 'max:50'],
            'inclusions.*' => ['string', 'max:255'],
            'exclusions' => ['sometimes', 'nullable', 'array', 'max:50'],
            'exclusions.*' => ['string', 'max:255'],
            'accessibility' => ['sometimes', 'nullable', 'array'],
            'accessibility.*' => ['boolean'],
            'booking_mode' => ['sometimes', 'string', 'in:shared,private,request,instant'],
            'booking_cutoff_minutes' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:525600'],
            'minimum_notice_minutes' => ['sometimes', 'integer', 'min:0', 'max:525600'],
            'timezone' => ['sometimes', 'required', 'timezone'],
            'weather_dependent' => ['sometimes', 'boolean'],
            'weather_cancellation_policy' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'safety_instructions' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'meta_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'meta_description' => ['sometimes', 'nullable', 'string', 'max:1000'],
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
