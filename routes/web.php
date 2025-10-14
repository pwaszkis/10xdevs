<?php

use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PlanPdfController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', HomeController::class)->name('home');

/*
|--------------------------------------------------------------------------
| Guest Routes (Unauthenticated Users Only)
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    // Google OAuth Routes
    Route::get('/auth/google', [OAuthController::class, 'redirectToGoogle'])
        ->name('auth.google');
    Route::get('/auth/google/callback', [OAuthController::class, 'handleGoogleCallback'])
        ->name('auth.google.callback');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {
    // Profile route (no email verification required)
    Route::view('profile', 'profile')->name('profile');

    // Onboarding route (requires auth and email verification)
    Route::get('onboarding', \App\Livewire\Onboarding\OnboardingWizard::class)
        ->middleware(['verified'])
        ->name('onboarding');

    // Welcome screen (shown after onboarding completion)
    Route::get('welcome', \App\Livewire\Welcome::class)
        ->middleware(['verified'])
        ->name('welcome');
});

/*
|--------------------------------------------------------------------------
| Authenticated & Verified Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'onboarding.completed'])->group(function () {
    // Dashboard requires completed onboarding
    Route::view('dashboard', 'dashboard')->name('dashboard');

    // Travel Plans routes
    Route::prefix('plans')->name('plans.')->group(function () {
        Route::get('/', function () {
            return redirect()->route('dashboard');
        })->name('index');

        Route::get('/create', \App\Livewire\Plans\CreatePlanForm::class)->name('create');
        Route::get('/{plan}', \App\Livewire\Plans\Show::class)->name('show');
        Route::get('/{plan}/pdf', [PlanPdfController::class, 'export'])->name('pdf');
    });
});

/*
|--------------------------------------------------------------------------
| Breeze Authentication Routes
|--------------------------------------------------------------------------
*/

require __DIR__ . '/auth.php';
Route::get('/admin/analytics', \App\Livewire\Admin\Analytics::class)->middleware(['auth', 'verified'])->name('admin.analytics');
