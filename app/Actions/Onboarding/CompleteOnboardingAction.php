<?php

declare(strict_types=1);

namespace App\Actions\Onboarding;

use App\Models\User;
use Illuminate\Support\Facades\DB;

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
                'onboarding_completed_at' => now(),
            ]);

            return $user->fresh(['preferences']);
        });
    }
}
