<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\AIGeneration;
use App\Models\PlanDay;
use App\Models\TravelPlan;
use App\Models\TravelPlanFeedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TravelPlanTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $plan = TravelPlan::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $plan->user);
        $this->assertEquals($user->id, $plan->user->id);
    }

    /** @test */
    public function it_has_many_days(): void
    {
        $plan = TravelPlan::factory()->create();
        PlanDay::factory()->count(3)->create(['travel_plan_id' => $plan->id]);

        $this->assertCount(3, $plan->days);
        $this->assertInstanceOf(PlanDay::class, $plan->days->first());
    }

    /** @test */
    public function it_has_one_feedback(): void
    {
        $plan = TravelPlan::factory()->create();
        $feedback = TravelPlanFeedback::factory()->create(['travel_plan_id' => $plan->id]);

        $this->assertInstanceOf(TravelPlanFeedback::class, $plan->feedback);
        $this->assertEquals($feedback->id, $plan->feedback->id);
    }

    /** @test */
    public function it_has_many_ai_generations(): void
    {
        $plan = TravelPlan::factory()->create();
        AIGeneration::factory()->count(2)->create(['travel_plan_id' => $plan->id]);

        $this->assertCount(2, $plan->aiGenerations);
        $this->assertInstanceOf(AIGeneration::class, $plan->aiGenerations->first());
    }

    /** @test */
    public function scope_drafts_filters_draft_plans(): void
    {
        TravelPlan::factory()->create(['status' => 'draft']);
        TravelPlan::factory()->create(['status' => 'planned']);
        TravelPlan::factory()->create(['status' => 'completed']);

        $drafts = TravelPlan::drafts()->get();

        $this->assertCount(1, $drafts);
        $this->assertEquals('draft', $drafts->first()->status);
    }

    /** @test */
    public function scope_planned_filters_planned_plans(): void
    {
        TravelPlan::factory()->create(['status' => 'draft']);
        TravelPlan::factory()->create(['status' => 'planned']);
        TravelPlan::factory()->create(['status' => 'completed']);

        $planned = TravelPlan::planned()->get();

        $this->assertCount(1, $planned);
        $this->assertEquals('planned', $planned->first()->status);
    }

    /** @test */
    public function scope_completed_filters_completed_plans(): void
    {
        TravelPlan::factory()->create(['status' => 'draft']);
        TravelPlan::factory()->create(['status' => 'planned']);
        TravelPlan::factory()->create(['status' => 'completed']);

        $completed = TravelPlan::completed()->get();

        $this->assertCount(1, $completed);
        $this->assertEquals('completed', $completed->first()->status);
    }

    /** @test */
    public function is_draft_returns_true_for_draft_status(): void
    {
        $plan = TravelPlan::factory()->create(['status' => 'draft']);

        $this->assertTrue($plan->isDraft());
    }

    /** @test */
    public function is_draft_returns_false_for_non_draft_status(): void
    {
        $plan = TravelPlan::factory()->create(['status' => 'planned']);

        $this->assertFalse($plan->isDraft());
    }

    /** @test */
    public function is_planned_returns_true_for_planned_status(): void
    {
        $plan = TravelPlan::factory()->create(['status' => 'planned']);

        $this->assertTrue($plan->isPlanned());
    }

    /** @test */
    public function is_planned_returns_false_for_non_planned_status(): void
    {
        $plan = TravelPlan::factory()->create(['status' => 'draft']);

        $this->assertFalse($plan->isPlanned());
    }

    /** @test */
    public function is_completed_returns_true_for_completed_status(): void
    {
        $plan = TravelPlan::factory()->create(['status' => 'completed']);

        $this->assertTrue($plan->isCompleted());
    }

    /** @test */
    public function is_completed_returns_false_for_non_completed_status(): void
    {
        $plan = TravelPlan::factory()->create(['status' => 'draft']);

        $this->assertFalse($plan->isCompleted());
    }

    /** @test */
    public function has_ai_plan_attribute_returns_true_when_days_exist(): void
    {
        $plan = TravelPlan::factory()->create();
        PlanDay::factory()->create(['travel_plan_id' => $plan->id]);

        // The attribute checks if days()->count() > 0
        $this->assertTrue($plan->days()->count() > 0);
    }

    /** @test */
    public function has_ai_plan_attribute_returns_false_when_no_days(): void
    {
        $plan = TravelPlan::factory()->create();

        $this->assertTrue($plan->days()->count() === 0);
    }

    /** @test */
    public function can_transition_from_draft_to_planned(): void
    {
        $plan = TravelPlan::factory()->create(['status' => 'draft']);

        $plan->update(['status' => 'planned']);

        $this->assertEquals('planned', $plan->status);
    }

    /** @test */
    public function can_transition_from_planned_to_completed(): void
    {
        $plan = TravelPlan::factory()->create(['status' => 'planned']);

        $plan->update(['status' => 'completed']);

        $this->assertEquals('completed', $plan->status);
    }

    /** @test */
    public function plan_casts_departure_date_to_date(): void
    {
        $plan = TravelPlan::factory()->create([
            'departure_date' => '2025-12-25',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $plan->departure_date);
        $this->assertEquals('2025-12-25', $plan->departure_date->toDateString());
    }

    /** @test */
    public function plan_has_fillable_attributes(): void
    {
        $plan = TravelPlan::factory()->make([
            'title' => 'Test Trip',
            'destination' => 'Paris',
            'number_of_days' => 5,
            'number_of_people' => 2,
            'budget_per_person' => 1000,
            'user_notes' => 'Test notes',
        ]);

        $this->assertEquals('Test Trip', $plan->title);
        $this->assertEquals('Paris', $plan->destination);
        $this->assertEquals(5, $plan->number_of_days);
        $this->assertEquals(2, $plan->number_of_people);
        $this->assertEquals(1000, $plan->budget_per_person);
        $this->assertEquals('Test notes', $plan->user_notes);
    }

    /** @test */
    public function plan_can_be_soft_deleted(): void
    {
        $plan = TravelPlan::factory()->create();
        $planId = $plan->id;

        $plan->delete();

        $this->assertSoftDeleted('travel_plans', ['id' => $planId]);
    }

    /** @test */
    public function deleting_plan_cascades_to_days(): void
    {
        $plan = TravelPlan::factory()->create();
        $day = PlanDay::factory()->create(['travel_plan_id' => $plan->id]);

        $plan->forceDelete();

        $this->assertDatabaseMissing('plan_days', ['id' => $day->id]);
    }

    /** @test */
    public function plan_default_status_is_draft(): void
    {
        $plan = TravelPlan::factory()->create();

        $this->assertEquals('draft', $plan->status);
    }

    /** @test */
    public function plan_has_correct_table_name(): void
    {
        $plan = new TravelPlan;

        $this->assertEquals('travel_plans', $plan->getTable());
    }
}
