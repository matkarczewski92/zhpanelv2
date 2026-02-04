<?php

use App\Http\Controllers\Admin\ShippingListController;
use Illuminate\Support\Facades\Route;

Route::get('/shipping-list', [ShippingListController::class, 'index'])
    ->name('shipping-list.index');
Route::post('/shipping-list/print', [ShippingListController::class, 'print'])
    ->name('shipping-list.print');

