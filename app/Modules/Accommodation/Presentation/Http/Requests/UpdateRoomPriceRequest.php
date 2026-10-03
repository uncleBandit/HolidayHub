<?php

namespace App\Modules\Accommodation\Presentation\Http\Requests;

use App\Modules\Accommodation\Domain\Models\RoomPrice;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $roomPrice = $this->route('room_price') ?? $this->route('roomPrice');

        return $roomPrice instanceof RoomPrice && ($this->user()?->can('update', $roomPrice) ?? false);
    }

    public function rules(): array
    {
        return [
            'room_id' => 'prohibited',
            'base_price' => 'sometimes|numeric|gt:0',
            'discount_price' => 'sometimes|nullable|numeric|min:0',
            'start_date' => 'sometimes|nullable|date',
            'end_date' => 'sometimes|nullable|date|after_or_equal:start_date',
            'meta' => 'sometimes|nullable|array',
        ];
    }
}
