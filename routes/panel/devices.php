<?php

use App\Http\Controllers\Panel\DevicesController;
use Illuminate\Support\Facades\Route;

Route::get('/urzadzenia', [DevicesController::class, 'index'])
    ->name('devices.index');

