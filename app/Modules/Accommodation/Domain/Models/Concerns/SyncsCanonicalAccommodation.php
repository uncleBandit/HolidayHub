<?php

namespace App\Modules\Accommodation\Domain\Models\Concerns;

use App\Modules\Accommodation\Domain\Enums\AccommodationStatus;
use App\Modules\Accommodation\Domain\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

trait SyncsCanonicalAccommodation
{
    protected static function bootSyncsCanonicalAccommodation(): void
    {
        static::saved(function (Model $property): void {
            $property->syncCanonicalAccommodation();
        });

        static::deleted(function (Model $property): void {
            $property->accommodation()->update([
                'status' => AccommodationStatus::Archived,
                'suspended_at' => now(),
            ]);
        });

        // Only models that soft delete can be restored.
        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::restored(function (Model $property): void {
                $property->accommodation()->update([
                    'status' => AccommodationStatus::Draft,
                    'suspended_at' => null,
                    'suspension_reason' => null,
                ]);
            });
        }
    }

    protected function syncCanonicalAccommodation(): void
    {
        $existing = $this->accommodation()->first();
        $attributes = [
            'provider_id' => $existing?->provider_id ?? $this->getAttribute('provider_id'),
            'name' => $this->getAttribute('name'),
            'slug' => $this->getAttribute('slug'),
            'description' => $this->getAttribute('description'),
            'address' => $this->getAttribute('address'),
            'city' => $this->getAttribute('city'),
            'country' => $this->getAttribute('country'),
            'latitude' => $this->getAttribute('latitude'),
            'longitude' => $this->getAttribute('longitude'),
            'policies' => $this->getAttribute('policies'),
            'is_featured' => (bool) $this->getAttribute('is_featured'),
            'avg_price_per_night' => $this->canonicalPrice(),
            'avg_rating' => $this->getAttribute('avg_rating') ?? 0,
            'reviews_count' => $this->getAttribute('reviews_count') ?? 0,
        ];

        if (! $existing) {
            $attributes['status'] = AccommodationStatus::Draft;
            $attributes['verification_status'] = VerificationStatus::Unverified;
        } elseif ($existing->isPublished() && $this->wasChanged([
            'name',
            'slug',
            'description',
            'address',
            'city',
            'country',
            'latitude',
            'longitude',
            'policies',
            'avg_price_per_night',
            'price_per_night',
            'cover_image',
            'gallery',
        ])) {
            $attributes['status'] = AccommodationStatus::Draft;
            $attributes['verification_status'] = VerificationStatus::Unverified;
            $attributes['published_at'] = null;
            $attributes['verified_at'] = null;
        }

        $canonical = $this->accommodation()->updateOrCreate([], $attributes);

        if ($existing?->isPublished() && $canonical->status === AccommodationStatus::Draft) {
            $canonical->verificationHistory()->create([
                'status' => VerificationStatus::Unverified->value,
                'reason' => 'Listing details changed; review is required before republishing.',
            ]);
        }
    }

    protected function canonicalPrice(): mixed
    {
        return $this->getAttribute('avg_price_per_night')
            ?? $this->getAttribute('price_per_night');
    }
}
