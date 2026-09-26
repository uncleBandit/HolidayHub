<?php

use App\Modules\Accommodation\Presentation\Http\Controllers\Api\V1\HotelController;
use App\Modules\Accommodation\Presentation\Http\Controllers\Api\V1\RoomController;
use App\Modules\Activities\Presentation\Http\Controllers\Api\V1\ActivityController;
use App\Modules\Agents\Presentation\Http\Controllers\Api\V1\AgentController;
use App\Modules\Auth\Presentation\Http\Controllers\Api\V1\AuthController;
use App\Modules\Booking\Presentation\Http\Controllers\Api\V1\BookingController;
use App\Modules\Catalog\Presentation\Http\Controllers\Api\V1\OfferController;
use App\Modules\Destinations\Presentation\Http\Controllers\Api\V1\DashboardController;
use App\Modules\Destinations\Presentation\Http\Controllers\Api\V1\DestinationController;
use App\Modules\Identity\Presentation\Http\Controllers\Api\V1\ProfileController;
use App\Modules\Packages\Presentation\Http\Controllers\Api\V1\PackageController;
use App\Modules\Providers\Presentation\Http\Controllers\Api\V1\ProviderController;
use App\Modules\Reviews\Presentation\Http\Controllers\Api\V1\ReviewController;
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
        Route::apiResource('/profile', ProfileController::class)->names([
            'index' => 'api.profile.index',
            'store' => 'api.profile.store',
            'show' => 'api.profile.show',
            'update' => 'api.profile.update',
            'destroy' => 'api.profile.destroy',
        ]);

        // Dashboard
        Route::apiResource('/dashboard', DashboardController::class)->only(['index'])->names(['index' => 'api.dashboard.index']);

        /**
         * Provider-only routes
         */
        Route::middleware('role:provider')->group(function () {
            Route::apiResource('/provider/dashboard', ProviderController::class)->names([
                'index' => 'api.provider.dashboard.index',
                'store' => 'api.provider.dashboard.store',
                'show' => 'api.provider.dashboard.show',
                'update' => 'api.provider.dashboard.update',
                'destroy' => 'api.provider.dashboard.destroy',
            ]);
        });

        /**
         * Agent-only routes
         */
        Route::middleware('role:agent')->group(function () {
            Route::apiResource('/agent/dashboard', AgentController::class)->names([
                'index' => 'api.agent.dashboard.index',
                'store' => 'api.agent.dashboard.store',
                'show' => 'api.agent.dashboard.show',
                'update' => 'api.agent.dashboard.update',
                'destroy' => 'api.agent.dashboard.destroy',
            ]);
            Route::apiResource('/agent/bookings', BookingController::class);
            // Route::apiResource('/agent/analytics', AnalyticsController::class);
        });

        /**
         * Other API Resources (accessible to authenticated users)
         */
        Route::apiResources([
            'hotels' => HotelController::class,
            'offers' => OfferController::class,
            'reviews' => ReviewController::class,
            'rooms' => RoomController::class,
            'activities' => ActivityController::class,
            'destinations' => DestinationController::class,
            'packages' => PackageController::class,
        ]);

        Route::apiResource('/agent/bookings', BookingController::class)->names([
            'index' => 'agent.bookings.index',
            'store' => 'agent.bookings.store',
            'show' => 'agent.bookings.show',
            'update' => 'agent.bookings.update',
            'destroy' => 'agent.bookings.destroy',
        ]);

        Route::apiResource('/bookings', BookingController::class)->names([
            'index' => 'bookings.index',
            'store' => 'bookings.store',
            'show' => 'bookings.show',
            'update' => 'bookings.update',
            'destroy' => 'bookings.destroy',
        ]);

    });
});
