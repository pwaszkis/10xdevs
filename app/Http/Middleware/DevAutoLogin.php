<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dev Auto Login Middleware
 *
 * Automatically logs in a test user for development routes.
 * ONLY works in local/development environments.
 */
class DevAutoLogin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only work in local/development
        if (! app()->environment(['local', 'development'])) {
            abort(404);
        }

        // Skip if already authenticated
        if (auth()->check()) {
            return $next($request);
        }

        // Get or create test user
        $user = User::first();

        if (! $user) {
            $user = User::create([
                'email' => 'test@example.com',
                'nickname' => 'Test User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'onboarding_completed_at' => now(),
                'onboarding_step' => 4,
            ]);

            // Create preferences
            \App\Models\UserPreference::create([
                'user_id' => $user->id,
                'language' => 'pl',
                'timezone' => 'Europe/Warsaw',
                'currency' => 'PLN',
                'notifications_enabled' => true,
                'email_notifications' => true,
                'push_notifications' => false,
                'theme' => 'light',
                'interests_categories' => ['history_culture', 'gastronomy'],
                'travel_pace' => 'moderate',
                'budget_level' => 'standard',
                'transport_preference' => 'mixed',
            ]);
        }

        // Login user
        auth()->login($user);

        return $next($request);
    }
}
