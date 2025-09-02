<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'hotel'      => new HotelResource($this->whenLoaded('hotel')),
            'room'       => $this->room->name ?? null,
            'check_in'   => $this->check_in,
            'check_out'  => $this->check_out,
            'guests'     => $this->guests,
            'total_price'=> $this->total_price,
            'status'     => $this->status,
        ];
    }
}
