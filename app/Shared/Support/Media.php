<?php

namespace App\Shared\Support;

use Illuminate\Support\Str;

/**
 * Listing galleries hold photos and videos side by side as plain paths; the
 * file extension says which is which.
 */
final class Media
{
    public const VIDEO_EXTENSIONS = ['mp4', 'webm', 'mov', 'm4v'];

    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'avif', 'gif'];

    /** Upload rule for a gallery file: a photo or a video, up to 200 MB. */
    public const UPLOAD_RULE = 'file|mimes:jpg,jpeg,png,webp,avif,mp4,webm,mov,m4v|max:204800';

    public static function isVideo(?string $path): bool
    {
        if (blank($path)) {
            return false;
        }

        $extension = Str::lower(pathinfo((string) parse_url($path, PHP_URL_PATH), PATHINFO_EXTENSION));

        return in_array($extension, self::VIDEO_EXTENSIONS, true);
    }

    /** A stored path or absolute URL, as a URL the browser can load. */
    public static function url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return Str::startsWith($path, ['http://', 'https://', '/']) ? $path : asset('storage/'.ltrim($path, '/'));
    }
}
