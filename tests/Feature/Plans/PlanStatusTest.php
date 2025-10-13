<?php

declare(strict_types=1);

namespace Tests\Feature\Plans;

use App\Models\PlanDay;
use App\Models\TravelPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite for travel plan status flow.
 *
 * Tests cover:
 * - Default status (draft)
 * - Status transitions (draft -> planned -> completed)
 * - Status helper methods
 * - Timestamps tracking
 */
class PlanStatusTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'onboarding_completed' => true,
            'onboarding_completed_at' => now(),
            'email_verified_at' => now(),
        ]);
    }

    public function test_new_plan_is_draft(): void
    {
        $plan = TravelPlan::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->assertEquals('draft', $plan->status);
        $this->assertTrue($plan->isDraft());
        $this->assertFalse($plan->isPlanned());
        $this->assertFalse($plan->isCompleted());
    }

    public function test_can_change_draft_to_planned(): void
    {
        $plan = TravelPlan::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);

        $this->assertTrue($plan->isDraft());

        // Change status to planned
        $plan->update(['status' => 'planned']);
        $plan->refresh();

        $this->assertEquals('planned', $plan->status);
        $this->assertTrue($plan->isPlanned());
        $this->assertFalse($plan->isDraft());
    }

    public function test_can_change_planned_to_completed(): void
    {
        $plan = TravelPlan::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'planned',
        ]);

        $this->assertTrue($plan->isPlanned());

        // Change status to completed
        $plan->update(['status' => 'completed']);
        $plan->refresh();

        $this->assertEquals('completed', $plan->status);
        $this->assertTrue($plan->isCompleted());
        $this->assertFalse($plan->isPlanned());
    }

    public function test_status_scopes_work_correctly(): void
    {
        // Create plans with different statuses
        $draftPlan = TravelPlan::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);

        $plannedPlan = TravelPlan::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'planned',
        ]);

        $completedPlan = TravelPlan::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'completed',
        ]);

        // Test drafts scope
        $drafts = TravelPlan::drafts()->get();
        $this->assertCount(1, $drafts);
        $this->assertEquals($draftPlan->id, $drafts->first()->id);

        // Test planned scope
        $planned = TravelPlan::planned()->get();
        $this->assertCount(1, $planned);
        $this->assertEquals($plannedPlan->id, $planned->first()->id);

        // Test completed scope
        $completed = TravelPlan::completed()->get();
        $this->assertCount(1, $completed);
        $this->assertEquals($completedPlan->id, $completed->first()->id);

        // Test status scope
        $draftsByStatus = TravelPlan::status('draft')->get();
        $this->assertCount(1, $draftsByStatus);
        $this->assertEquals($draftPlan->id, $draftsByStatus->first()->id);
    }

    public function test_status_changes_tracked_in_timestamps(): void
    {
        $plan = TravelPlan::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);

        $createdAt = $plan->created_at;
        $updatedAt = $plan->updated_at;

        // Wait a moment to ensure timestamp changes
        $this->travel(1)->second();

        // Update status
        $plan->update(['status' => 'planned']);
        $plan->refresh();

        // Verify timestamps
        $this->assertEquals($createdAt->timestamp, $plan->created_at->timestamp);
        $this->assertGreaterThan($updatedAt->timestamp, $plan->updated_at->timestamp);
    }

    public function test_has_ai_plan_attribute_based_on_days(): void
    {
        // Plan without days (no AI content)
        $planWithoutDays = TravelPlan::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);

        $this->assertFalse($planWithoutDays->has_ai_plan);

        // Plan with days (has AI content)
        $planWithDays = TravelPlan::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'planned',
        ]);

        PlanDay::factory()->create([
            'travel_plan_id' => $planWithDays->id,
            'day_number' => 1,
        ]);

        // Refresh to load relationship
        $planWithDays->refresh();

        $this->assertTrue($planWithDays->has_ai_plan);
    }
}
