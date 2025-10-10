<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Register User Action
 *
 * Handles user registration logic including creating user and default preferences.
 */
class RegisterUserAction
{
    /**
     * Execute the action.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            // Create default user preferences
            UserPreference::create([
                'user_id' => $user->id,
                'language' => 'en',
                'timezone' => config('app.timezone'),
                'currency' => 'USD',
                'notifications_enabled' => true,
                'email_notifications' => true,
                'push_notifications' => false,
                'theme' => 'auto',
            ]);

            return $user->fresh(['preferences']);
        });
    }
}
