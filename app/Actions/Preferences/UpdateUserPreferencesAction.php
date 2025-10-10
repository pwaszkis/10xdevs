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
                'language' => $data['language'] ?? 'en',
                'timezone' => $data['timezone'] ?? config('app.timezone'),
                'currency' => $data['currency'] ?? 'USD',
                'notifications_enabled' => $data['notifications_enabled'] ?? true,
                'email_notifications' => $data['email_notifications'] ?? true,
                'push_notifications' => $data['push_notifications'] ?? false,
                'theme' => $data['theme'] ?? 'auto',
            ]);
        } else {
            $preferences->update($data);
        }

        return $preferences->fresh();
    }
}
