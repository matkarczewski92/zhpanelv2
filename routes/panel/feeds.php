<?php

use App\Http\Controllers\Panel\FeedController;
use App\Http\Controllers\Panel\FeedDeliveryController;
use Illuminate\Support\Facades\Route;

Route::get('/karma', [FeedController::class, 'index'])
    ->name('feeds.index');
Route::post('/karma', [FeedController::class, 'store'])
    ->name('feeds.store');
Route::post('/karma/planning/recalculate', [FeedController::class, 'recalculatePlanning'])
    ->name('feeds.planning.recalculate');
Route::delete('/karma/{feed}', [FeedController::class, 'destroy'])
    ->name('feeds.destroy');

Route::post('/karma/delivery/items', [FeedDeliveryController::class, 'store'])
    ->name('feeds.delivery.items.store');
Route::delete('/karma/delivery/items/{feed}', [FeedDeliveryController::class, 'destroy'])
    ->name('feeds.delivery.items.destroy');
Route::post('/karma/delivery/commit', [FeedDeliveryController::class, 'commit'])
    ->name('feeds.delivery.commit');
