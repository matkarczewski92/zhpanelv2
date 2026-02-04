<?php

require __DIR__ . '/panel/_panel.php';
require __DIR__ . '/admin/_admin.php';

use App\Http\Controllers\Public\PublicProfileController;
use App\Http\Controllers\Public\LandingController;

Route::get('/', [LandingController::class, 'index'])->name('web.home');
Route::post('/profile/lookup', [LandingController::class, 'lookup'])->name('profile.lookup');

Route::get('/profile/{code}', [PublicProfileController::class, 'show'])->name('profile.show');
Route::get('/profile/{code}/weights', [PublicProfileController::class, 'weights'])->name('profile.weights');
Route::get('/profile/{code}/molts', [PublicProfileController::class, 'molts'])->name('profile.molts');
