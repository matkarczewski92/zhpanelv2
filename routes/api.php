<?php

use App\Http\Controllers\Api\CurrentOfferController;
use App\Http\Controllers\Api\AnimalProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/offers/current', [CurrentOfferController::class, 'index'])
    ->name('api.offers.current');

Route::middleware(['api.key', 'throttle:30,1'])->group(function (): void {
    Route::get('/animals/{secret_tag}', [AnimalProfileController::class, 'show'])
        ->name('api.animals.show');
});
