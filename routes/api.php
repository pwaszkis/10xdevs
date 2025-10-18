<?php

use App\Http\Controllers\Api\TravelPlanFeedbackController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('travel-plans')->group(function () {
    // Feedback routes
    Route::post('/{plan}/feedback', [TravelPlanFeedbackController::class, 'store'])->name('api.travel-plans.feedback.store');
    Route::get('/{plan}/feedback', [TravelPlanFeedbackController::class, 'show'])->name('api.travel-plans.feedback.show');
    Route::delete('/{plan}/feedback', [TravelPlanFeedbackController::class, 'destroy'])->name('api.travel-plans.feedback.destroy');
});
