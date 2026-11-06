<?php

namespace App\Modules\Media\Database\Seeders;

use App\Modules\Accommodation\Database\Seeders\ShowcaseStaySeeder;
use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Identity\Database\Seeders\ShowcaseAccountSeeder;
use App\Modules\Media\Application\Services\MediaModerationService;
use App\Modules\Media\Domain\Enums\MediaAssetStatus;
use App\Modules\Media\Domain\Enums\MediaAssetType;
use App\Modules\Media\Domain\Enums\MediaPostStatus;
use App\Modules\Media\Domain\Enums\MediaPostType;
use App\Modules\Media\Domain\Models\MediaPost;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Publicly published showcase reels, each promoting a showcase stay or
 * experience.
 *
 * MediaAsset::url signs a temporary URL on the asset's disk, so a hotlinked
 * remote URL cannot be stored as a path. Instead the house Mixkit clips and
 * posters listed in config/stays.php are downloaded once onto the configured
 * media disk (config('media.disk'), the same disk provider uploads use), under
 * the same directory layout MediaLibrary writes. The default `local` disk has
 * `serve => true`, so the signed /storage/... URL streams the file with no app
 * code changes.
 *
 * Posts go through MediaModerationService::approve() like an uploaded reel,
 * then published_at is back-dated so the feed has a spread over two weeks.
 *
 * Idempotent: posts match on provider + title; files are only
 * downloaded when missing; approval only runs for posts not yet published.
 */
class ShowcaseReelSeeder extends Seeder
{
    /**
     * [clip key from config/stays.php, target kind, target slug, title, caption, published N days/hours ago]
     */
    private const REELS = [
        ['hotel-resort-sea', 'stay', 'coral-tide-beach-hotel-diani', 'Mornings at Coral Tide', 'Pool, palms and the lagoon at low tide. Diani does mornings properly.', [1, 3]],
        ['villa-garden-pool', 'stay', 'villa-kilindini-diani', 'A garden pool all to yourselves', 'Four bedrooms, one pool, nobody else. Villa Kilindini, Diani.', [2, 6]],
        ['coast-sunset', 'activity', 'sunset-dhow-sail-lamu', 'Golden hour on the Lamu channel', 'The engine goes off, the sail goes up, and the sun does the rest.', [3, 2]],
        ['wild-safari', 'activity', 'amboseli-game-drive-kilimanjaro', 'Off-road at first light', 'Out of the gate before the clouds cover Kilimanjaro.', [4, 9]],
        ['villa-luxury-pool', 'stay', 'watamu-white-house-villa', 'Glass, white walls and a long blue pool', 'The Watamu White House, five minutes from the beach.', [6, 1]],
        ['bnb-breakfast-table', 'stay', 'lamu-old-town-courtyard-bnb', 'Breakfast on the rooftop', 'Coffee, bread and fruit above Lamu old town.', [8, 4]],
        ['hotel-suite-pan', 'stay', 'gion-lantern-hotel-kyoto', 'Slow mornings in Higashiyama', 'Your room, a short walk from the Yasaka pagoda.', [10, 5]],
        ['mountain-clouds', 'activity', 'mount-kenya-moorland-day-hike', 'Above the clouds on Mount Kenya', 'From the forest up into the moorland, with the cloud below you.', [12, 7]],
        ['coast-turquoise', 'stay', 'mida-creek-ocean-lodge-watamu', 'Turquoise water off Watamu', 'White sand, warm sea and the marine park on the doorstep.', [13, 8]],
    ];

    public function run(): void
    {
        $reviewer = ShowcaseAccountSeeder::reviewer();
        $moderation = app(MediaModerationService::class);
        $disk = (string) config('media.disk', 'local');
        $stays = ShowcaseStaySeeder::accommodations();
        $ready = 0;

        foreach (self::REELS as [$clipKey, $kind, $slug, $title, $caption, [$daysAgo, $hoursAgo]]) {
            $clip = config("stays.clips.{$clipKey}");
            // Stay reels target the property (hotel / villa / B&B) rather than
            // the canonical accommodation row: that is what the public detail
            // routes bind, and it keeps feed consumers that eager-load only
            // `targetable` from lazy-loading accommodation->bookable.
            $target = $kind === 'stay'
                ? $stays->get($slug)?->bookable
                : Activity::query()->published()->where('slug', $slug)->first();

            if (! $clip || ! $target) {
                $this->command?->warn("Skipping reel '{$title}': clip or published target '{$slug}' not found.");

                continue;
            }

            $providerId = $target->provider_id;
            $directory = "media/provider-{$providerId}/showcase/{$clipKey}";
            $video = $this->store($disk, "{$directory}/original.mp4", $clip['video']);
            $poster = $this->store($disk, "{$directory}/thumbnail.jpg", $clip['poster']);

            if ($video === null) {
                $this->command?->warn("Skipping reel '{$title}': could not download {$clip['video']}.");

                continue;
            }

            $post = MediaPost::withTrashed()
                ->where('provider_id', $providerId)
                ->where('title', $title)
                ->first() ?? new MediaPost;
            if ($post->trashed()) {
                $post->restore();
            }

            if (! $post->exists) {
                $post->fill([
                    'provider_id' => $providerId,
                    'title' => $title,
                    'type' => MediaPostType::Reel,
                    'caption' => $caption,
                    'status' => MediaPostStatus::PendingReview,
                    'visibility' => 'public',
                ]);
            }

            if (! $post->exists || $post->targetable_type !== $target->getMorphClass() || (int) $post->targetable_id !== (int) $target->getKey()) {
                $post->targetable()->associate($target);
                $post->save();
            }

            $post->assets()->updateOrCreate(
                ['type' => MediaAssetType::Original],
                [
                    'disk' => $disk,
                    'path' => $video['path'],
                    'mime_type' => 'video/mp4',
                    'size_bytes' => $video['size'],
                    'metadata' => ['source' => $clip['video'], 'licence' => 'Mixkit free licence', 'clip' => $clipKey],
                    'status' => MediaAssetStatus::Ready,
                ],
            );

            if ($poster !== null) {
                $post->assets()->updateOrCreate(
                    ['type' => MediaAssetType::Thumbnail],
                    [
                        'disk' => $disk,
                        'path' => $poster['path'],
                        'mime_type' => 'image/jpeg',
                        'size_bytes' => $poster['size'],
                        'metadata' => ['source' => $clip['poster']],
                        'status' => MediaAssetStatus::Ready,
                    ],
                );
            }

            if ($post->status === MediaPostStatus::Draft || $post->status === MediaPostStatus::Rejected) {
                $post->forceFill(['status' => MediaPostStatus::PendingReview])->save();
            }

            if ($post->status === MediaPostStatus::PendingReview) {
                $post = $moderation->approve($post, $reviewer);
                $post->forceFill(['published_at' => now()->subDays($daysAgo)->subHours($hoursAgo)])->save();
            }

            if ($post->status === MediaPostStatus::Published && $post->visibility !== 'public') {
                $post->forceFill(['visibility' => 'public'])->save();
            }

            $ready++;
        }

        $this->command?->info(sprintf('Showcase reels ready: %d published, files on the "%s" disk.', $ready, $disk));
    }

    /**
     * Download a remote file onto the disk unless it is already there.
     *
     * @return array{path: string, size: int}|null
     */
    private function store(string $disk, string $path, string $url): ?array
    {
        $storage = Storage::disk($disk);

        if (! $storage->exists($path) || $storage->size($path) === 0) {
            try {
                $response = Http::timeout(120)->retry(2, 1000)->get($url);
            } catch (Throwable $e) {
                return null;
            }

            if (! $response->successful() || $response->body() === '') {
                return null;
            }

            $storage->put($path, $response->body());
        }

        return ['path' => $path, 'size' => (int) $storage->size($path)];
    }
}
