<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Amenity extends Model
{
    /** @use HasFactory<\Database\Factories\AmenityFactory> */
    use HasFactory;

    protected $fillable = ['name', 'icon'];

    public function hotels()
    {
        return $this->belongsToMany(Hotel::class, 'amenity_hotel');
    }
}
