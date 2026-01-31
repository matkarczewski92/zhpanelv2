<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Settings\AdminSettingsController;
use App\Http\Controllers\Admin\Settings\AnimalCategoryController;
use App\Http\Controllers\Admin\Settings\AnimalTypeController;
use App\Http\Controllers\Admin\Settings\GenotypeCategoryController;
use App\Http\Controllers\Admin\Settings\TraitController;
use App\Http\Controllers\Admin\Settings\TraitDictionaryController;
use App\Http\Controllers\Admin\Settings\WinteringStageController;
use App\Http\Controllers\Admin\Settings\SystemConfigController;
use App\Http\Controllers\Admin\Settings\FeedController;

Route::get('/settings', AdminSettingsController::class)->name('settings.index');

// animal categories
Route::post('/settings/animal-categories', [AnimalCategoryController::class, 'store'])->name('settings.animal-categories.store');
Route::patch('/settings/animal-categories/{category}', [AnimalCategoryController::class, 'update'])->name('settings.animal-categories.update');
Route::delete('/settings/animal-categories/{category}', [AnimalCategoryController::class, 'destroy'])->name('settings.animal-categories.destroy');

// animal types
Route::post('/settings/animal-types', [AnimalTypeController::class, 'store'])->name('settings.animal-types.store');
Route::patch('/settings/animal-types/{type}', [AnimalTypeController::class, 'update'])->name('settings.animal-types.update');
Route::delete('/settings/animal-types/{type}', [AnimalTypeController::class, 'destroy'])->name('settings.animal-types.destroy');

// genotype categories
Route::post('/settings/genotype-categories', [GenotypeCategoryController::class, 'store'])->name('settings.genotype-categories.store');
Route::patch('/settings/genotype-categories/{category}', [GenotypeCategoryController::class, 'update'])->name('settings.genotype-categories.update');
Route::delete('/settings/genotype-categories/{category}', [GenotypeCategoryController::class, 'destroy'])->name('settings.genotype-categories.destroy');

// traits
Route::post('/settings/traits', [TraitController::class, 'store'])->name('settings.traits.store');
Route::patch('/settings/traits/{trait}', [TraitController::class, 'update'])->name('settings.traits.update');
Route::delete('/settings/traits/{trait}', [TraitController::class, 'destroy'])->name('settings.traits.destroy');

// trait dictionary (trait -> gene)
Route::post('/settings/traits/{trait}/genes', [TraitDictionaryController::class, 'store'])->name('settings.traits.genes.store');
Route::delete('/settings/traits/{trait}/genes/{dictionary}', [TraitDictionaryController::class, 'destroy'])->name('settings.traits.genes.destroy');

// wintering stages
Route::post('/settings/wintering-stages', [WinteringStageController::class, 'store'])->name('settings.wintering-stages.store');
Route::patch('/settings/wintering-stages/{stage}', [WinteringStageController::class, 'update'])->name('settings.wintering-stages.update');
Route::delete('/settings/wintering-stages/{stage}', [WinteringStageController::class, 'destroy'])->name('settings.wintering-stages.destroy');

// system config
Route::post('/settings/system-config', [SystemConfigController::class, 'store'])->name('settings.system-config.store');
Route::patch('/settings/system-config/{config}', [SystemConfigController::class, 'update'])->name('settings.system-config.update');
Route::delete('/settings/system-config/{config}', [SystemConfigController::class, 'destroy'])->name('settings.system-config.destroy');

// feeds
Route::post('/settings/feeds', [FeedController::class, 'store'])->name('settings.feeds.store');
Route::patch('/settings/feeds/{feed}', [FeedController::class, 'update'])->name('settings.feeds.update');
Route::delete('/settings/feeds/{feed}', [FeedController::class, 'destroy'])->name('settings.feeds.destroy');
