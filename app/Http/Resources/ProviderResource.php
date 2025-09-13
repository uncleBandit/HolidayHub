<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProviderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'company_name'    => $this->company_name,
            'contact_person'  => $this->contact_person,
            'email'           => $this->email,
            'phone'           => $this->phone,

            // Business details
            'business_license'=> $this->business_license,
            'tax_number'      => $this->tax_number,
            'provider_type'   => $this->provider_type,

            // Location
            'country'         => $this->country,
            'city'            => $this->city,
            'address'         => $this->address,
            'latitude'        => $this->latitude,
            'longitude'       => $this->longitude,

            // Profile & verification
            'logo'            => $this->logo ? asset('storage/' . $this->logo) : null,
            'is_verified'     => (bool) $this->is_verified,
            'verified_at'     => $this->verified_at?->toDateTimeString(),

            // Status
            'active'          => (bool) $this->active,

            // Timestamps
            'created_at'      => $this->created_at?->toDateTimeString(),
            'updated_at'      => $this->updated_at?->toDateTimeString(),
        ];
    }
}
