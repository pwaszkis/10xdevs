<?php

use App\Livewire\Plans\CreatePlanForm;
use App\Livewire\Plans\Show as PlansShow;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Authentication routes
require __DIR__.'/auth.php';

// Onboarding routes (placeholder for future implementation)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/onboarding', function () {
        return 'Onboarding - TODO';
    })->name('onboarding.index');
});

// Authenticated routes with onboarding completed requirement
Route::middleware(['auth', 'verified', 'onboarding.completed'])->group(function () {
    // Travel Plans
    Route::get('/plans/create', CreatePlanForm::class)->name('plans.create');
    Route::get('/plans/{travelId}/edit', CreatePlanForm::class)->name('plans.edit');
    Route::get('/plans/{id}', PlansShow::class)->name('plans.show');

    // Dashboard (placeholder for future implementation)
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

// ============================================================
// DEVELOPMENT ONLY - Testing routes without authentication
// TODO: Remove before production deployment
// ============================================================
if (app()->environment(['local', 'development'])) {
    Route::middleware([\App\Http\Middleware\DevAutoLogin::class])->group(function () {
        Route::get('/dev/plans/create', CreatePlanForm::class)->name('dev.plans.create');
        Route::get('/dev/plans/{travelId}/edit', CreatePlanForm::class)->name('dev.plans.edit');
    });
}
