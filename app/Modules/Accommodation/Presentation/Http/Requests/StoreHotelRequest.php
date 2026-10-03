<?php

namespace App\Modules\Accommodation\Presentation\Http\Requests;

use App\Modules\Accommodation\Domain\Models\Hotel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;

class StoreHotelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Hotel::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:hotels,slug',
            'description' => 'nullable|string',
            'address' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'destination_id' => 'required|integer|exists:destinations,id',
            'provider_id' => Auth::user()?->isPlatformAdmin()
                ? 'required|integer|exists:providers,id'
                : 'prohibited',
            'policies' => 'required|array',
            'stars' => 'required|integer|min:1|max:5',
            'avg_price_per_night' => 'required|numeric|min:0',
        ];
    }
}
