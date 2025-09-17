<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\ProfileController;
use App\Livewire\Provider\ProviderDashboard;
use App\Livewire\Package\PackageCreate;
use App\Livewire\Dashboard\DashboardPage;
use App\Livewire\Destination\DestinationShow;
use App\Livewire\Hotel\HotelShow;
use App\Livewire\Activity\ActivityShow;
use App\Livewire\Agent\AgentDashboard;
use App\Livewire\Agent\AgentPackages;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Auth\ConfirmPassword;
use App\Livewire\Auth\EmailVerification;
use App\Livewire\Auth\UpdatePassword;
use App\Livewire\BedAndBreakfast\BedAndBreakfastShow;
use App\Livewire\Package\PackageShow;
use App\Livewire\Villa\VillaShow;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Keep your web routes clean.
| API controllers belong in routes/api.php.
| Livewire and frontend pages stay here.
|
*/

// Public pages
Route::get('/', fn () => view('welcome'))->name('welcome');
Route::get('/destination/{destination}', DestinationShow::class)->name('destination.show');
Route::get('/hotel/{slug}', HotelShow::class)->name('hotel.show');
Route::get('/activity/{slug}', ActivityShow::class)->name('activity.show');
Route::get('/bed-and-breakfast/{slug}', BedAndBreakfastShow::class)->name('bedandbreakfast.show');
Route::get('/villa/{slug}', VillaShow::class)->name('villa.show');
Route::get('package/{slug}', PackageShow::class)->name('packages-show');

// Authenticated user pages
Route::middleware(['auth', 'verified'])->group(function () {
    // Generic dashboard (for all users)
    Route::get('/dashboard', DashboardPage::class)->name('dashboard');

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Agent routes
    Route::prefix('agent')->name('agent.')->group(function () {
        Route::get('/dashboard', AgentDashboard::class)->name('dashboard');
        Route::get('/packages', AgentPackages::class)->name('packages');
        Route::get('/packages/create', PackageCreate::class)->name('packages.create');
    });

    // Provider routes
    Route::middleware(['auth', 'verified'])->prefix('provider')->name('provider.')->group(function () {
    Route::get('/dashboard', ProviderDashboard::class)->name('dashboard');
});
});

require __DIR__.'/auth.php';
