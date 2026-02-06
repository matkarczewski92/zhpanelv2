<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Config\ConfigController;
use App\Http\Controllers\Admin\Config\AnimalCategoryController;
use App\Http\Controllers\Admin\Config\AnimalTypeController;
use App\Http\Controllers\Admin\Config\GenotypeCategoryController;
use App\Http\Controllers\Admin\Config\TraitController;
use App\Http\Controllers\Admin\Config\TraitCompositionController;
use App\Http\Controllers\Admin\Config\WinteringStageController;
use App\Http\Controllers\Admin\Config\SystemConfigController;
use App\Http\Controllers\Admin\Config\FeedController;

Route::prefix('config')->name('config.')->group(function () {
    Route::get('/', [ConfigController::class, 'index'])->name('index');

    Route::post('/animal-categories', [AnimalCategoryController::class, 'store'])->name('animal-categories.store');
    Route::patch('/animal-categories/{category}', [AnimalCategoryController::class, 'update'])->name('animal-categories.update');
    Route::delete('/animal-categories/{category}', [AnimalCategoryController::class, 'destroy'])->name('animal-categories.destroy');

    Route::post('/animal-types', [AnimalTypeController::class, 'store'])->name('animal-types.store');
    Route::patch('/animal-types/{type}', [AnimalTypeController::class, 'update'])->name('animal-types.update');
    Route::delete('/animal-types/{type}', [AnimalTypeController::class, 'destroy'])->name('animal-types.destroy');

    Route::post('/genotype-categories', [GenotypeCategoryController::class, 'store'])->name('genotype-categories.store');
    Route::patch('/genotype-categories/{category}', [GenotypeCategoryController::class, 'update'])->name('genotype-categories.update');
    Route::delete('/genotype-categories/{category}', [GenotypeCategoryController::class, 'destroy'])->name('genotype-categories.destroy');

    Route::post('/traits', [TraitController::class, 'store'])->name('traits.store');
    Route::patch('/traits/{trait}', [TraitController::class, 'update'])->name('traits.update');
    Route::delete('/traits/{trait}', [TraitController::class, 'destroy'])->name('traits.destroy');

    Route::post('/traits/{trait}/genes', [TraitCompositionController::class, 'store'])->name('traits.genes.store');
    Route::delete('/traits/{trait}/genes/{dict}', [TraitCompositionController::class, 'destroy'])->name('traits.genes.destroy');

    Route::post('/wintering-stages', [WinteringStageController::class, 'store'])->name('wintering-stages.store');
    Route::patch('/wintering-stages/{stage}', [WinteringStageController::class, 'update'])->name('wintering-stages.update');
    Route::delete('/wintering-stages/{stage}', [WinteringStageController::class, 'destroy'])->name('wintering-stages.destroy');

    Route::post('/system-config', [SystemConfigController::class, 'store'])->name('system-config.store');
    Route::patch('/system-config/{config}', [SystemConfigController::class, 'update'])->name('system-config.update');
    Route::delete('/system-config/{config}', [SystemConfigController::class, 'destroy'])->name('system-config.destroy');

    Route::post('/feeds', [FeedController::class, 'store'])->name('feeds.store');
    Route::patch('/feeds/{feed}', [FeedController::class, 'update'])->name('feeds.update');
    Route::delete('/feeds/{feed}', [FeedController::class, 'destroy'])->name('feeds.destroy');
});
