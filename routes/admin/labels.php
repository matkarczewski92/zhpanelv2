<?php

use App\Http\Controllers\Admin\AdminLitterLabelsController;
use App\Http\Controllers\Admin\AdminLabelsController;
use Illuminate\Support\Facades\Route;

Route::get('/labels/print', [AdminLabelsController::class, 'print'])->name('labels.print');
Route::post('/labels/export', [AdminLabelsController::class, 'export'])->name('labels.export');
Route::get('/labels/secret/print', [AdminLabelsController::class, 'printSecret'])->name('labels.secret.print');
Route::post('/labels/secret/export', [AdminLabelsController::class, 'exportSecret'])->name('labels.secret.export');
Route::get('/labels/litters/print', [AdminLitterLabelsController::class, 'index'])->name('labels.litters.print');
Route::post('/labels/litters/export', [AdminLitterLabelsController::class, 'export'])->name('labels.litters.export');
