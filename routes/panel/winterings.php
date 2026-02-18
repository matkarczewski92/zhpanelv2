<?php

use App\Http\Controllers\Panel\WinteringsController;
use Illuminate\Support\Facades\Route;

Route::get('/zimowanie', [WinteringsController::class, 'index'])
    ->name('winterings.index');
Route::get('/zimowanie/dane', [WinteringsController::class, 'data'])
    ->name('winterings.data');
Route::post('/zimowanie/{animal}/etap/{wintering}/kolejny', [WinteringsController::class, 'advanceStage'])
    ->name('winterings.advance-stage');

