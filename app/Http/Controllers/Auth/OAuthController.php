<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

/**
 * OAuth Controller
 *
 * Handles Google OAuth authentication flow.
 */
class OAuthController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private readonly AuthService $authService
    ) {}

    /**
     * Redirect to Google OAuth provider.
     */
    public function redirectToGoogle(): RedirectResponse
    {
        /** @phpstan-ignore-next-line */
        return Socialite::driver('google')
            ->scopes(['email', 'profile'])
            ->redirect();
    }

    /**
     * Handle Google OAuth callback.
     */
    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            /** @phpstan-ignore-next-line */
            $googleUser = Socialite::driver('google')->stateless()->user();

            $user = $this->authService->handleGoogleCallback([
                'id' => $googleUser->id,
                'name' => $googleUser->name,
                'email' => $googleUser->email,
                'avatar' => $googleUser->avatar,
            ]);

            Log::channel('auth')->info('User authenticated via Google OAuth', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            // Redirect based on onboarding status
            if ($user->needsOnboarding()) {
                return redirect()->route('onboarding')
                    ->with('success', 'Welcome! Please complete your profile setup.');
            }

            return redirect()->route('dashboard')
                ->with('success', 'Welcome back!');

        } catch (\Exception $e) {
            Log::channel('auth')->error('Google OAuth failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('register')
                ->with('error', 'Failed to authenticate with Google. Please try again or use email registration.');
        }
    }
}
