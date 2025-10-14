<?php

declare(strict_types=1);

namespace App\Actions\Onboarding;

use App\Mail\WelcomeEmail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Complete Onboarding Action
 *
 * Marks user onboarding as complete and updates preferences.
 */
class CompleteOnboardingAction
{
    /**
     * Execute the action.
     *
     * @param  array<string, mixed>  $preferences
     */
    public function execute(User $user, array $preferences = []): User
    {
        return DB::transaction(function () use ($user, $preferences) {
            // Update user preferences if provided
            if (! empty($preferences)) {
                $user->preferences()->update($preferences);
            }

            // Mark onboarding as complete
            $user->update([
                'onboarding_completed' => true,
                'onboarding_completed_at' => now(),
                'onboarding_step' => 4,
            ]);

            // Send welcome email
            Mail::to($user->email)->queue(new WelcomeEmail($user));

            return $user->fresh(['preferences']);
        });
    }
}
