<?php

declare(strict_types=1);

namespace Tests\Feature\AI;

use App\Exceptions\LimitExceededException;
use App\Models\AIGeneration;
use App\Models\TravelPlan;
use App\Models\User;
use App\Services\LimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite for AI generation limits.
 *
 * Tests cover:
 * - Monthly limit (10 generations)
 * - Limit decrementation
 * - Limit reset
 * - Failed generation handling
 * - Regeneration limit consumption
 */
class GenerationLimitsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private LimitService $limitService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'onboarding_completed' => true,
            'onboarding_completed_at' => now(),
            'email_verified_at' => now(),
        ]);

        $this->limitService = app(LimitService::class);
    }

    public function test_user_has_10_generations_per_month(): void
    {
        $limit = $this->limitService->getMonthlyLimit($this->user->id);
        $remaining = $this->limitService->getRemainingGenerations($this->user->id);

        $this->assertEquals(10, LimitService::MONTHLY_LIMIT);
        $this->assertEquals(10, $limit);
        $this->assertEquals(10, $remaining);
        $this->assertTrue($this->limitService->canGenerate($this->user->id));
    }

    public function test_generation_decrements_remaining_limit(): void
    {
        // Create 1 generation
        AIGeneration::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now(),
        ]);

        $remaining = $this->limitService->getRemainingGenerations($this->user->id);
        $used = $this->limitService->getGenerationCount($this->user->id);

        $this->assertEquals(9, $remaining);
        $this->assertEquals(1, $used);

        // Create 3 more generations
        AIGeneration::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'created_at' => now(),
        ]);

        $remaining = $this->limitService->getRemainingGenerations($this->user->id);
        $used = $this->limitService->getGenerationCount($this->user->id);

        $this->assertEquals(6, $remaining);
        $this->assertEquals(4, $used);
    }

    public function test_limit_resets_on_first_day_of_month(): void
    {
        // Create 10 generations in previous month
        AIGeneration::factory()->count(10)->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subMonth(),
        ]);

        // Verify previous month's generations don't count
        $currentMonthUsed = $this->limitService->getGenerationCount($this->user->id);
        $remaining = $this->limitService->getRemainingGenerations($this->user->id);

        $this->assertEquals(0, $currentMonthUsed);
        $this->assertEquals(10, $remaining);
        $this->assertTrue($this->limitService->canGenerate($this->user->id));

        // Create 1 generation in current month
        AIGeneration::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now(),
        ]);

        $currentMonthUsed = $this->limitService->getGenerationCount($this->user->id);
        $this->assertEquals(1, $currentMonthUsed);
    }

    public function test_failed_generation_does_not_consume_limit(): void
    {
        $plan = TravelPlan::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);

        $initialRemaining = $this->limitService->getRemainingGenerations($this->user->id);

        // Create a pending generation through the service
        $aiGeneration = $this->limitService->incrementGenerationCount(
            $this->user->id,
            $plan->id
        );

        // Verify generation was counted
        $this->assertEquals($initialRemaining - 1, $this->limitService->getRemainingGenerations($this->user->id));

        // Rollback the failed generation (simulating job failure)
        $this->limitService->rollbackGeneration($this->user->id);

        $finalRemaining = $this->limitService->getRemainingGenerations($this->user->id);

        // Limit should be back to initial value
        $this->assertEquals($initialRemaining, $finalRemaining);

        // Verify the generation was deleted
        $this->assertDatabaseMissing('ai_generations', [
            'id' => $aiGeneration->id,
        ]);
    }

    public function test_regeneration_consumes_additional_limit(): void
    {
        // Create plan with existing generation
        $plan = TravelPlan::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'planned',
        ]);

        AIGeneration::factory()->create([
            'user_id' => $this->user->id,
            'travel_plan_id' => $plan->id,
            'status' => 'completed',
        ]);

        $this->assertEquals(9, $this->limitService->getRemainingGenerations($this->user->id));

        // Regenerate (create new generation)
        $newGeneration = $this->limitService->incrementGenerationCount(
            $this->user->id,
            $plan->id
        );

        $this->assertEquals(8, $this->limitService->getRemainingGenerations($this->user->id));

        // Verify new generation was created
        $this->assertDatabaseHas('ai_generations', [
            'id' => $newGeneration->id,
            'user_id' => $this->user->id,
            'travel_plan_id' => $plan->id,
            'status' => 'pending',
        ]);
    }

    public function test_cannot_exceed_monthly_limit(): void
    {
        // Create 10 generations (max limit)
        AIGeneration::factory()->count(10)->create([
            'user_id' => $this->user->id,
            'created_at' => now(),
        ]);

        $this->assertEquals(10, $this->limitService->getGenerationCount($this->user->id));
        $this->assertEquals(0, $this->limitService->getRemainingGenerations($this->user->id));
        $this->assertFalse($this->limitService->canGenerate($this->user->id));
        $this->assertTrue($this->limitService->hasReachedLimit($this->user->id));

        // Attempt to create 11th generation should throw exception
        $this->expectException(LimitExceededException::class);
        $this->expectExceptionMessage('Monthly generation limit');

        $this->limitService->incrementGenerationCount($this->user->id);
    }

    public function test_get_limit_info_returns_correct_data(): void
    {
        // Create 7 generations
        AIGeneration::factory()->count(7)->create([
            'user_id' => $this->user->id,
            'created_at' => now(),
        ]);

        $info = $this->limitService->getLimitInfo($this->user->id);

        $this->assertEquals(7, $info['used']);
        $this->assertEquals(10, $info['limit']);
        $this->assertEquals(3, $info['remaining']);
        $this->assertEquals(70.0, $info['percentage']);
        $this->assertTrue($info['can_generate']);
        $this->assertEquals('7/10 w tym miesiącu', $info['display_text']);
        $this->assertEquals('yellow', $info['color']); // 70% is yellow

        // Create 2 more (total 9, 90%)
        AIGeneration::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'created_at' => now(),
        ]);

        $info = $this->limitService->getLimitInfo($this->user->id);
        $this->assertEquals('red', $info['color']); // 90% is red
    }

    public function test_reset_date_is_first_day_of_next_month(): void
    {
        $resetDate = $this->limitService->getResetDate();
        $expectedDate = now()->addMonth()->startOfMonth();

        $this->assertEquals(
            $expectedDate->format('Y-m-d'),
            $resetDate->format('Y-m-d')
        );
    }
}
