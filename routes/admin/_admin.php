<?php

use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->middleware(['web', 'auth'])
    ->name('admin.')
    ->group(function () {
        Route::get('/', function () {
            return redirect()->route('admin.settings.index');
        })->name('home');

        require __DIR__ . '/settings.php';
        require __DIR__ . '/labels.php';
        require __DIR__ . '/config.php';
    });


