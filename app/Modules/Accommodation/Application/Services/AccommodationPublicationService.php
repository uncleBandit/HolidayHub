<?php

namespace App\Modules\Accommodation\Application\Services;

use App\Modules\Accommodation\Domain\Enums\AccommodationStatus;
use App\Modules\Accommodation\Domain\Enums\VerificationStatus;
use App\Modules\Accommodation\Domain\Models\Accommodation;
use App\Modules\Accommodation\Domain\Models\Hotel;
use App\Modules\Accommodation\Domain\Models\Villa;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccommodationPublicationService
{
    /**
     * @return array<int, string>
     */
    public function missingRequirements(Accommodation $accommodation): array
    {
        $property = $accommodation->bookable;
        $missing = [];

        if (! $accommodation->provider_id) {
            $missing[] = 'An owning service provider is required.';
        }

        if (! $accommodation->destination_id) {
            $missing[] = 'Select a destination for this listing.';
        }

        foreach (['name', 'slug', 'description', 'city', 'country'] as $field) {
            if (blank($accommodation->{$field})) {
                $missing[] = ucfirst(str_replace('_', ' ', $field)).' is required.';
            }
        }

        if (! $property) {
            $missing[] = 'The accommodation details could not be found.';

            return $missing;
        }

        if (! $property->amenities()->exists()) {
            $missing[] = 'Select at least one amenity.';
        }

        $hasImages = method_exists($property, 'images')
            ? $property->images()->exists()
            : false;
        $hasGallery = filled($property->cover_image ?? null)
            || ! empty($property->gallery ?? []);
        if (! $hasImages && ! $hasGallery) {
            $missing[] = 'Add at least one listing image.';
        }

        if (empty($accommodation->policies)) {
            $missing[] = 'Add the accommodation policies.';
        }

        if ($property instanceof Hotel) {
            if (! $property->roomTypes()->where('price_per_night', '>', 0)->exists()) {
                $missing[] = 'Add at least one room type with a nightly price.';
            }
        } elseif ($property instanceof Villa) {
            if ((float) $accommodation->avg_price_per_night <= 0) {
                $missing[] = 'Set a nightly price greater than zero.';
            }
            if ((int) $property->max_guests < 1) {
                $missing[] = 'Set the maximum guest capacity.';
            }
        } elseif ((float) ($property->price_per_night ?? 0) <= 0) {
            $missing[] = 'Set a nightly price greater than zero.';
        }

        return $missing;
    }

    public function submitForReview(Accommodation $accommodation): Accommodation
    {
        return DB::transaction(function () use ($accommodation): Accommodation {
            $locked = Accommodation::query()->lockForUpdate()->findOrFail($accommodation->id);
            $missing = $this->missingRequirements($locked);

            if ($missing !== []) {
                throw ValidationException::withMessages(['publication' => $missing]);
            }

            if (! in_array($locked->status, [AccommodationStatus::Draft, AccommodationStatus::Rejected], true)) {
                throw ValidationException::withMessages([
                    'publication' => 'Only draft or rejected listings can be submitted for review.',
                ]);
            }

            $locked->update([
                'status' => AccommodationStatus::PendingReview,
                'verification_status' => VerificationStatus::Pending,
                'submitted_at' => now(),
                'reviewed_by' => null,
                'suspension_reason' => null,
            ]);
            $locked->verificationHistory()->create([
                'status' => VerificationStatus::Pending->value,
                'reason' => 'Listing submitted for review.',
            ]);

            return $locked->refresh();
        });
    }

    public function approve(Accommodation $accommodation, int $reviewerId): Accommodation
    {
        return DB::transaction(function () use ($accommodation, $reviewerId): Accommodation {
            $locked = Accommodation::query()->lockForUpdate()->findOrFail($accommodation->id);
            $this->ensurePendingReview($locked);

            $locked->update([
                'status' => AccommodationStatus::Published,
                'verification_status' => VerificationStatus::Approved,
                'verified_at' => now(),
                'published_at' => now(),
                'reviewed_by' => $reviewerId,
                'suspended_at' => null,
                'suspension_reason' => null,
            ]);
            $locked->verificationHistory()->create([
                'reviewer_id' => $reviewerId,
                'status' => VerificationStatus::Approved->value,
                'reason' => 'Listing approved and published.',
            ]);

            $property = $locked->bookable;
            if ($property) {
                $property->forceFill(['is_active' => true, 'is_verified' => true])->save();
            }

            return $locked->refresh();
        });
    }

    public function reject(Accommodation $accommodation, int $reviewerId, string $reason): Accommodation
    {
        return DB::transaction(function () use ($accommodation, $reviewerId, $reason): Accommodation {
            $locked = Accommodation::query()->lockForUpdate()->findOrFail($accommodation->id);
            $this->ensurePendingReview($locked);

            $locked->update([
                'status' => AccommodationStatus::Rejected,
                'verification_status' => VerificationStatus::Rejected,
                'reviewed_by' => $reviewerId,
                'suspension_reason' => $reason,
            ]);
            $locked->verificationHistory()->create([
                'reviewer_id' => $reviewerId,
                'status' => VerificationStatus::Rejected->value,
                'reason' => $reason,
            ]);

            $property = $locked->bookable;
            if ($property) {
                $property->forceFill(['is_active' => false, 'is_verified' => false])->save();
            }

            return $locked->refresh();
        });
    }

    public function suspend(Accommodation $accommodation, int $reviewerId, string $reason): Accommodation
    {
        return DB::transaction(function () use ($accommodation, $reviewerId, $reason): Accommodation {
            $locked = Accommodation::query()->lockForUpdate()->findOrFail($accommodation->id);

            if (! $locked->isPublished()) {
                throw ValidationException::withMessages([
                    'publication' => 'Only published listings can be suspended.',
                ]);
            }

            $locked->update([
                'status' => AccommodationStatus::Suspended,
                'suspended_at' => now(),
                'suspension_reason' => $reason,
                'reviewed_by' => $reviewerId,
            ]);
            $locked->verificationHistory()->create([
                'reviewer_id' => $reviewerId,
                'status' => AccommodationStatus::Suspended->value,
                'reason' => $reason,
            ]);

            $property = $locked->bookable;
            if ($property) {
                $property->forceFill(['is_active' => false])->save();
            }

            return $locked->refresh();
        });
    }

    private function ensurePendingReview(Accommodation $accommodation): void
    {
        if ($accommodation->status !== AccommodationStatus::PendingReview
            || $accommodation->verification_status !== VerificationStatus::Pending) {
            throw ValidationException::withMessages([
                'publication' => 'This listing is not awaiting review.',
            ]);
        }
    }
}
