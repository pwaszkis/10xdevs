<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/**
 * Create Test User Command
 *
 * Creates a test user with preferences for development testing.
 */
class CreateTestUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dev:create-test-user
                            {--email=test@example.com : The email address}
                            {--nickname=Test User : The user nickname}
                            {--password=password : The password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a test user with preferences for development testing';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->option('email');
        $nickname = $this->option('nickname');
        $password = $this->option('password');

        // Check if user already exists
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            $this->error("User with email {$email} already exists!");
            $this->info("User ID: {$existingUser->id}");
            $this->info("Nickname: {$existingUser->nickname}");

            return Command::FAILURE;
        }

        // Create user
        $user = User::create([
            'nickname' => $nickname,
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
            'onboarding_completed_at' => now(),
            'onboarding_step' => 4,
        ]);

        // Create default preferences
        UserPreference::create([
            'user_id' => $user->id,
            'language' => 'pl',
            'timezone' => 'Europe/Warsaw',
            'currency' => 'PLN',
            'notifications_enabled' => true,
            'email_notifications' => true,
            'push_notifications' => false,
            'theme' => 'light',
            'interests_categories' => ['history_culture', 'gastronomy', 'nature_outdoor'],
            'travel_pace' => 'moderate',
            'budget_level' => 'standard',
            'transport_preference' => 'mixed',
            'restrictions' => null,
        ]);

        $this->info('✅ Test user created successfully!');
        $this->newLine();
        $this->table(
            ['Field', 'Value'],
            [
                ['ID', $user->id],
                ['Nickname', $user->nickname],
                ['Email', $user->email],
                ['Password', $password],
                ['Onboarding', 'Completed'],
                ['Preferences', 'Created with default values'],
            ]
        );

        $this->newLine();
        $this->info('🔗 You can now test at:');
        $this->line('   - Create Plan: ' . url('/dev/plans/create'));
        $this->line('   - Login: Email=' . $email . ', Password=' . $password);

        return Command::SUCCESS;
    }
}
