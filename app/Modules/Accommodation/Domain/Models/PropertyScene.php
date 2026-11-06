<?php

namespace App\Modules\Accommodation\Domain\Models;

use App\Support\Media;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One stop on a property's reel walk: a film or photo of a single space, with
 * the host's title and caption. Scenes play in `position` order.
 */
class PropertyScene extends Model
{
    use HasFactory;

    /** Spaces a scene can show, keyed as stored. */
    public const SPACES = [
        'arrival' => 'Arrival',
        'entrance' => 'Entrance',
        'living' => 'Living room',
        'kitchen' => 'Kitchen',
        'dining' => 'Dining',
        'master' => 'Master suite',
        'bedroom' => 'Bedroom',
        'bathroom' => 'Bathroom',
        'pool' => 'Pool',
        'garden' => 'Garden',
        'terrace' => 'Terrace',
        'courtyard' => 'Courtyard',
        'breakfast' => 'Breakfast',
        'lounge' => 'Lounge',
        'rooftop' => 'Rooftop',
        'spa' => 'Spa',
        'beach' => 'Beach',
        'view' => 'The view',
        'night' => 'After dark',
        'neighbourhood' => 'Neighbourhood',
    ];

    /** What an experience's scenes show: moments rather than rooms. */
    public const MOMENTS = [
        'meeting' => 'Meeting point',
        'journey' => 'The journey there',
        'guide' => 'Your guide',
        'briefing' => 'The briefing',
        'first-sight' => 'First sight',
        'the-moment' => 'The moment',
        'wildlife' => 'Wildlife',
        'on-the-water' => 'On the water',
        'underwater' => 'Underwater',
        'in-the-air' => 'In the air',
        'summit' => 'The summit',
        'hands-on' => 'Hands on',
        'food' => 'Food & drink',
        'people' => 'The people',
        'golden-hour' => 'Golden hour',
        'night' => 'After dark',
        'wrap-up' => 'Wrapping up',
    ];

    /** What a package's journey reel shows: the stops of a trip. */
    public const STOPS = [
        'arrival' => 'Arrival',
        'on-the-road' => 'On the road',
        'the-stay' => 'Where you stay',
        'the-room' => 'Your room',
        'experience' => 'The experience',
        'wildlife' => 'Wildlife',
        'beach' => 'The beach',
        'on-the-water' => 'On the water',
        'food' => 'Food & drink',
        'people' => 'The people',
        'free-time' => 'Time to yourself',
        'golden-hour' => 'Golden hour',
        'night' => 'After dark',
        'farewell' => 'Farewell',
    ];

    protected $fillable = ['position', 'space', 'title', 'caption', 'media_path', 'media_type'];

    /** The scene labels that suit a kind of listing. */
    public static function spacesFor(?string $ownerType): array
    {
        return match ($ownerType) {
            'activity' => self::MOMENTS,
            'package' => self::STOPS,
            default => self::SPACES,
        };
    }

    /** The label for a stored space, moment or stop key. */
    public static function labelFor(?string $space): ?string
    {
        if (blank($space)) {
            return null;
        }

        return self::SPACES[$space] ?? self::MOMENTS[$space] ?? self::STOPS[$space] ?? null;
    }

    protected $casts = ['position' => 'integer'];

    public function sceneable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isVideo(): bool
    {
        return $this->media_type === 'video' || Media::isVideo($this->media_path);
    }

    public function url(): ?string
    {
        return Media::url($this->media_path);
    }

    public function spaceLabel(): ?string
    {
        return self::labelFor($this->space);
    }
}
