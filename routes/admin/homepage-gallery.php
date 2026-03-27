<?php

use App\Http\Controllers\Admin\AdminHomepageGalleryController;
use Illuminate\Support\Facades\Route;

Route::get('/homepage-gallery', [AdminHomepageGalleryController::class, 'index'])
    ->name('homepage-gallery.index');
Route::patch('/homepage-gallery/{photo}/remove', [AdminHomepageGalleryController::class, 'remove'])
    ->name('homepage-gallery.remove');
