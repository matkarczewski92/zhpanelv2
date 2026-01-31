<?php

use App\Http\Controllers\Panel\FeedController;
use Illuminate\Support\Facades\Route;

Route::get('/karma', [FeedController::class, 'index'])
    ->name('feeds.index');
Route::post('/karma', [FeedController::class, 'store'])
    ->name('feeds.store');
Route::delete('/karma/{feed}', [FeedController::class, 'destroy'])
    ->name('feeds.destroy');
