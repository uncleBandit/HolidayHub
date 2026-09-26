<?php

namespace App\Modules\Catalog\Domain\Models;

use App\Modules\Accommodation\Domain\Models\Villa;
use App\Modules\Packages\Domain\Models\Package;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Amenity extends Model
{
    use HasFactory;

    /**
     * Mass assignable fields
     */
    protected $fillable = [
        'name',
        'description',
        'icon',
        'slug',
        'type',
        'active',
    ];

    /**
     * Casts
     */
    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Scope to only active amenities
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Polymorphic relation: Hotels
     */
    public function hotels(): MorphToMany
    {
        return $this->morphedByMany(
            \App\Modules\Accommodation\Domain\Models\Hotel::class,
            'amenable',
            'amenables',
            'amenity_id',
            'amenable_id'
        );
    }

    /**
     * Polymorphic relation: Rooms
     */
    public function rooms(): MorphToMany
    {
        return $this->morphedByMany(
            \App\Modules\Accommodation\Domain\Models\Room::class,
            'amenable',
            'amenables',
            'amenity_id',
            'amenable_id'
        );
    }

    /**
     * Polymorphic relation: Packages
     */
    public function packages(): MorphToMany
    {
        return $this->morphedByMany(
            Package::class,
            'amenable',
            'amenables',
            'amenity_id',
            'amenable_id'
        );
    }

    /**
     * Polymorphic relation: Villas
     */
    public function villas(): MorphToMany
    {
        return $this->morphedByMany(
            Villa::class,
            'amenable',
            'amenables',
            'amenity_id',
            'amenable_id'
        );
    }

    /**
     * Attach this amenity to any model dynamically
     */
    public function attachTo(Model $model): void
    {
        $model->amenities()->syncWithoutDetaching([$this->id]);
    }

    /**
     * Detach this amenity from any model
     */
    public function detachFrom(Model $model): void
    {
        $model->amenities()->detach($this->id);
    }

    /**
     * Accessor for full icon URL
     */
    public function getIconUrlAttribute(): ?string
    {
        return $this->icon ? asset("storage/{$this->icon}") : null;
    }

    /**
     * Group amenities by type for a collection
     */
    public static function groupByType($amenities)
    {
        return $amenities->groupBy('type');
    }
}
