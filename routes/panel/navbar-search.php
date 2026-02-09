<?php

use App\Http\Controllers\Panel\NavbarSearchController;
use Illuminate\Support\Facades\Route;

Route::get('/navbar-search', [NavbarSearchController::class, 'go'])
    ->name('navbar-search.go');
Route::get('/navbar-search/suggest', [NavbarSearchController::class, 'suggest'])
    ->name('navbar-search.suggest');

