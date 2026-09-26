<?php

namespace App\Modules\Booking\Presentation\Http\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class BookingFilters
{
    public function apply(Builder $query, Request $request): Builder
    {
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($from = $request->input('from_date')) {
            $query->where('start_date', '>=', $from);
        }

        if ($to = $request->input('to_date')) {
            $query->where('end_date', '<=', $to);
        }

        if ($destination = $request->input('destination')) {
            $query->whereHas('bookable', fn ($q) => $q->where('destination_id', $destination));
        }

        if ($minPrice = $request->input('min_price')) {
            $query->where('price', '>=', $minPrice);
        }

        if ($maxPrice = $request->input('max_price')) {
            $query->where('price', '<=', $maxPrice);
        }

        return $query;
    }
}
