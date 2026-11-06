<?php

namespace App\Modules\Packages\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One piece of a package's journey on a given day: a stay (room type, villa
 * or B&B for some nights), an experience, or an extra such as a transfer or a
 * free day. Listings are priced live from the listing itself; extras carry
 * their own price.
 */
class PackageComponent extends Model
{
    public const KINDS = [
        'stay' => 'Stay',
        'experience' => 'Experience',
        'transfer' => 'Transfer',
        'free' => 'Free time',
        'note' => 'Note',
    ];

    protected $fillable = [
        'day', 'sequence', 'kind', 'componentable_type', 'componentable_id', 'activity_option_id',
        'nights', 'quantity', 'title', 'description', 'price', 'currency',
    ];

    protected $casts = [
        'day' => 'integer',
        'sequence' => 'integer',
        'nights' => 'integer',
        'quantity' => 'integer',
        'price' => 'float',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /** The stay or experience this component books; null for extras. */
    public function componentable(): MorphTo
    {
        return $this->morphTo();
    }

    /** The experience option or ticket chosen, when the agent picked one. */
    public function activityOption(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Activities\Domain\Models\ActivityOption::class);
    }

    /** Listing kinds a stay component may book (morph aliases). */
    public const STAY_TYPES = ['room_type', 'villa', 'bed_and_breakfast'];

    public function isListing(): bool
    {
        return in_array($this->kind, ['stay', 'experience'], true) && $this->componentable_id !== null;
    }
}
