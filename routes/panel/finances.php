<?php

use App\Http\Controllers\Panel\FinancesController;
use Illuminate\Support\Facades\Route;

Route::get('/finances', [FinancesController::class, 'index'])->name('finances.index');
