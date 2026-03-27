<?php

use App\Http\Controllers\Panel\PregnantFemalesController;
use Illuminate\Support\Facades\Route;

Route::get('/ciaze', [PregnantFemalesController::class, 'index'])
    ->name('pregnancies.index');
