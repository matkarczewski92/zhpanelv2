<?php

use App\Http\Controllers\OffersController;
use Illuminate\Support\Facades\Route;

Route::get('/offers', [OffersController::class, 'index'])->name('offers.index');
Route::post('/offers/passports', [OffersController::class, 'bulkPassport'])->name('offers.passports');
Route::post('/offers/labels/export', [OffersController::class, 'exportLabels'])->name('offers.labels.export');
Route::patch('/offers/bulk-edit', [OffersController::class, 'bulkEdit'])->name('offers.bulkEdit');
