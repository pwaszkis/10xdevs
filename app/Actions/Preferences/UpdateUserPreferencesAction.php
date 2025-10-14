<?php

declare(strict_types=1);

namespace App\Actions\Preferences;

use App\Models\User;
use App\Models\UserPreference;

/**
 * Update User Preferences Action
 *
 * Handles updating user preferences.
 */
class UpdateUserPreferencesAction
{
    /**
     * Execute the action.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(User $user, array $data): UserPreference
    {
        $preferences = $user->preferences;

        if (! $preferences) {
            $preferences = UserPreference::create([
                'user_id' => $user->id,
                'interests_categories' => $data['interests_categories'] ?? [],
                'travel_pace' => $data['travel_pace'] ?? null,
                'budget_level' => $data['budget_level'] ?? null,
                'transport_preference' => $data['transport_preference'] ?? null,
                'restrictions' => $data['restrictions'] ?? null,
            ]);
        } else {
            $preferences->update($data);
        }

        return $preferences->fresh();
    }
}
