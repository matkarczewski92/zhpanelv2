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
use App\Http\Controllers\Admin\Settings\FinanceCategoryController;
use App\Http\Controllers\Admin\Settings\ColorGroupController;
use App\Http\Controllers\Admin\Settings\GeneticsGeneratorController;
use App\Http\Controllers\Admin\Settings\EwelinkDeviceController;

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

// finance categories
Route::post('/settings/finance-categories', [FinanceCategoryController::class, 'store'])->name('settings.finance-categories.store');
Route::patch('/settings/finance-categories/{category}', [FinanceCategoryController::class, 'update'])->name('settings.finance-categories.update');
Route::delete('/settings/finance-categories/{category}', [FinanceCategoryController::class, 'destroy'])->name('settings.finance-categories.destroy');

// color groups
Route::post('/settings/color-groups', [ColorGroupController::class, 'store'])->name('settings.color-groups.store');
Route::patch('/settings/color-groups/{colorGroup}', [ColorGroupController::class, 'update'])->name('settings.color-groups.update');
Route::delete('/settings/color-groups/{colorGroup}', [ColorGroupController::class, 'destroy'])->name('settings.color-groups.destroy');

// genetics generator
Route::post('/settings/genetics-generator/generate', [GeneticsGeneratorController::class, 'generate'])->name('settings.genetics-generator.generate');
Route::post('/settings/genetics-generator/store', [GeneticsGeneratorController::class, 'store'])->name('settings.genetics-generator.store');

// ewelink devices
Route::post('/settings/ewelink-devices', [EwelinkDeviceController::class, 'store'])->name('settings.ewelink-devices.store');
Route::patch('/settings/ewelink-devices/{device}', [EwelinkDeviceController::class, 'update'])->name('settings.ewelink-devices.update');
Route::delete('/settings/ewelink-devices/{device}', [EwelinkDeviceController::class, 'destroy'])->name('settings.ewelink-devices.destroy');
Route::post('/settings/ewelink-devices/sync', [EwelinkDeviceController::class, 'sync'])->name('settings.ewelink-devices.sync');
