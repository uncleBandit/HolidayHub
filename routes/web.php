<?php

use App\Modules\Accommodation\Presentation\Livewire\AccommodationList;
use App\Modules\Accommodation\Presentation\Livewire\BedAndBreakfast\BedAndBreakfastShow;
use App\Modules\Accommodation\Presentation\Livewire\Hotel\HotelCreate;
use App\Modules\Accommodation\Presentation\Livewire\Hotel\HotelShow;
use App\Modules\Accommodation\Presentation\Livewire\Stays\StaysIndex;
use App\Modules\Accommodation\Presentation\Livewire\Villa\VillaShow;
use App\Modules\Activities\Presentation\Livewire\Activity\ActivityShow;
use App\Modules\Activities\Presentation\Livewire\Activity\Activities;
use App\Modules\Activities\Presentation\Livewire\Activity\ExperiencesIndex;
use App\Modules\Agents\Presentation\Livewire\Agent\AgentDashboard;
use App\Modules\Agents\Presentation\Livewire\Agent\AgentPackages;
use App\Modules\Booking\Presentation\Livewire\Booking\BookingConfirmation;
use App\Modules\Booking\Presentation\Livewire\Booking\BookingIndex;
use App\Modules\Booking\Presentation\Livewire\Booking\BookingShow;
use App\Modules\Destinations\Presentation\Livewire\Destination\DestinationIndex;
use App\Modules\Destinations\Presentation\Livewire\Destination\DestinationShow;
use App\Modules\Destinations\Presentation\Livewire\Discover;
use App\Modules\Destinations\Presentation\Livewire\Home\HomeLanding;
use App\Modules\Destinations\Presentation\Livewire\Search\SearchBar;
use App\Modules\Identity\Presentation\Http\Controllers\Api\V1\ProfileController;
use App\Modules\Identity\Presentation\Livewire\Dashboard\DashboardPage;
use App\Modules\Identity\Presentation\Livewire\UserProfile;
use App\Modules\Packages\Presentation\Livewire\Package\PackageCreate;
use App\Modules\Packages\Presentation\Livewire\Package\PackageShow;
use App\Modules\Providers\Presentation\Livewire\Provider\ProviderDashboard;
use Illuminate\Support\Facades\Route;

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
Route::get('/', HomeLanding::class)->name('welcome');
Route::get('destinations', DestinationIndex::class)->name('destination.index');

// The unified stays section: hotels, villas and B&Bs in one reels-first place.
Route::get('stays', StaysIndex::class)->name('stays.index');
Route::get('stays/reels', StaysIndex::class)
    ->defaults('view', 'reels')
    ->name('stays.index.reels');

// Legacy per-type listings now fold into the unified section.
Route::get('hotels', fn () => redirect()->route('stays.index', ['type' => 'hotel']))->name('hotel.index');
Route::get('bed-and-breakfasts', fn () => redirect()->route('stays.index', ['type' => 'bed_and_breakfast']))->name('bedandbreakfast-index');
// The experiences storefront: everything a guest can go and do, reels-first.
Route::get('experiences', ExperiencesIndex::class)->name('experiences.index');
Route::get('experiences/reels', ExperiencesIndex::class)
    ->defaults('view', 'reels')
    ->name('experiences.index.reels');

// An activity is something a guest does. Browsing and buying one must not
// require an account, so the detail page is public rather than guest-only.
Route::get('/activity/{activity:slug}', ActivityShow::class)->name('activity.show');

// The legacy activities listing now folds into the experiences section.
Route::get('/activities', fn () => redirect()->route('experiences.index'))->name('activities.page');

// Authenticated user pages
Route::middleware(['auth', 'verified'])->group(function () {
    // Generic dashboard (for all users)
    Route::get('/dashboard', DashboardPage::class)->name('dashboard');
    Route::get('/search', SearchBar::class)->name('search.results');

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/userprofile', UserProfile::class)->name('userprofile');

    // Agent routes
    Route::prefix('agent')->name('agent.')->group(function () {
        Route::get('/dashboard', AgentDashboard::class)->name('dashboard');
        Route::get('/packages', AgentPackages::class)->name('packages');
        Route::get('/packages/create', PackageCreate::class)->name('packages.create');
    });

    // Provider routes
    Route::middleware('role:provider')->prefix('provider')->name('provider.')->group(function () {
        Route::get('/dashboard', ProviderDashboard::class)->name('dashboard');
        Route::get('/hotel/create', HotelCreate::class)->name('hotel-create');
        Route::get('/villa/create', \App\Modules\Accommodation\Presentation\Livewire\Villa\VillaCreate::class)->name('villa-create');
        Route::get('/bed-and-breakfast/create', \App\Modules\Accommodation\Presentation\Livewire\BedAndBreakfast\BedAndBreakfastCreate::class)->name('bedandbreakfast-create');

    });

    // Guest routes
    Route::get('/discover', Discover::class)->name('discover');
    Route::get('/bookings/{booking}', BookingShow::class)->name('bookings-show');
    Route::get('/bookings', BookingIndex::class)->name('bookings');
    Route::get('/booking-confirmation/{booking}', BookingConfirmation::class)->name('booking-confirmation');
    Route::get('/destination/{destination}', DestinationShow::class)->name('destination.show');
    Route::get('/hotel/{hotel}', HotelShow::class)->name('hotel-show');
    Route::get('/bed-and-breakfast/{bnb:slug}', BedAndBreakfastShow::class)->name('bedandbreakfast.show');
    Route::get('/villa/{villa:slug}', VillaShow::class)->name('villa.show');
    Route::get('package/{package:slug}', PackageShow::class)->name('packages-show');
    Route::get('/accommodationlist', AccommodationList::class)->name('accommodation.list');

});

require __DIR__.'/auth.php';
