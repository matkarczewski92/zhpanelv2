<?php

\Illuminate\Support\Facades\Route::get('/animals', [\App\Http\Controllers\Animals\AnimalController::class, 'index'])
    ->name('animals.index');
\Illuminate\Support\Facades\Route::get('/animals/create', [\App\Http\Controllers\Animals\AnimalController::class, 'create'])
    ->name('animals.create');
\Illuminate\Support\Facades\Route::post('/animals', [\App\Http\Controllers\Animals\AnimalController::class, 'store'])
    ->name('animals.store');
\Illuminate\Support\Facades\Route::get('/animals/{animal}', [\App\Http\Controllers\Animals\AnimalController::class, 'show'])
    ->name('animals.show');
\Illuminate\Support\Facades\Route::get('/animals/{animal}/edit', [\App\Http\Controllers\Animals\AnimalController::class, 'edit'])
    ->name('animals.edit');
\Illuminate\Support\Facades\Route::put('/animals/{animal}', [\App\Http\Controllers\Animals\AnimalController::class, 'update'])
    ->name('animals.update');
\Illuminate\Support\Facades\Route::post('/animals/{animal}/delete', [\App\Http\Controllers\Animals\AnimalController::class, 'destroy'])
    ->name('animals.delete');

\Illuminate\Support\Facades\Route::post('/animals/{animal}/feedings', [\App\Http\Controllers\Animals\AnimalFeedingController::class, 'store'])
    ->name('animals.feedings.store');
\Illuminate\Support\Facades\Route::put('/animals/{animal}/feedings/{feeding}', [\App\Http\Controllers\Animals\AnimalFeedingController::class, 'update'])
    ->name('animals.feedings.update');
\Illuminate\Support\Facades\Route::delete('/animals/{animal}/feedings/{feeding}', [\App\Http\Controllers\Animals\AnimalFeedingController::class, 'destroy'])
    ->name('animals.feedings.destroy');

\Illuminate\Support\Facades\Route::post('/animals/{animal}/weights', [\App\Http\Controllers\Animals\AnimalWeightController::class, 'store'])
    ->name('animals.weights.store');
\Illuminate\Support\Facades\Route::put('/animals/{animal}/weights/{weight}', [\App\Http\Controllers\Animals\AnimalWeightController::class, 'update'])
    ->name('animals.weights.update');
\Illuminate\Support\Facades\Route::delete('/animals/{animal}/weights/{weight}', [\App\Http\Controllers\Animals\AnimalWeightController::class, 'destroy'])
    ->name('animals.weights.destroy');

\Illuminate\Support\Facades\Route::post('/animals/{animal}/molts', [\App\Http\Controllers\Animals\AnimalMoltController::class, 'store'])
    ->name('animals.molts.store');
\Illuminate\Support\Facades\Route::put('/animals/{animal}/molts/{molt}', [\App\Http\Controllers\Animals\AnimalMoltController::class, 'update'])
    ->name('animals.molts.update');
\Illuminate\Support\Facades\Route::delete('/animals/{animal}/molts/{molt}', [\App\Http\Controllers\Animals\AnimalMoltController::class, 'destroy'])
    ->name('animals.molts.destroy');

\Illuminate\Support\Facades\Route::post('/animals/{animal}/genotypes', [\App\Http\Controllers\Animals\AnimalGenotypeController::class, 'store'])
    ->name('animals.genotypes.store');
\Illuminate\Support\Facades\Route::delete('/animals/{animal}/genotypes/{genotype}', [\App\Http\Controllers\Animals\AnimalGenotypeController::class, 'destroy'])
    ->name('animals.genotypes.destroy');

\Illuminate\Support\Facades\Route::post('/animals/{animal}/photos', [\App\Http\Controllers\Animals\AnimalPhotoController::class, 'store'])
    ->name('animals.photos.store');
\Illuminate\Support\Facades\Route::delete('/animals/{animal}/photos/{photo}', [\App\Http\Controllers\Animals\AnimalPhotoController::class, 'destroy'])
    ->name('animals.photos.destroy');
\Illuminate\Support\Facades\Route::patch('/animals/{animal}/photos/{photo}/main', [\App\Http\Controllers\Animals\AnimalPhotoController::class, 'setMain'])
    ->name('animals.photos.main');
\Illuminate\Support\Facades\Route::patch('/animals/{animal}/photos/{photo}/website', [\App\Http\Controllers\Animals\AnimalPhotoController::class, 'toggleWebsite'])
    ->name('animals.photos.website');

\Illuminate\Support\Facades\Route::post('/animals/{animal}/offer', [\App\Http\Controllers\Animals\AnimalOfferController::class, 'store'])
    ->name('animals.offer.store');
\Illuminate\Support\Facades\Route::delete('/animals/{animal}/offer', [\App\Http\Controllers\Animals\AnimalOfferController::class, 'destroy'])
    ->name('animals.offer.destroy');
\Illuminate\Support\Facades\Route::delete('/animals/{animal}/offer/reservation', [\App\Http\Controllers\Animals\AnimalOfferController::class, 'destroyReservation'])
    ->name('animals.offer.reservation.destroy');
\Illuminate\Support\Facades\Route::post('/animals/{animal}/offer/sell', [\App\Http\Controllers\Animals\AnimalOfferController::class, 'sell'])
    ->name('animals.offer.sell');
\Illuminate\Support\Facades\Route::post('/animals/{animal}/offer/toggle-public', [\App\Http\Controllers\Animals\AnimalOfferController::class, 'togglePublic'])
    ->name('animals.offer.toggle-public');

\Illuminate\Support\Facades\Route::get('/animals/{animal}/passport', [\App\Http\Controllers\Animals\AnimalPassportController::class, 'show'])
    ->name('animals.passport');
\Illuminate\Support\Facades\Route::get('/animals/{animal}/label', [\App\Http\Controllers\Animals\AnimalLabelController::class, 'download'])
    ->name('animals.label');

\Illuminate\Support\Facades\Route::post('/animals/{animal}/toggle-public', \App\Http\Controllers\Animals\AnimalPublicVisibilityController::class)
    ->name('animals.toggle-public');
