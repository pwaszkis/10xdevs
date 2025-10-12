<?php

use App\Http\Controllers\Api\TravelPlanController;
use App\Http\Controllers\Api\TravelPlanFeedbackController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('travel-plans')->group(function () {
    Route::get('/{id}', [TravelPlanController::class, 'show'])->name('api.travel-plans.show');
    Route::delete('/{id}', [TravelPlanController::class, 'destroy'])->name('api.travel-plans.destroy');
    Route::post('/{id}/generate', [TravelPlanController::class, 'generate'])->name('api.travel-plans.generate');
    Route::get('/{id}/generation-status', [TravelPlanController::class, 'generationStatus'])->name('api.travel-plans.generation-status');
    Route::get('/{id}/pdf', [TravelPlanController::class, 'exportPdf'])->name('api.travel-plans.pdf');

    // Feedback routes
    Route::post('/{plan}/feedback', [TravelPlanFeedbackController::class, 'store'])->name('api.travel-plans.feedback.store');
    Route::get('/{plan}/feedback', [TravelPlanFeedbackController::class, 'show'])->name('api.travel-plans.feedback.show');
    Route::delete('/{plan}/feedback', [TravelPlanFeedbackController::class, 'destroy'])->name('api.travel-plans.feedback.destroy');
});
