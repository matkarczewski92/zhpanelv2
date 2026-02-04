<?php

use App\Http\Controllers\Panel\FinancesController;
use Illuminate\Support\Facades\Route;

Route::get('/finances', [FinancesController::class, 'index'])->name('finances.index');
Route::post('/finances/transactions', [FinancesController::class, 'storeTransaction'])->name('finances.transactions.store');
Route::put('/finances/transactions/{finance}', [FinancesController::class, 'updateTransaction'])->name('finances.transactions.update');
Route::delete('/finances/transactions/{finance}', [FinancesController::class, 'destroyTransaction'])->name('finances.transactions.destroy');
