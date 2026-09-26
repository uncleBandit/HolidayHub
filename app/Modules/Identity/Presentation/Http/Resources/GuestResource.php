<?php

namespace App\Modules\Identity\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            // Basic details
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,

            // Linked user
            'user' => [
                'id' => $this->user->id ?? null,
                'name' => $this->user->name ?? null,
                'email' => $this->user->email ?? null,
            ],

            // Demographics
            'date_of_birth' => $this->date_of_birth,
            'gender' => $this->gender,
            'nationality' => $this->nationality,

            // Identity & verification
            'passport_number' => $this->passport_number,
            'id_number' => $this->id_number,
            'verified' => (bool) $this->verified,

            // Preferences & loyalty
            'preferred_language' => $this->preferred_language,
            'preferred_currency' => $this->preferred_currency,
            'loyalty_tier' => $this->loyalty_tier,
            'loyalty_points' => $this->loyalty_points,

            // Emergency contact
            'emergency_contact' => [
                'name' => $this->emergency_contact_name,
                'phone' => $this->emergency_contact_phone,
                'relation' => $this->emergency_contact_relation,
            ],

            // Travel info
            'frequent_flyer_number' => $this->frequent_flyer_number,
            'special_requests' => $this->special_requests,

            // Address
            'address' => [
                'line1' => $this->address_line1,
                'line2' => $this->address_line2,
                'city' => $this->city,
                'state' => $this->state,
                'postal_code' => $this->postal_code,
                'country' => $this->country,
            ],

            // System fields
            'deleted_at' => $this->deleted_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
