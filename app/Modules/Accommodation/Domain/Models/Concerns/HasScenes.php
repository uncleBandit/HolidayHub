<?php

namespace App\Modules\Accommodation\Domain\Models\Concerns;

use App\Modules\Accommodation\Domain\Models\PropertyScene;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * A property with a reel walk: scenes the host orders from arrival onwards.
 */
trait HasScenes
{
    public function scenes(): MorphMany
    {
        return $this->morphMany(PropertyScene::class, 'sceneable')->orderBy('position')->orderBy('id');
    }
}
