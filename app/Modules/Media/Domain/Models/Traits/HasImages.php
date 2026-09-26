<?php

namespace App\Modules\Media\Domain\Models\Traits;

use App\Modules\Media\Domain\Models\Image;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasImages
{
    /**
     * Polymorphic relationship: model can have many images.
     */
    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')->ordered();
    }

    /**
     * Get the primary image of the model.
     */
    public function primaryImage(): ?Image
    {
        return $this->images()->primary()->first();
    }

    /**
     * Get the URL of the primary image.
     */
    public function primaryImageUrl(): ?string
    {
        return $this->primaryImage()?->url;
    }

    /**
     * Get all gallery image URLs as array.
     */
    public function galleryUrls(): array
    {
        return $this->images()->get()->map(fn (Image $img) => $img->url)->toArray();
    }

    /**
     * Add a new image to the model.
     *
     * @param  array  $attributes  Attributes for the Image model
     */
    public function addImage(array $attributes): Image
    {
        return $this->images()->create($attributes);
    }

    /**
     * Get a specific variant from all images (if available).
     *
     * @param  string  $variantName  e.g. "thumb", "webp"
     * @return array URLs of the variant
     */
    public function variantUrls(string $variantName): array
    {
        return $this->images()->get()
            ->map(fn (Image $img) => $img->variant($variantName))
            ->filter()
            ->toArray();
    }
}
