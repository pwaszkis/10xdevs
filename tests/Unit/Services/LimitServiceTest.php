<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\AIGeneration;
use App\Models\User;
use App\Services\LimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LimitServiceTest extends TestCase
{
    use RefreshDatabase;

    private LimitService $limitService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->limitService = app(LimitService::class);
    }

    /** @test */
    public function it_returns_correct_generation_count_for_current_month(): void
    {
        $user = User::factory()->create();

        // Create 3 generations in current month
        AIGeneration::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'created_at' => now(),
        ]);

        // Create 2 generations in previous month (should not count)
        AIGeneration::factory()->count(2)->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'created_at' => now()->subMonth(),
        ]);

        $count = $this->limitService->getGenerationCount($user->id);

        $this->assertEquals(3, $count);
    }

    /** @test */
    public function it_calculates_remaining_generations_correctly(): void
    {
        $user = User::factory()->create();

        AIGeneration::factory()->count(4)->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'created_at' => now(),
        ]);

        $remaining = $this->limitService->getRemainingGenerations($user->id);

        $this->assertEquals(6, $remaining); // 10 - 4 = 6
    }

    /** @test */
    public function it_returns_zero_remaining_when_limit_exceeded(): void
    {
        $user = User::factory()->create();

        AIGeneration::factory()->count(12)->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'created_at' => now(),
        ]);

        $remaining = $this->limitService->getRemainingGenerations($user->id);

        $this->assertEquals(0, $remaining);
    }

    /** @test */
    public function it_checks_if_user_can_generate_when_under_limit(): void
    {
        $user = User::factory()->create();

        AIGeneration::factory()->count(5)->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'created_at' => now(),
        ]);

        $canGenerate = $this->limitService->canGenerate($user->id);

        $this->assertTrue($canGenerate);
    }

    /** @test */
    public function it_checks_if_user_cannot_generate_when_at_limit(): void
    {
        $user = User::factory()->create();

        AIGeneration::factory()->count(10)->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'created_at' => now(),
        ]);

        $canGenerate = $this->limitService->canGenerate($user->id);

        $this->assertFalse($canGenerate);
    }

    /** @test */
    public function it_increments_generation_count(): void
    {
        $user = User::factory()->create();

        AIGeneration::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'created_at' => now(),
        ]);

        $initialCount = $this->limitService->getGenerationCount($user->id);
        $this->assertEquals(3, $initialCount);

        // Create a new generation to simulate increment
        AIGeneration::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'created_at' => now(),
        ]);

        $newCount = $this->limitService->getGenerationCount($user->id);
        $this->assertEquals(4, $newCount);
    }

    /** @test */
    public function it_rolls_back_generation_on_failure(): void
    {
        $user = User::factory()->create();

        // Create a pending generation (the actual implementation looks for 'pending' status)
        $generation = AIGeneration::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'created_at' => now(),
        ]);

        // Rollback deletes the pending generation
        $this->limitService->rollbackGeneration($user->id);

        // Generation should be deleted
        $this->assertDatabaseMissing('ai_generations', ['id' => $generation->id]);
    }

    /** @test */
    public function it_returns_correct_limit_info(): void
    {
        $user = User::factory()->create();

        AIGeneration::factory()->count(7)->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'created_at' => now(),
        ]);

        $limitInfo = $this->limitService->getLimitInfo($user->id);

        $this->assertEquals(7, $limitInfo['used']);
        $this->assertEquals(10, $limitInfo['limit']);
        $this->assertEquals(3, $limitInfo['remaining']);
        $this->assertEquals(70.0, $limitInfo['percentage']);
        $this->assertIsString($limitInfo['reset_date']); // Returns formatted string 'Y-m-d'
        $this->assertEquals('7/10 w tym miesiącu', $limitInfo['display_text']);
        $this->assertEquals('yellow', $limitInfo['color']); // 70% is yellow
    }

    /** @test */
    public function it_calculates_correct_percentage(): void
    {
        $user = User::factory()->create();

        AIGeneration::factory()->count(5)->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'created_at' => now(),
        ]);

        $limitInfo = $this->limitService->getLimitInfo($user->id);

        $this->assertEquals(50, $limitInfo['percentage']);
    }

    /** @test */
    public function it_counts_all_generations_in_current_month(): void
    {
        $user = User::factory()->create();

        // The actual implementation counts ALL generations in the current month
        // regardless of status (using AIGeneration::forUser()->thisMonth()->count())
        AIGeneration::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'created_at' => now(),
        ]);

        AIGeneration::factory()->count(2)->create([
            'user_id' => $user->id,
            'status' => 'failed',
            'created_at' => now(),
        ]);

        AIGeneration::factory()->count(1)->create([
            'user_id' => $user->id,
            'status' => 'processing',
            'created_at' => now(),
        ]);

        $count = $this->limitService->getGenerationCount($user->id);

        $this->assertEquals(6, $count); // All generations count
    }

    /** @test */
    public function reset_date_is_first_day_of_next_month(): void
    {
        $user = User::factory()->create();

        $resetDate = $this->limitService->getResetDate();

        $expectedDate = now()->addMonth()->startOfMonth();

        $this->assertEquals($expectedDate->day, $resetDate->day);
        $this->assertEquals($expectedDate->month, $resetDate->month);
        $this->assertEquals($expectedDate->year, $resetDate->year);
    }

    /** @test */
    public function it_handles_user_with_no_generations(): void
    {
        $user = User::factory()->create();

        $count = $this->limitService->getGenerationCount($user->id);
        $remaining = $this->limitService->getRemainingGenerations($user->id);
        $canGenerate = $this->limitService->canGenerate($user->id);

        $this->assertEquals(0, $count);
        $this->assertEquals(10, $remaining);
        $this->assertTrue($canGenerate);
    }

    /** @test */
    public function it_uses_correct_monthly_limit_constant(): void
    {
        $user = User::factory()->create();

        $limitInfo = $this->limitService->getLimitInfo($user->id);

        $this->assertEquals(10, $limitInfo['limit']);
    }
}
