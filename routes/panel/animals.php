<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Animals\AnimalController;
use App\Http\Controllers\Animals\AnimalFeedingController;
use App\Http\Controllers\Animals\AnimalWeightController;
use App\Http\Controllers\Animals\AnimalMoltController;
use App\Http\Controllers\Animals\AnimalGenotypeController;
use App\Http\Controllers\Animals\AnimalPhotoController;
use App\Http\Controllers\Animals\AnimalOfferController;
use App\Http\Controllers\Animals\AnimalPassportController;
use App\Http\Controllers\Animals\AnimalLabelController;
use App\Http\Controllers\Animals\AnimalPublicVisibilityController;
use App\Http\Controllers\Animals\AnimalColorGroupController;


Route::get('/animals', [AnimalController::class, 'index'])
    ->name('animals.index');
Route::get('/animals/create', [AnimalController::class, 'create'])
    ->name('animals.create');
Route::post('/animals', [AnimalController::class, 'store'])
    ->name('animals.store');
Route::get('/animals/{animal}', [AnimalController::class, 'show'])
    ->name('animals.show');
Route::get('/animals/{animal}/edit', [AnimalController::class, 'edit'])
    ->name('animals.edit');
Route::put('/animals/{animal}', [AnimalController::class, 'update'])
    ->name('animals.update');
Route::post('/animals/{animal}/delete', [AnimalController::class, 'destroy'])
    ->name('animals.delete');

Route::post('/animals/{animal}/feedings', [AnimalFeedingController::class, 'store'])
    ->name('animals.feedings.store');
Route::put('/animals/{animal}/feedings/{feeding}', [AnimalFeedingController::class, 'update'])
    ->name('animals.feedings.update');
Route::delete('/animals/{animal}/feedings/{feeding}', [AnimalFeedingController::class, 'destroy'])
    ->name('animals.feedings.destroy');

Route::post('/animals/{animal}/weights', [AnimalWeightController::class, 'store'])
    ->name('animals.weights.store');
Route::put('/animals/{animal}/weights/{weight}', [AnimalWeightController::class, 'update'])
    ->name('animals.weights.update');
Route::delete('/animals/{animal}/weights/{weight}', [AnimalWeightController::class, 'destroy'])
    ->name('animals.weights.destroy');

Route::post('/animals/{animal}/molts', [AnimalMoltController::class, 'store'])
    ->name('animals.molts.store');
Route::put('/animals/{animal}/molts/{molt}', [AnimalMoltController::class, 'update'])
    ->name('animals.molts.update');
Route::delete('/animals/{animal}/molts/{molt}', [AnimalMoltController::class, 'destroy'])
    ->name('animals.molts.destroy');

Route::post('/animals/{animal}/genotypes', [AnimalGenotypeController::class, 'store'])
    ->name('animals.genotypes.store');
Route::delete('/animals/{animal}/genotypes/{genotype}', [AnimalGenotypeController::class, 'destroy'])
    ->name('animals.genotypes.destroy');

Route::post('/animals/{animal}/photos', [AnimalPhotoController::class, 'store'])
    ->name('animals.photos.store');
Route::delete('/animals/{animal}/photos/{photo}', [AnimalPhotoController::class, 'destroy'])
    ->name('animals.photos.destroy');
Route::patch('/animals/{animal}/photos/{photo}/main', [AnimalPhotoController::class, 'setMain'])
    ->name('animals.photos.main');
Route::patch('/animals/{animal}/photos/{photo}/website', [AnimalPhotoController::class, 'toggleWebsite'])
    ->name('animals.photos.website');

Route::put('/animals/{animal}/offer', [AnimalOfferController::class, 'update'])
    ->name('animals.offer.update');
Route::post('/animals/{animal}/offer', [AnimalOfferController::class, 'update'])
    ->name('animals.offer.store');
Route::delete('/animals/{animal}/offer', [AnimalOfferController::class, 'destroy'])
    ->name('animals.offer.destroy');
Route::delete('/animals/{animal}/offer/reservation', [AnimalOfferController::class, 'destroyReservation'])
    ->name('animals.offer.reservation.destroy');
Route::post('/animals/{animal}/offer/sell', [AnimalOfferController::class, 'sell'])
    ->name('animals.offer.sell');
Route::post('/animals/{animal}/offer/toggle-public', [AnimalOfferController::class, 'togglePublic'])
    ->name('animals.offer.toggle-public');

Route::get('/animals/{animal}/passport', [AnimalPassportController::class, 'show'])
    ->name('animals.passport');
Route::get('/animals/{animal}/label', [AnimalLabelController::class, 'download'])
    ->name('animals.label');
Route::get('/animals/{animal}/label-secret', [AnimalLabelController::class, 'downloadSecret'])
    ->name('animals.label.secret');

Route::post('/animals/{animal}/toggle-public', AnimalPublicVisibilityController::class)
    ->name('animals.toggle-public');

Route::post('/animals/{animal}/color-groups', AnimalColorGroupController::class)
    ->name('animals.color-groups.sync');
