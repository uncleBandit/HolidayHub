<?php

namespace App\Modules\Activities\Presentation\Http\Requests;

use App\Modules\Activities\Domain\Models\Activity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActivitySessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $activity = $this->route('activity');

        return $activity instanceof Activity && ($this->user()?->can('update', $activity) ?? false);
    }

    public function rules(): array
    {
        $activity = $this->route('activity');

        return [
            'activity_option_id' => [
                'nullable',
                'integer',
                Rule::exists('activity_options', 'id')->where('activity_id', $activity?->id),
            ],
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'timezone' => ['required', 'timezone'],
            'capacity' => ['required', 'integer', 'min:1', 'max:10000'],
            'booking_cutoff_at' => ['nullable', 'date', 'before:starts_at'],
        ];
    }
}
