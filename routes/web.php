<?php

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
    Route::get('/plans/{id}', PlansShow::class)->name('plans.show');

    // Dashboard (placeholder for future implementation)
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});
