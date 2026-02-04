<?php

use App\Http\Controllers\Admin\AdminLabelsController;
use Illuminate\Support\Facades\Route;

Route::get('/labels/print', [AdminLabelsController::class, 'print'])->name('labels.print');
Route::post('/labels/export', [AdminLabelsController::class, 'export'])->name('labels.export');
Route::get('/labels/secret/print', [AdminLabelsController::class, 'printSecret'])->name('labels.secret.print');
Route::post('/labels/secret/export', [AdminLabelsController::class, 'exportSecret'])->name('labels.secret.export');
