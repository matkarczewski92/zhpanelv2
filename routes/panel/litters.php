<?php

use App\Http\Controllers\Panel\LittersController;
use Illuminate\Support\Facades\Route;

Route::get('/mioty', [LittersController::class, 'index'])->name('litters.index');
Route::get('/mioty/create', [LittersController::class, 'create'])->name('litters.create');
Route::post('/mioty', [LittersController::class, 'store'])->name('litters.store');
Route::delete('/mioty/planned/bulk-destroy', [LittersController::class, 'bulkDestroyPlanned'])->name('litters.bulk-destroy-planned');
Route::post('/mioty/{litter}/gallery', [LittersController::class, 'storeGalleryPhoto'])->name('litters.gallery.store');
Route::delete('/mioty/{litter}/gallery/{photo}', [LittersController::class, 'destroyGalleryPhoto'])->name('litters.gallery.destroy');
Route::post('/mioty/{litter}/offspring', [LittersController::class, 'storeOffspring'])->name('litters.offspring.store');
Route::put('/mioty/{litter}/offspring', [LittersController::class, 'updateOffspringBatch'])->name('litters.offspring.update-batch');
Route::patch('/mioty/{litter}/adnotacje', [LittersController::class, 'updateAdnotation'])->name('litters.adnotation.update');
Route::get('/mioty/{litter}', [LittersController::class, 'show'])->name('litters.show');
Route::get('/mioty/{litter}/edit', [LittersController::class, 'edit'])->name('litters.edit');
Route::put('/mioty/{litter}', [LittersController::class, 'update'])->name('litters.update');
Route::delete('/mioty/{litter}', [LittersController::class, 'destroy'])->name('litters.destroy');
