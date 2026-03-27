<?php

use App\Http\Controllers\Panel\QrScannerController;
use Illuminate\Support\Facades\Route;

Route::prefix('qr-scanner')
    ->name('qr-scanner.')
    ->group(function (): void {
        Route::get('/', [QrScannerController::class, 'index'])->name('index');
        Route::post('/resolve', [QrScannerController::class, 'resolve'])->name('resolve');
        Route::post('/feedings', [QrScannerController::class, 'storeFeeding'])->name('feedings.store');
        Route::post('/weights', [QrScannerController::class, 'storeWeight'])->name('weights.store');
        Route::post('/molts', [QrScannerController::class, 'storeMolt'])->name('molts.store');
    });
