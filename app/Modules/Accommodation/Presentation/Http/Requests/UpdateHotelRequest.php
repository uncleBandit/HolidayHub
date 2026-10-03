<?php

namespace App\Modules\Accommodation\Presentation\Http\Requests;

use App\Modules\Accommodation\Domain\Models\Hotel;
use Illuminate\Foundation\Http\FormRequest;

class UpdateHotelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $hotel = $this->route('hotel');

        return $hotel instanceof Hotel && ($this->user()?->can('update', $hotel) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'address' => 'nullable|string|max:255',
            'city' => 'sometimes|string|max:255',
            'country' => 'sometimes|string|max:255',
            'destination_id' => 'sometimes|integer|exists:destinations,id',
            'policies' => 'sometimes|array',
            'stars' => 'sometimes|integer|min:1|max:5',
            'avg_price_per_night' => 'sometimes|numeric|min:0',
        ];
    }
}
