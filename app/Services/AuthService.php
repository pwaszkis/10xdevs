<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

/**
 * Authentication Service
 *
 * Handles user registration, login, OAuth integration, and account management.
 */
class AuthService
{
    /**
     * Register a new user with email and password.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function registerWithEmail(array $data): User
    {
        $user = User::create([
            'name' => $data['name'] ?? 'User',
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'onboarding_completed' => false,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return $user;
    }

    /**
     * Register or login user via Google OAuth.
     *
     * @param  array<string, mixed>  $googleUser
     */
    public function handleGoogleCallback(array $googleUser): User
    {
        // Find existing user by Google ID or email
        $user = User::where('google_id', $googleUser['id'])
            ->orWhere('email', $googleUser['email'])
            ->first();

        if ($user) {
            // Update Google ID if not set
            if (! $user->google_id) {
                $user->update([
                    'google_id' => $googleUser['id'],
                    'avatar_url' => $googleUser['avatar'] ?? null,
                ]);
            }

            // Mark email as verified (Google confirms email)
            if (! $user->email_verified_at) {
                $user->markEmailAsVerified();
            }
        } else {
            // Create new user
            $user = User::create([
                'name' => $googleUser['name'],
                'email' => $googleUser['email'],
                'google_id' => $googleUser['id'],
                'avatar_url' => $googleUser['avatar'] ?? null,
                'email_verified_at' => now(),
                'password' => null,
                'onboarding_completed' => false,
            ]);

            event(new Registered($user));
        }

        Auth::login($user);

        return $user;
    }

    /**
     * Attempt to authenticate user with email and password.
     *
     * @throws ValidationException
     */
    public function attemptLogin(string $email, string $password, bool $remember = false): bool
    {
        if (Auth::attempt(['email' => $email, 'password' => $password], $remember)) {
            session()->regenerate();

            return true;
        }

        throw ValidationException::withMessages([
            'email' => __('auth.failed'),
        ]);
    }

    /**
     * Logout the authenticated user.
     */
    public function logout(): void
    {
        Auth::guard('web')->logout();

        session()->invalidate();
        session()->regenerateToken();
    }

    /**
     * Send password reset link to user.
     */
    public function sendPasswordResetLink(string $email): string
    {
        $status = Password::sendResetLink(['email' => $email]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return __($status);
    }

    /**
     * Reset user password with token.
     *
     * @param  array<string, mixed>  $credentials
     *
     * @throws ValidationException
     */
    public function resetPassword(array $credentials): string
    {
        $status = Password::reset($credentials, function ($user, $password) {
            $user->forceFill([
                'password' => Hash::make($password),
            ])->save();

            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return __($status);
    }

    /**
     * Check if user has completed onboarding.
     */
    public function hasCompletedOnboarding(User $user): bool
    {
        return $user->onboarding_completed;
    }

    /**
     * Delete user account (GDPR compliance - hard delete with cascade).
     */
    public function deleteAccount(User $user): void
    {
        // Delete related data (cascade handled in model events)
        $user->travelPlans()->delete();
        $user->aiGenerations()->delete();
        $user->preferences()->delete();

        // Hard delete user
        $user->forceDelete();

        $this->logout();
    }
}
