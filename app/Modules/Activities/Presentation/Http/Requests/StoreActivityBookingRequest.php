<?php

namespace App\Modules\Activities\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreActivityBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'participants' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
