<?php

use App\Http\Controllers\Api\AnimalProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api.key', 'throttle:30,1'])->group(function (): void {
    Route::get('/animals/{secret_tag}', [AnimalProfileController::class, 'show'])
        ->name('api.animals.show');
});
