<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHotelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
      public function rules(): array
    {
        return [
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'location'    => 'required|string|max:255',
            'price_per_night' => 'required|numeric|min:0',
            'rating'      => 'nullable|numeric|min:0|max:5',
            'amenities'   => 'nullable|array',
            'image'       => 'nullable|image|max:2048'
        ];
    }
}
