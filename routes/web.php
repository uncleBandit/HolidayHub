<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProviderController;
use App\Http\Livewire\WelcomePage;
use App\Livewire\Activity\ActivityShow;
use App\Livewire\Destination\DestinationShow;
use App\Livewire\Hotel\HotelShow;
use App\Models\Destination;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
/**
*Route::get('/', function () {
  *  return Inertia::render('Welcome', [
    *    'canLogin' => Route::has('login'),
     *   'canRegister' => Route::has('register'),
     *   'laravelVersion' => Application::VERSION,
     *   'phpVersion' => PHP_VERSION,
   * ]);
*});
**/

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

//Route::get('/', WelcomePage::class)->name('welcome');

Route::get('/', fn () => view('welcome'));
Route::get('/destination-page/{destination}', DestinationShow::class)
    ->name('destination.show');
    Route::get('/hotel/{slug}', HotelShow::class)
    ->name('hotel.show');
    Route::get('/activity/{slug}', ActivityShow::class)
    ->name('activity.show');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::resource('hotels', \App\Http\Controllers\HotelController::class);
    Route::resource('bookings', \App\Http\Controllers\BookingController::class);
    Route::resource('offers', \App\Http\Controllers\OfferController::class);
    Route::resource('reviews', \App\Http\Controllers\ReviewController::class);
    Route::resource('rooms', \App\Http\Controllers\RoomController::class);
    Route::resource('activities', \App\Http\Controllers\ActivityController::class);
    Route::resource('destinations', \App\Http\Controllers\DestinationController::class);
    Route::resource('packages', \App\Http\Controllers\PackageController::class);
    Route::group(['middleware' => ['role:admin']], function () {
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
    });

    Route::group(['middleware' => ['role:provider']], function () {
    Route::get('/provider/dashboard', [ProviderController::class, 'dashboard']);
    });

    Route::group(['middleware' => ['role:agent']], function () {
    Route::get('/agent/dashboard', [AgentController::class, 'dashboard']);
    });



});

require __DIR__.'/auth.php';
