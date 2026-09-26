<?php

namespace App\Modules\Packages\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PackageFeature extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Mass assignable attributes
     */
    protected $fillable = [
        'name',
        'description',
        'icon',
        'category',
        'is_highlighted',
        'metadata',
    ];

    /**
     * Attribute casting
     */
    protected $casts = [
        'is_highlighted' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Polymorphic relation:
     * A feature can belong to many bookable entities (packages, tours, hotels, villas, etc.)
     */
    public function featureable()
    {
        return $this->morphTo();
    }

    /**
     * Scope: highlight only key selling features
     */
    public function scopeHighlighted($query)
    {
        return $query->where('is_highlighted', true);
    }

    /**
     * Scope: filter by category
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
