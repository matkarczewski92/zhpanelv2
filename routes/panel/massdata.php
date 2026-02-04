<?php

use App\Http\Controllers\Panel\MassDataController;
use Illuminate\Support\Facades\Route;

Route::get('/massdata', [MassDataController::class, 'index'])
    ->name('massdata.index');
Route::post('/massdata/commit', [MassDataController::class, 'commit'])
    ->name('massdata.commit');

