<?php

use App\Http\Controllers\Panel\IncubationController;
use Illuminate\Support\Facades\Route;

Route::get('/inkubacja', [IncubationController::class, 'index'])
    ->name('incubation.index');
