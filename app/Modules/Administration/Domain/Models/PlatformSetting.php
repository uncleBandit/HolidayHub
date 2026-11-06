<?php

namespace App\Modules\Administration\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One platform setting. Read and write them through PlatformSettings, which
 * knows each setting's default and caches the lot.
 */
class PlatformSetting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value', 'updated_by'];

    protected $casts = [
        'value' => 'array',
    ];
}
