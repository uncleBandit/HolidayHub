<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\ActivityReview;
use App\Http\Requests\StoreActivityReviewRequest;
use App\Http\Requests\UpdateActivityReviewRequest;
use App\Http\Controllers\Controller;
use App\Models\Review;

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
