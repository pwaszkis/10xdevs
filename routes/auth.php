<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
|
| Basic authentication routes for login/logout functionality.
| TODO: Install Laravel Breeze fully or implement custom auth.
|
*/

// Login routes
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', function () {
    $credentials = request()->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (auth()->attempt($credentials, request()->boolean('remember'))) {
        request()->session()->regenerate();

        return redirect()->intended('/dashboard');
    }

    return back()->withErrors([
        'email' => 'Podane dane są nieprawidłowe.',
    ])->onlyInput('email');
});

// Logout route
Route::post('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/');
})->name('logout');

// Register routes (placeholder)
Route::get('/register', function () {
    return view('auth.register');
})->name('register');

// Password reset routes (placeholder)
Route::get('/forgot-password', function () {
    return view('auth.forgot-password');
})->name('password.request');
