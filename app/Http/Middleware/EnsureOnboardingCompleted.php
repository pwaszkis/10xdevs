<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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
            return redirect()->route('onboarding.index')
                ->with('error', 'Ukończ onboarding, aby uzyskać dostęp do tej strony.');
        }

        return $next($request);
    }
}
