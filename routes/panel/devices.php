<?php

use App\Http\Controllers\Panel\DevicesController;
use Illuminate\Support\Facades\Route;

Route::get('/urzadzenia', [DevicesController::class, 'index'])
    ->name('devices.index');
Route::get('/urzadzenia/dane', [DevicesController::class, 'data'])
    ->name('devices.data');
Route::post('/urzadzenia/{device}/przelacz', [DevicesController::class, 'toggle'])
    ->name('devices.toggle');
Route::post('/urzadzenia/{device}/harmonogram', [DevicesController::class, 'updateSchedule'])
    ->name('devices.schedule');
Route::get('/urzadzenia/autoryzuj', [DevicesController::class, 'authorize'])
    ->name('devices.authorize');
Route::post('/urzadzenia/odswiez', [DevicesController::class, 'refresh'])
    ->name('devices.refresh');
