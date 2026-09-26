<?php

namespace App\Modules\Booking\Presentation\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'check_in_date' => $this->check_in_date,
            'check_out_date' => $this->check_out_date,
            'guests' => [
                'adults' => $this->guests_adults,
                'children' => $this->guests_children,
            ],
            'pricing' => [
                'price_per_night' => $this->price_per_night,
                'total_amount' => $this->total_amount,
                'currency' => $this->currency,
            ],
            'payment' => [
                'status' => $this->payment_status,
                'method' => $this->payment_method,
            ],
            'guest' => [
                'id' => $this->guest->id ?? null,
                'name' => $this->guest->name ?? null,
                'email' => $this->guest->email ?? null,
            ],
            'bookable' => [
                'type' => class_basename($this->bookable_type),
                'id' => $this->bookable_id,
                'name' => $this->bookable->name ?? null,
            ],
            'special_requests' => $this->special_requests,
            'confirmation_code' => $this->confirmation_code,
            'cancelled_at' => $this->cancelled_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
