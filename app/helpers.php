<?php

/**
 * Development Helper Functions
 *
 * Convenience functions for testing and debugging in Tinker.
 */
if (! function_exists('create_test_plan')) {
    /**
     * Quick create a test travel plan.
     */
    function create_test_plan(?int $userId = null): \App\Models\TravelPlan
    {
        $userId = $userId ?? \App\Models\User::first()?->id;

        if (! $userId) {
            throw new \Exception('No users found. Run: php artisan dev:create-test-user');
        }

        $service = app(\App\Services\TravelService::class);

        return $service->create([
            'user_id' => $userId,
            'title' => 'Test Plan - '.now()->format('Y-m-d H:i'),
            'destination' => fake()->randomElement(['Paryż', 'Rzym', 'Barcelona', 'Lizbona', 'Berlin']),
            'departure_date' => now()->addDays(rand(7, 30))->format('Y-m-d'),
            'number_of_days' => rand(3, 10),
            'number_of_people' => rand(1, 4),
            'budget_per_person' => rand(500, 3000),
            'budget_currency' => fake()->randomElement(['PLN', 'EUR', 'USD']),
            'user_notes' => 'Auto-generated test plan',
            'status' => 'draft',
        ]);
    }
}

if (! function_exists('check_limits')) {
    /**
     * Check generation limits for user.
     *
     * @return array<string, mixed>
     */
    function check_limits(?int $userId = null): array
    {
        $userId = $userId ?? auth()->id() ?? \App\Models\User::first()?->id;

        if (! $userId) {
            throw new \Exception('No users found');
        }

        $service = app(\App\Services\LimitService::class);

        return $service->getLimitInfo($userId);
    }
}

if (! function_exists('test_ai_generation')) {
    /**
     * Test AI generation for a plan.
     *
     * @return array<string, mixed>
     */
    function test_ai_generation(int $planId): array
    {
        $plan = \App\Models\TravelPlan::findOrFail($planId);

        $aiService = app(\App\Services\AIGenerationService::class);
        $prefService = app(\App\Services\PreferenceService::class);

        $prefs = $prefService->getUserPreferences($plan->user_id);

        return $aiService->generatePlan($plan, $prefs);
    }
}

if (! function_exists('dispatch_generation')) {
    /**
     * Dispatch generation job for a plan.
     */
    function dispatch_generation(int $planId): void
    {
        $plan = \App\Models\TravelPlan::findOrFail($planId);

        $limitService = app(\App\Services\LimitService::class);
        $prefService = app(\App\Services\PreferenceService::class);

        $aiGen = $limitService->incrementGenerationCount($plan->user_id, $plan->id);
        $prefs = $prefService->getUserPreferences($plan->user_id);

        \App\Jobs\GenerateTravelPlanJob::dispatch(
            travelPlanId: $plan->id,
            userId: $plan->user_id,
            aiGenerationId: $aiGen->id,
            userPreferences: $prefs
        )->onQueue('ai-generation');

        echo "✅ Job dispatched for plan #{$plan->id}\n";
        echo "👀 Run: php artisan queue:work --queue=ai-generation\n";
    }
}

if (! function_exists('dev_login')) {
    /**
     * Quick login as first user (for dev routes).
     */
    function dev_login(): \App\Models\User
    {
        $user = \App\Models\User::first();

        if (! $user) {
            throw new \Exception('No users found. Run: php artisan dev:create-test-user');
        }

        auth()->login($user);

        echo "✅ Logged in as: {$user->nickname} ({$user->email})\n";

        return $user;
    }
}
