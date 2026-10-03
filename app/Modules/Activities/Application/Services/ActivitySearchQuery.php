<?php

namespace App\Modules\Activities\Application\Services;

use App\Modules\Activities\Domain\Enums\ActivitySessionStatus;
use App\Modules\Activities\Domain\Models\Activity;
use Illuminate\Database\Eloquent\Builder;

class ActivitySearchQuery
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function build(array $filters = []): Builder
    {
        return Activity::query()
            ->published()
            ->with(['category', 'destination', 'images'])
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $term = '%'.mb_strtolower(trim($search)).'%';
                $query->where(function (Builder $query) use ($term): void {
                    $query->whereRaw('LOWER(name) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(description) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(short_description) LIKE ?', [$term]);
                });
            })
            ->when($filters['destination_id'] ?? null, fn (Builder $query, int $id) => $query->where('destination_id', $id))
            ->when($filters['category_id'] ?? null, fn (Builder $query, int $id) => $query->where('category_id', $id))
            ->when($filters['category'] ?? null, fn (Builder $query, string $category) => $query->whereHas(
                'category',
                fn (Builder $categories) => $categories->whereRaw('LOWER(name) = ?', [mb_strtolower($category)])
            ))
            ->when($filters['min_price'] ?? null, fn (Builder $query, float $price) => $query->where('base_price', '>=', $price))
            ->when($filters['max_price'] ?? null, fn (Builder $query, float $price) => $query->where('base_price', '<=', $price))
            ->when($filters['min_duration'] ?? null, fn (Builder $query, int $duration) => $query->where('duration_minutes', '>=', $duration))
            ->when($filters['max_duration'] ?? null, fn (Builder $query, int $duration) => $query->where('duration_minutes', '<=', $duration))
            ->when($filters['min_rating'] ?? null, fn (Builder $query, float $rating) => $query->where('rating', '>=', $rating))
            ->when($filters['booking_mode'] ?? null, fn (Builder $query, string $mode) => $query->where('booking_mode', $mode))
            ->when($filters['featured'] ?? false, fn (Builder $query) => $query->where('is_featured', true))
            ->when($filters['language'] ?? null, fn (Builder $query, string $language) => $query->whereHas(
                'languages',
                fn (Builder $languages) => $languages->where('language_code', $language)
            ))
            ->when($filters['participants'] ?? null, function (Builder $query, int $participants): void {
                $query->whereHas('sessions', fn (Builder $sessions) => $sessions
                    ->where('status', ActivitySessionStatus::Scheduled)
                    ->where('starts_at', '>=', now())
                    ->whereRaw('(capacity - booked_capacity) >= ?', [$participants])
                    ->where(fn (Builder $query) => $query->whereNull('booking_cutoff_at')->orWhere('booking_cutoff_at', '>', now())));
            })
            ->when($filters['date'] ?? null, function (Builder $query, string $date): void {
                $query->whereHas('sessions', fn (Builder $sessions) => $sessions
                    ->where('status', ActivitySessionStatus::Scheduled)
                    ->where('starts_at', '>=', now())
                    ->whereDate('starts_at', $date)
                    ->whereColumn('booked_capacity', '<', 'capacity')
                    ->where(fn (Builder $query) => $query->whereNull('booking_cutoff_at')->orWhere('booking_cutoff_at', '>', now())));
            })
            ->when($filters['sort'] ?? null, function (Builder $query, string $sort): void {
                match ($sort) {
                    'price_low_high' => $query->orderBy('base_price')->orderBy('id'),
                    'price_high_low' => $query->orderByDesc('base_price')->orderBy('id'),
                    'rating' => $query->orderByDesc('rating')->orderByDesc('reviews_count'),
                    'latest' => $query->latest('created_at'),
                    'popularity' => $query->orderByDesc('bookings_count'),
                    default => $query->orderBy('name'),
                };
            }, fn (Builder $query) => $query->orderBy('name'));
    }
}
