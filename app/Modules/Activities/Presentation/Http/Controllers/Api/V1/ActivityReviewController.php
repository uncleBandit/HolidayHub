<?php

namespace App\Modules\Activities\Presentation\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Activities\Presentation\Http\Requests\StoreActivityReviewRequest;
use App\Modules\Activities\Presentation\Http\Requests\UpdateActivityReviewRequest;
use App\Modules\Reviews\Domain\Models\Review;

class ActivityReviewController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreActivityReviewRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Review $activityReview)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Review $activityReview)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateActivityReviewRequest $request, Review $activityReview)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Review $activityReview)
    {
        //
    }
}
