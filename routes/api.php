<?php

use App\Modules\Accommodation\Presentation\Http\Controllers\Api\V1\AccommodationWorkflowController;
use App\Modules\Accommodation\Presentation\Http\Controllers\Api\V1\HotelController;
use App\Modules\Accommodation\Presentation\Http\Controllers\Api\V1\RoomController;
use App\Modules\Activities\Presentation\Http\Controllers\Api\V1\ActivityController;
use App\Modules\Activities\Presentation\Http\Controllers\Api\V1\ActivityInventoryController;
use App\Modules\Activities\Presentation\Http\Controllers\Api\V1\ActivityReviewController;
use App\Modules\Activities\Presentation\Http\Controllers\Api\V1\ActivitySessionBookingController;
use App\Modules\Activities\Presentation\Http\Controllers\Api\V1\ActivityWorkflowController;
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
    Route::get('/activities', [ActivityController::class, 'index'])->name('activities.index');
    Route::get('/activities/{activity}', [ActivityController::class, 'show'])->name('activities.show');
    Route::get('/activities/{activity}/sessions', [ActivityInventoryController::class, 'sessions'])
        ->name('activities.sessions.index');
    Route::get('/activities/{activity}/reviews', [ActivityReviewController::class, 'index'])
        ->name('activities.reviews.index');

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

        Route::post('/accommodations/{accommodation}/submit', [AccommodationWorkflowController::class, 'submit'])
            ->name('api.accommodations.submit');
        Route::post('/activities/{activity}/submit', [ActivityWorkflowController::class, 'submit'])
            ->name('api.activities.submit');
        Route::post('/activities/{activity}/sessions/{session}/book', [ActivitySessionBookingController::class, 'store'])
            ->name('api.activities.sessions.book');
        Route::post('/activities/{activity}/options', [ActivityInventoryController::class, 'storeOption'])
            ->name('api.activities.options.store');
        Route::post('/activities/{activity}/schedules', [ActivityInventoryController::class, 'storeSchedule'])
            ->name('api.activities.schedules.store');
        Route::post('/activities/{activity}/schedules/{schedule}/generate-sessions', [ActivityInventoryController::class, 'generateSessions'])
            ->name('api.activities.schedules.generate-sessions');
        Route::post('/activities/{activity}/sessions', [ActivityInventoryController::class, 'storeSession'])
            ->name('api.activities.sessions.store');
        Route::post('/activities/{activity}/reviews', [ActivityReviewController::class, 'store'])
            ->name('api.activities.reviews.store');
        Route::post('/activities', [ActivityController::class, 'store'])->name('activities.store');
        Route::match(['put', 'patch'], '/activities/{activity}', [ActivityController::class, 'update'])
            ->name('activities.update');
        Route::delete('/activities/{activity}', [ActivityController::class, 'destroy'])
            ->name('activities.destroy');

        Route::middleware('permission:admin.panel.access')->prefix('admin')->group(function () {
            Route::post('/accommodations/{accommodation}/approve', [AccommodationWorkflowController::class, 'approve'])
                ->middleware('permission:accommodations.approve')->name('api.admin.accommodations.approve');
            Route::post('/accommodations/{accommodation}/reject', [AccommodationWorkflowController::class, 'reject'])
                ->middleware('permission:accommodations.reject')->name('api.admin.accommodations.reject');
            Route::post('/accommodations/{accommodation}/suspend', [AccommodationWorkflowController::class, 'suspend'])
                ->middleware('permission:accommodations.suspend')->name('api.admin.accommodations.suspend');
            Route::post('/activities/{activity}/review', [ActivityWorkflowController::class, 'review'])
                ->middleware('permission:activities.moderate')->name('api.admin.activities.review');
            Route::post('/activities/{activity}/approve', [ActivityWorkflowController::class, 'approve'])
                ->middleware('permission:activities.approve')->name('api.admin.activities.approve');
            Route::post('/activities/{activity}/reject', [ActivityWorkflowController::class, 'reject'])
                ->middleware('permission:activities.reject')->name('api.admin.activities.reject');
            Route::post('/activities/{activity}/suspend', [ActivityWorkflowController::class, 'suspend'])
                ->middleware('permission:activities.suspend')->name('api.admin.activities.suspend');
            Route::patch('/activity-reviews/{review}/moderate', [ActivityReviewController::class, 'moderate'])
                ->middleware('permission:reviews.moderate')->name('api.admin.activity-reviews.moderate');
        });

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
