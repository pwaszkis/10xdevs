<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure Onboarding Completed Middleware
 *
 * Redirects users who haven't completed onboarding to the onboarding flow.
 */
class EnsureOnboardingCompleted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && ! auth()->user()->hasCompletedOnboarding()) {
            return redirect()->route('onboarding')
                ->with('info', 'Proszę uzupełnić swój profil.');
        }

        return $next($request);
    }
}
