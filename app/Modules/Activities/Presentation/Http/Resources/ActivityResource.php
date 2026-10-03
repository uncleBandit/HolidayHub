<?php

namespace App\Modules\Activities\Presentation\Http\Resources;

use App\Modules\Media\Domain\Enums\MediaAssetStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'highlights' => $this->highlights ?? [],
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category?->id,
                'name' => $this->category?->name,
                'slug' => $this->category?->slug,
            ]),
            'tags' => $this->tags ?? [],
            'location' => $this->whenLoaded('destination', fn () => [
                'destination_id' => $this->destination_id,
                'destination' => $this->destination?->name,
                'latitude' => $this->destination?->latitude,
                'longitude' => $this->destination?->longitude,
                'points' => $this->whenLoaded('locations', fn () => $this->locations->map(fn ($location) => [
                    'type' => $location->type,
                    'name' => $location->name,
                    'address' => $location->address,
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude,
                    'instructions' => $location->instructions,
                ])->values()),
            ]),
            'provider' => $this->whenLoaded('provider', fn () => [
                'id' => $this->provider?->id,
                'name' => $this->provider?->company_name,
                'verified' => (bool) $this->provider?->is_verified,
            ]),
            'duration_minutes' => $this->duration_minutes,
            'age' => [
                'minimum' => data_get($this->age_policy, 'minimum_age', $this->min_age),
                'maximum' => data_get($this->age_policy, 'maximum_age', $this->max_age),
                'requires_adult' => (bool) data_get($this->age_policy, 'requires_adult', false),
            ],
            'booking' => [
                'mode' => $this->booking_mode,
                'minimum_notice_minutes' => $this->minimum_notice_minutes,
                'booking_cutoff_minutes' => $this->booking_cutoff_minutes,
                'timezone' => $this->timezone,
            ],
            'price' => [
                'starting_from' => $this->base_price,
                'currency' => $this->currency,
            ],
            'availability' => [
                'next_sessions' => $this->whenLoaded('sessions', fn () => $this->sessions->map(fn ($session) => [
                    'id' => $session->id,
                    'starts_at' => $session->starts_at?->toIso8601String(),
                    'ends_at' => $session->ends_at?->toIso8601String(),
                    'timezone' => $session->timezone,
                    'available_capacity' => $session->availableCapacity(),
                    'option_id' => $session->activity_option_id,
                ])->values()),
            ],
            'options' => $this->whenLoaded('options', fn () => $this->options->map(fn ($option) => [
                'id' => $option->id,
                'name' => $option->name,
                'description' => $option->description,
                'duration_minutes' => $option->duration_minutes,
                'starting_from' => $option->base_price,
                'currency' => $option->currency,
                'booking_mode' => $option->booking_mode,
                'max_participants' => $option->max_participants,
            ])->values()),
            'inclusions' => $this->inclusions ?? [],
            'exclusions' => $this->exclusions ?? [],
            'accessibility' => $this->accessibility ?? [],
            'requirements' => $this->whenLoaded('requirements', fn () => $this->requirements->map(fn ($requirement) => [
                'type' => $requirement->type,
                'title' => $requirement->title,
                'description' => $requirement->description,
                'required' => $requirement->required,
            ])->values()),
            'languages' => $this->whenLoaded('languages', fn () => $this->languages->pluck('language_code')->values()),
            'itinerary' => $this->whenLoaded('itinerary', fn () => $this->itinerary->map(fn ($item) => [
                'sequence' => $item->sequence,
                'title' => $item->title,
                'description' => $item->description,
                'duration_minutes' => $item->duration_minutes,
            ])->values()),
            'media' => [
                'cover' => $this->thumbnail ?: ($this->getImages()[0] ?? null),
                'gallery' => $this->getImages(),
                'posts' => $this->whenLoaded('mediaPosts', fn () => $this->mediaPosts
                    ->where('provider_id', $this->provider_id)
                    ->map(fn ($post) => [
                        'id' => $post->id,
                        'type' => $post->type?->value,
                        'title' => $post->title,
                        'caption' => $post->caption,
                        'assets' => $post->assets
                            ->where('status', MediaAssetStatus::Ready)
                            ->map(fn ($asset) => [
                                'type' => $asset->type?->value,
                                'url' => $asset->url,
                            ])->values(),
                    ])->values()),
            ],
            'rating' => [
                'average' => round((float) $this->rating, 1),
                'count' => $this->reviews_count,
            ],
            'status' => $this->status?->value,
            'is_featured' => (bool) $this->is_featured,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'links' => [
                'self' => url('/api/v1/activities/'.$this->id),
            ],
        ];
    }
}
