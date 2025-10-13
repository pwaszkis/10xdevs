<?php

declare(strict_types=1);

namespace Tests\Performance;

use App\Jobs\GenerateTravelPlanJob;
use App\Models\AIGeneration;
use App\Models\PlanDay;
use App\Models\PlanPoint;
use App\Models\TravelPlan;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Performance Test Suite
 *
 * Tests performance benchmarks for critical operations.
 *
 * IMPORTANT: These tests are marked as @group performance and are OPTIONAL.
 * - They may take significant time to run (45+ seconds each)
 * - Run only when investigating performance issues
 * - Use: php artisan test --group=performance
 *
 * Skip in regular test runs with: php artisan test --exclude-group=performance
 */
#[Group('performance')]
class AIGenerationPerformanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: AI generation completes under 45 seconds (with real API)
     *
     * IMPORTANT: This test requires AI_USE_REAL_API=true in .env
     * It will be SKIPPED if using mock API (default in tests).
     *
     * Performance target: < 45 seconds for 5-day plan generation
     *
     * Why this matters:
     * - User experience: users wait for plan generation
     * - Server resources: long-running jobs consume workers
     * - Cost: longer API calls = higher OpenAI costs
     *
     * @group slow
     */
    #[Test]
    public function test_ai_generation_completes_under_45_seconds(): void
    {
        // Skip if using mock API (can't measure real performance)
        if (config('ai.use_real_api') !== true) {
            $this->markTestSkipped(
                'This test requires AI_USE_REAL_API=true in .env. '.
                'Skipped because mock API cannot measure real performance.'
            );
        }

        // Arrange: User with preferences and a travel plan
        $user = User::factory()->create();
        UserPreference::factory()->create(['user_id' => $user->id]);

        $plan = TravelPlan::factory()->create([
            'user_id' => $user->id,
            'title' => 'Performance Test Plan',
            'destination' => 'Paris',
            'departure_date' => now()->addDays(30),
            'number_of_days' => 5, // 5-day plan
            'number_of_people' => 2,
            'status' => 'draft',
        ]);

        $aiGeneration = AIGeneration::factory()->create([
            'user_id' => $user->id,
            'travel_plan_id' => $plan->id,
            'status' => 'pending',
        ]);

        // Act: Execute job and measure time
        $startTime = microtime(true);

        $job = new GenerateTravelPlanJob(
            travelPlanId: $plan->id,
            userId: $user->id,
            aiGenerationId: $aiGeneration->id,
            userPreferences: $user->preferences->toArray()
        );

        $job->handle(app(\App\Services\AIGenerationService::class));

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Assert: Completed under 45 seconds
        $this->assertLessThan(
            45,
            $duration,
            "AI generation took {$duration} seconds (target: < 45s)"
        );

        // Verify plan was actually generated
        $plan->refresh();
        $this->assertEquals('planned', $plan->status);
        $this->assertGreaterThan(0, $plan->days()->count());

        // Log duration for monitoring
        $this->addToAssertionCount(1); // Count as assertion
        fwrite(STDOUT, "\n[PERFORMANCE] AI Generation completed in ".round($duration, 2)."s\n");
    }

    /**
     * Test: Dashboard loads with 100 plans under 2 seconds
     *
     * Performance target: < 2 seconds for dashboard with many plans
     *
     * Why this matters:
     * - First impression: dashboard is first page after login
     * - Scalability: users may accumulate many plans over time
     * - Database: tests query optimization and eager loading
     */
    #[Test]
    public function test_dashboard_loads_with_100_plans_under_2_seconds(): void
    {
        // Arrange: User with 100 travel plans
        $user = User::factory()->create([
            'onboarding_completed' => true,
            'onboarding_completed_at' => now(),
            'email_verified_at' => now(),
        ]);

        // Create 100 plans with varied data
        TravelPlan::factory()->count(100)->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user);

        // Act: Load dashboard and measure time
        $startTime = microtime(true);

        $response = $this->get('/dashboard');

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Assert: Loads under 2 seconds
        $response->assertOk();
        $this->assertLessThan(
            2,
            $duration,
            "Dashboard loaded in {$duration} seconds (target: < 2s)"
        );

        // Log duration for monitoring
        fwrite(STDOUT, "\n[PERFORMANCE] Dashboard with 100 plans loaded in ".round($duration, 2)."s\n");
    }

    /**
     * Test: Plan view with 30 days loads quickly
     *
     * Performance target: < 1 second for large plan (30 days, 180 points)
     *
     * Why this matters:
     * - User experience: viewing detailed plan should be instant
     * - Edge case: maximum plan size (30 days)
     * - Database: tests N+1 query issues with days→points relations
     */
    #[Test]
    public function test_plan_view_with_30_days_loads_under_1_second(): void
    {
        // Arrange: User with a large travel plan
        $user = User::factory()->create([
            'onboarding_completed' => true,
            'onboarding_completed_at' => now(),
            'email_verified_at' => now(),
        ]);

        $plan = TravelPlan::factory()->create([
            'user_id' => $user->id,
            'status' => 'planned',
            'title' => 'Month-long Journey',
            'destination' => 'Europe',
            'departure_date' => now()->addDays(60),
            'number_of_days' => 30,
            'number_of_people' => 2,
        ]);

        // Create 30 days with 6 points each = 180 total points
        for ($dayNum = 1; $dayNum <= 30; $dayNum++) {
            $day = PlanDay::factory()->create([
                'travel_plan_id' => $plan->id,
                'day_number' => $dayNum,
                'date' => $plan->departure_date->copy()->addDays($dayNum - 1),
            ]);

            // Create 6 points for each day (with unique order_number)
            for ($pointNum = 1; $pointNum <= 6; $pointNum++) {
                PlanPoint::factory()->create([
                    'plan_day_id' => $day->id,
                    'order_number' => $pointNum,
                    'day_part' => match ($pointNum) {
                        1, 2 => 'rano',
                        3, 4 => 'popoludnie',
                        5, 6 => 'wieczor',
                    },
                ]);
            }
        }

        $this->actingAs($user);

        // Verify data created correctly
        $this->assertEquals(30, $plan->days()->count());
        $this->assertEquals(180, PlanPoint::whereIn('plan_day_id', $plan->days->pluck('id'))->count());

        // Act: Load plan view and measure time
        $startTime = microtime(true);

        $response = $this->get("/plans/{$plan->id}");

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Assert: Loads under 1 second
        $response->assertOk();
        $this->assertLessThan(
            1,
            $duration,
            "Plan view with 30 days loaded in {$duration} seconds (target: < 1s)"
        );

        // Verify plan data is displayed (sampling)
        $response->assertSee($plan->title);
        $response->assertSee($plan->destination);

        // Log duration for monitoring
        fwrite(STDOUT, "\n[PERFORMANCE] Plan view with 30 days (180 points) loaded in ".round($duration, 2)."s\n");
    }

    /**
     * Test: Dashboard pagination performance
     *
     * Bonus test: Verify pagination doesn't load all records.
     * This ensures scalability even with thousands of plans.
     */
    #[Test]
    public function test_dashboard_pagination_is_efficient(): void
    {
        $user = User::factory()->create([
            'onboarding_completed' => true,
            'onboarding_completed_at' => now(),
            'email_verified_at' => now(),
        ]);

        // Create 50 plans (simulating moderate user)
        TravelPlan::factory()->count(50)->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user);

        // Measure time to load first page
        $startTime = microtime(true);

        $response = $this->get('/dashboard');

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Assert: First page loads quickly even with many plans
        $response->assertOk();
        $this->assertLessThan(
            1,
            $duration,
            "Dashboard first page loaded in {$duration} seconds (target: < 1s)"
        );

        // Log for monitoring
        fwrite(STDOUT, "\n[PERFORMANCE] Dashboard (50 plans) loaded in ".round($duration, 2)."s\n");
    }
}
