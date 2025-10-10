<?php

use App\Http\Controllers\Api\TravelPlanController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('travel-plans')->group(function () {
    Route::get('/{id}', [TravelPlanController::class, 'show'])->name('api.travel-plans.show');
    Route::delete('/{id}', [TravelPlanController::class, 'destroy'])->name('api.travel-plans.destroy');
    Route::post('/{id}/generate', [TravelPlanController::class, 'generate'])->name('api.travel-plans.generate');
    Route::get('/{id}/generation-status', [TravelPlanController::class, 'generationStatus'])->name('api.travel-plans.generation-status');
    Route::get('/{id}/pdf', [TravelPlanController::class, 'exportPdf'])->name('api.travel-plans.pdf');
});
