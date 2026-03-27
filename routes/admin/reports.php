<?php

use App\Http\Controllers\Admin\AdminReportsController;
use Illuminate\Support\Facades\Route;

Route::get('/reports', [AdminReportsController::class, 'index'])
    ->name('reports.index');
Route::post('/reports/preview', [AdminReportsController::class, 'preview'])
    ->name('reports.preview');
Route::post('/reports', [AdminReportsController::class, 'store'])
    ->name('reports.store');
Route::get('/reports/{report}/preview', [AdminReportsController::class, 'previewStored'])
    ->name('reports.history.preview');
Route::get('/reports/{report}/download', [AdminReportsController::class, 'download'])
    ->name('reports.history.download');
Route::delete('/reports/{report}', [AdminReportsController::class, 'destroy'])
    ->name('reports.destroy');
