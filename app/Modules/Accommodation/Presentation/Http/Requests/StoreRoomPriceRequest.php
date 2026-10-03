<?php

namespace App\Modules\Accommodation\Presentation\Http\Requests;

use App\Modules\Accommodation\Domain\Models\Room;
use Illuminate\Foundation\Http\FormRequest;

class StoreRoomPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $room = Room::query()->with('hotel.accommodation')->find($this->input('room_id'));

        return $room !== null && ($this->user()?->can('update', $room) ?? false);
    }

    public function rules(): array
    {
        return [
            'room_id' => 'required|integer|exists:rooms,id',
            'base_price' => 'required|numeric|gt:0',
            'discount_price' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'meta' => 'nullable|array',
        ];
    }
}
