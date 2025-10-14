<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register custom middleware aliases
        $middleware->alias([
            'onboarding.completed' => \App\Http\Middleware\EnsureOnboardingCompleted::class,
            'track.activity' => \App\Http\Middleware\TrackLastActivity::class,
        ]);

        // Add TrackLastActivity to web middleware group for session tracking
        $middleware->web(append: [
            \App\Http\Middleware\TrackLastActivity::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
