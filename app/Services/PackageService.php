<?php

namespace App\Services;

use App\Models\Package;
use Illuminate\Support\Str;

class PackageService
{
    public function create(array $data): Package
    {
        // Calculate discount
        $discountPrice = !empty($data['discount'])
            ? $data['base_price'] - ($data['base_price'] * $data['discount'] / 100)
            : null;

        // Create package
        return Package::create([
            'title' => $data['title'],
            'slug' => Str::slug($data['title']),
            'destination' => $data['destination'],
            'country' => $data['country'] ?? null,
            'short_description' => $data['short_description'] ?? null,
            'description' => $data['description'] ?? null,
            'price' => $data['base_price'],
            'discount_price' => $discountPrice,
            'currency' => $data['currency'] ?? 'USD',
            'duration_days' => $data['duration_days'],
            'duration_nights' => $data['duration_nights'] ?? null,
            'image_url' => $data['cover_image'] ?? null,
            'tags' => json_encode($data['tags'] ?? []),
            'start_date' => $data['available_from'] ?? null,
            'end_date' => $data['available_to'] ?? null,
            'status' => $data['status'] ?? 'draft',
        ]);
    }
}
