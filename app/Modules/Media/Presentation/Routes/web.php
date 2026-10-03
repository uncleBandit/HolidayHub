<?php

use App\Modules\Media\Presentation\Livewire\MediaModeration;
use App\Modules\Media\Presentation\Livewire\Provider\MediaLibrary;
use App\Modules\Media\Presentation\Livewire\ProviderMediaProfile;
use App\Modules\Media\Presentation\Livewire\ReelFeed;
use Illuminate\Support\Facades\Route;

Route::get('/reels', ReelFeed::class)->name('reels');
Route::get('/providers/{provider}/media', ProviderMediaProfile::class)->name('providers.media');

Route::middleware(['auth', 'verified'])->prefix('provider')->name('provider.')->group(function () {
    Route::get('/media', MediaLibrary::class)->name('media');
});

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/media', MediaModeration::class)->name('moderation');
});
