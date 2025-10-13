<?php

declare(strict_types=1);

namespace Tests\Feature\Plans;

use App\Livewire\Plans\Show;
use App\Models\PlanDay;
use App\Models\PlanPoint;
use App\Models\TravelPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Test suite for viewing travel plans.
 *
 * Tests cover:
 * - Viewing own plans
 * - Authorization (cannot view others' plans)
 * - Displaying plan details, days, and points
 * - Status badges
 */
class ShowPlanTest extends TestCase
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
            'title' => 'Test Plan',
            'destination' => 'Test Destination',
            'status' => 'draft',
        ]);
    }

    public function test_user_can_view_own_plan(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(Show::class, ['plan' => $this->plan])
            ->assertOk();

        // Verify plan is loaded
        $this->assertEquals($this->plan->id, $component->get('plan')->id);
        $this->assertEquals($this->plan->title, $component->get('plan')->title);
    }

    public function test_user_cannot_view_others_plan(): void
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

    public function test_plan_displays_all_details(): void
    {
        $plan = TravelPlan::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Detailed Plan',
            'destination' => 'Paris',
            'departure_date' => now()->addDays(30),
            'number_of_days' => 7,
            'number_of_people' => 2,
            'budget_per_person' => 1500.50,
            'budget_currency' => 'EUR',
            'user_notes' => 'Want to see Eiffel Tower',
            'status' => 'planned',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(Show::class, ['plan' => $plan]);

        $loadedPlan = $component->get('plan');

        $this->assertEquals('Detailed Plan', $loadedPlan->title);
        $this->assertEquals('Paris', $loadedPlan->destination);
        $this->assertEquals(7, $loadedPlan->number_of_days);
        $this->assertEquals(2, $loadedPlan->number_of_people);
        $this->assertEquals(1500.50, $loadedPlan->budget_per_person);
        $this->assertEquals('EUR', $loadedPlan->budget_currency);
        $this->assertEquals('Want to see Eiffel Tower', $loadedPlan->user_notes);
        $this->assertEquals('planned', $loadedPlan->status);
    }

    public function test_plan_displays_days_and_points(): void
    {
        $plan = TravelPlan::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'planned',
        ]);

        // Create days with points
        $day1 = PlanDay::factory()->create([
            'travel_plan_id' => $plan->id,
            'day_number' => 1,
            'summary' => 'Day 1 - Arrival',
        ]);

        $point1 = PlanPoint::factory()->create([
            'plan_day_id' => $day1->id,
            'name' => 'Eiffel Tower',
            'description' => 'Iconic landmark',
            'day_part' => 'rano',
        ]);

        $point2 = PlanPoint::factory()->create([
            'plan_day_id' => $day1->id,
            'name' => 'Louvre Museum',
            'description' => 'Art museum',
            'day_part' => 'popoludnie',
        ]);

        $day2 = PlanDay::factory()->create([
            'travel_plan_id' => $plan->id,
            'day_number' => 2,
            'summary' => 'Day 2 - Exploration',
        ]);

        PlanPoint::factory()->create([
            'plan_day_id' => $day2->id,
            'name' => 'Notre-Dame',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(Show::class, ['plan' => $plan]);

        $loadedPlan = $component->get('plan');

        // Verify days are loaded
        $this->assertCount(2, $loadedPlan->days);

        // Verify first day has 2 points
        $this->assertCount(2, $loadedPlan->days[0]->points);
        $pointNames = $loadedPlan->days[0]->points->pluck('name')->toArray();
        $this->assertContains('Eiffel Tower', $pointNames);
        $this->assertContains('Louvre Museum', $pointNames);

        // Verify second day has 1 point
        $this->assertCount(1, $loadedPlan->days[1]->points);
        $this->assertEquals('Notre-Dame', $loadedPlan->days[1]->points[0]->name);
    }

    public function test_plan_shows_correct_status_badge(): void
    {
        // Test draft status
        $draftPlan = TravelPlan::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(Show::class, ['plan' => $draftPlan]);

        $this->assertTrue($component->get('plan')->isDraft());

        // Test planned status
        $plannedPlan = TravelPlan::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'planned',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(Show::class, ['plan' => $plannedPlan]);

        $this->assertTrue($component->get('plan')->isPlanned());

        // Test completed status
        $completedPlan = TravelPlan::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'completed',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(Show::class, ['plan' => $completedPlan]);

        $this->assertTrue($component->get('plan')->isCompleted());
    }

    public function test_guest_cannot_access_plan(): void
    {
        $response = $this->get(route('plans.show', $this->plan));

        $response->assertRedirect(route('login'));
    }
}
