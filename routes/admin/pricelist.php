<?php

use App\Http\Controllers\Admin\PriceListController;
use Illuminate\Support\Facades\Route;

Route::get('/pricelist', [PriceListController::class, 'index'])
    ->name('pricelist.index');
Route::post('/pricelist/print', [PriceListController::class, 'print'])
    ->name('pricelist.print');

