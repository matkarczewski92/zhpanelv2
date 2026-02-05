<?php

use App\Http\Controllers\Panel\LittersPlanningController;
use App\Http\Controllers\Panel\OffspringPredictionController;
use Illuminate\Support\Facades\Route;

Route::get('/litters-planning', [LittersPlanningController::class, 'index'])
    ->name('litters-planning.index');
Route::post('/litters-planning/predict', [OffspringPredictionController::class, 'predict'])
    ->name('litters-planning.predict');
Route::post('/litters-planning/female-preview', [LittersPlanningController::class, 'femalePreview'])
    ->name('litters-planning.female-preview');
Route::post('/litters-planning/summary', [LittersPlanningController::class, 'summary'])
    ->name('litters-planning.summary');
Route::post('/litters-planning/plans', [LittersPlanningController::class, 'store'])
    ->name('litters-planning.store');
Route::post('/litters-planning/plans/{plan}/realize', [LittersPlanningController::class, 'realize'])
    ->name('litters-planning.realize');
Route::delete('/litters-planning/plans/{plan}', [LittersPlanningController::class, 'destroy'])
    ->name('litters-planning.destroy');

Route::post('/litters-planning/roadmaps', [LittersPlanningController::class, 'storeRoadmap'])
    ->name('litters-planning.roadmaps.store');
Route::put('/litters-planning/roadmaps/{roadmap}', [LittersPlanningController::class, 'updateRoadmap'])
    ->name('litters-planning.roadmaps.update');
Route::post('/litters-planning/roadmaps/{roadmap}/refresh', [LittersPlanningController::class, 'refreshRoadmap'])
    ->name('litters-planning.roadmaps.refresh');
Route::patch('/litters-planning/roadmaps/{roadmap}/step-status', [LittersPlanningController::class, 'updateRoadmapStepStatus'])
    ->name('litters-planning.roadmaps.step-status');
Route::delete('/litters-planning/roadmaps/{roadmap}', [LittersPlanningController::class, 'destroyRoadmap'])
    ->name('litters-planning.roadmaps.destroy');
