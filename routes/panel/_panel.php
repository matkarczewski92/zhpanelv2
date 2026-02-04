<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::get('/', function () {
    return view('welcome');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware(['web', 'auth'])
    ->name('logout');

Route::prefix('panel')
    ->middleware(['web', 'auth'])
    ->name('panel.')
    ->group(function () {
        Route::get('/', function () {
            return view('admin.dashboard');
        })->name('home');

        require __DIR__ . '/animals.php';
        require __DIR__ . '/feeds.php';
        require __DIR__ . '/litters.php';
        require __DIR__ . '/finances.php';
        require __DIR__ . '/offers.php';
    });
