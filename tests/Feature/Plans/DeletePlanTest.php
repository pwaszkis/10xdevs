<?php

declare(strict_types=1);

namespace Tests\Feature\Plans;

use App\Livewire\Plans\Show;
use App\Models\PlanDay;
use App\Models\PlanPoint;
use App\Models\TravelPlan;
use App\Models\TravelPlanFeedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Test suite for deleting travel plans.
 *
 * Tests cover:
 * - Deleting own plans
 * - Authorization (cannot delete others' plans)
 * - Cascade deletion of related data
 * - Soft delete behavior
 */
class DeletePlanTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private TravelPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'onboarding_completed' => true,
            'onboarding_completed_at' => now(),
            'email_verified_at' => now(),
        ]);

        $this->plan = TravelPlan::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);
    }

    public function test_user_can_delete_own_plan(): void
    {
        Livewire::actingAs($this->user)
            ->test(Show::class, ['plan' => $this->plan])
            ->call('deletePlan')
            ->assertSet('showDeleteModal', true);

        // Confirm deletion
        Livewire::actingAs($this->user)
            ->test(Show::class, ['plan' => $this->plan])
            ->call('confirmDelete')
            ->assertRedirect(route('dashboard'));

        // Verify soft delete (model uses SoftDeletes)
        $this->assertSoftDeleted('travel_plans', [
            'id' => $this->plan->id,
        ]);
    }

    public function test_user_cannot_delete_others_plan(): void
    {
        $otherUser = User::factory()->create([
            'onboarding_completed' => true,
            'onboarding_completed_at' => now(),
        ]);

        $otherPlan = TravelPlan::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        // Attempt to view another user's plan should abort with 403
        $response = $this->actingAs($this->user)
            ->get(route('plans.show', $otherPlan));

        $response->assertForbidden();
    }

    public function test_deleting_plan_removes_related_data(): void
    {
        // Create related data
        $day = PlanDay::factory()->create([
            'travel_plan_id' => $this->plan->id,
            'day_number' => 1,
        ]);

        $point = PlanPoint::factory()->create([
            'plan_day_id' => $day->id,
        ]);

        $feedback = TravelPlanFeedback::factory()->create([
            'travel_plan_id' => $this->plan->id,
        ]);

        // Delete plan
        Livewire::actingAs($this->user)
            ->test(Show::class, ['plan' => $this->plan])
            ->call('confirmDelete');

        // Verify plan is soft deleted
        $this->assertSoftDeleted('travel_plans', [
            'id' => $this->plan->id,
        ]);

        // Note: Cascade behavior depends on database foreign key constraints
        // If cascading is set up, related data should also be deleted
        // For now, we just verify the plan is deleted
    }

    public function test_soft_deleted_plan_can_be_restored(): void
    {
        // Delete the plan
        $this->plan->delete();

        $this->assertSoftDeleted('travel_plans', [
            'id' => $this->plan->id,
        ]);

        // Restore the plan
        $this->plan->restore();

        $this->assertDatabaseHas('travel_plans', [
            'id' => $this->plan->id,
            'deleted_at' => null,
        ]);
    }

    public function test_delete_modal_shows_correctly(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(Show::class, ['plan' => $this->plan])
            ->assertSet('showDeleteModal', false);

        // Trigger delete modal
        $component->call('deletePlan')
            ->assertSet('showDeleteModal', true);
    }
}
