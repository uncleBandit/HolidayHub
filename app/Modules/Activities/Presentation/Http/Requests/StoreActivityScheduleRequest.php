<?php

namespace App\Modules\Activities\Presentation\Http\Requests;

use App\Modules\Activities\Domain\Models\Activity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActivityScheduleRequest extends FormRequest
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
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'different:start_time'],
            'timezone' => ['required', 'timezone'],
            'capacity' => ['required', 'integer', 'min:1', 'max:10000'],
            'active_from' => ['nullable', 'date_format:Y-m-d'],
            'active_until' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:active_from'],
            'booking_cutoff_minutes' => ['nullable', 'integer', 'min:0', 'max:525600'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
