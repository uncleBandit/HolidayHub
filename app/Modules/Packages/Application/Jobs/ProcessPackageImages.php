<?php

namespace App\Modules\Packages\Application\Jobs;

use App\Modules\Packages\Domain\Models\Package;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\ImageManagerStatic as Image;

class ProcessPackageImages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Package $package;

    /**
     * Create a new job instance.
     */
    public function __construct(Package $package)
    {
        $this->package = $package;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Example: create thumbnails and move to final location or push to S3.
        // Requires intervention/image & configured disks (public + s3).
        $disk = Storage::disk('public');

        // process cover
        if ($this->package->cover_image && $disk->exists($this->package->cover_image)) {
            $raw = $disk->get($this->package->cover_image);
            $img = ImageManager::make($raw)->orientate()->resize(1600, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            $thumbPath = str_replace('cover', 'cover_thumb', $this->package->cover_image);
            $disk->put($thumbPath, (string) $img->encode('webp', 85));
            // Optionally: update package with processed paths or move to s3
        }

        // process gallery
        if (is_array($this->package->gallery)) {
            foreach ($this->package->gallery as $idx => $path) {
                if (! $disk->exists($path)) {
                    continue;
                }
                $raw = $disk->get($path);
                $img = ImageManager::make($raw)->orientate()->resize(1200, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });

                $thumbPath = str_replace('gallery', 'gallery_thumb', $path);
                $disk->put($thumbPath, (string) $img->encode('webp', 80));
            }
        }

        // You may push processed images to S3 / CDN and update package model accordingly
    }
}
