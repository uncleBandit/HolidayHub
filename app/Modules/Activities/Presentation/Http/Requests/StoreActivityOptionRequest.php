<?php

namespace App\Modules\Activities\Presentation\Http\Requests;

use App\Modules\Activities\Domain\Models\Activity;
use Illuminate\Foundation\Http\FormRequest;

class StoreActivityOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $activity = $this->route('activity');

        return $activity instanceof Activity && ($this->user()?->can('update', $activity) ?? false);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:10080'],
            'base_price' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'currency' => ['required', 'string', 'size:3'],
            'included_participants' => ['sometimes', 'integer', 'min:1'],
            'max_participants' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'booking_mode' => ['sometimes', 'in:shared,private,request,instant'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
