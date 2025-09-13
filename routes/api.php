<?php

use App\Http\Controllers\Api\v1\ActivityController;
use App\Http\Controllers\Api\v1\AdminController;
use App\Http\Controllers\Api\v1\AgentController;
use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\BookingController;
use App\Http\Controllers\Api\v1\DashboardController;
use App\Http\Controllers\Api\v1\DestinationController;
use App\Http\Controllers\Api\v1\HotelController;
use App\Http\Controllers\Api\v1\OfferController;
use App\Http\Controllers\Api\v1\PackageController;
use App\Http\Controllers\Api\v1\ProfileController;
use App\Http\Controllers\Api\v1\ProviderController;
use App\Http\Controllers\Api\v1\ReviewController;
use App\Http\Controllers\Api\v1\RoomController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    /**
     * Public routes (no auth required)
     */
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    /**
     * Protected routes
     */
    Route::middleware('auth:sanctum')->group(function () {

        // Get currently authenticated user
        Route::get('/user', function (Request $request) {
            return $request->user();
        });

        // Logout
        Route::post('/logout', [AuthController::class, 'logout']);

        // Profile
        Route::apiResource('/profile', ProfileController::class);

        // Dashboard
        Route::apiResource('/dashboard', DashboardController::class)->only(['index']);

        /**
         * Admin-only routes
         */
        Route::middleware('role:admin')->group(function () {
            Route::apiResource('/admin/dashboard', AdminController::class);
        });

        /**
         * Provider-only routes
         */
        Route::middleware('role:provider')->group(function () {
            Route::apiResource('/provider/dashboard', ProviderController::class);
        });

        /**
         * Agent-only routes
         */
        Route::middleware('role:agent')->group(function () {
            Route::apiResource('/agent/dashboard', AgentController::class);
            Route::apiResource('/agent/bookings', BookingController::class);
            Route::apiResource('/agent/analytics', AnalyticsController::class);
        });

        /**
         * Other API Resources (accessible to authenticated users)
         */
        Route::apiResources([
            'hotels' => HotelController::class,
            'bookings' => BookingController::class,
            'offers' => OfferController::class,
            'reviews' => ReviewController::class,
            'rooms' => RoomController::class,
            'activities' => ActivityController::class,
            'destinations' => DestinationController::class,
            'packages' => PackageController::class,
        ]);
    });
});
