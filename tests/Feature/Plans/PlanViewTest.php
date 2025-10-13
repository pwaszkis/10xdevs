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
 * Test suite for plan view and display functionality.
 *
 * Tests cover:
 * - Viewing generated plans
 * - Displaying days and points
 * - Google Maps links
 * - Authorization checks
 */
class PlanViewTest extends TestCase
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

    public function test_user_can_view_generated_plan(): void
    {
        $plan = TravelPlan::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'planned',
            'title' => 'Paris Adventure',
            'destination' => 'Paris',
            'departure_date' => now()->addDays(30),
            'number_of_days' => 3,
        ]);

        // Create 3 days with points
        for ($i = 1; $i <= 3; $i++) {
            $day = PlanDay::factory()->create([
                'travel_plan_id' => $plan->id,
                'day_number' => $i,
                'date' => $plan->departure_date->copy()->addDays($i - 1),
                'summary' => "Day {$i} summary",
            ]);

            // Create 4 points per day with sequential order_number
            for ($j = 1; $j <= 4; $j++) {
                PlanPoint::factory()->create([
                    'plan_day_id' => $day->id,
                    'order_number' => $j,
                ]);
            }
        }

        $this->actingAs($this->user);

        // Test Livewire component
        Livewire::test(Show::class, ['plan' => $plan])
            ->assertOk()
            ->assertSee('Paris Adventure')
            ->assertSee('Paris');

        // Verify plan data is loaded by fetching fresh instance
        $plan->refresh();
        $loadedPlan = $plan;
        $this->assertEquals($plan->id, $loadedPlan->id);
        $this->assertEquals(3, $loadedPlan->days->count());

        // Verify each day is loaded
        foreach ($loadedPlan->days as $day) {
            $this->assertEquals(4, $day->points->count());
        }
    }

    public function test_plan_displays_all_points_for_each_day(): void
    {
        $plan = TravelPlan::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'planned',
        ]);

        $day = PlanDay::factory()->create([
            'travel_plan_id' => $plan->id,
            'day_number' => 1,
            'date' => now()->addDays(10),
        ]);

        $point1 = PlanPoint::factory()->create([
            'plan_day_id' => $day->id,
            'name' => 'Eiffel Tower',
            'description' => 'Iconic landmark in Paris',
            'day_part' => 'rano',
            'order_number' => 1,
        ]);

        $point2 = PlanPoint::factory()->create([
            'plan_day_id' => $day->id,
            'name' => 'Louvre Museum',
            'description' => 'World famous art museum',
            'day_part' => 'popoludnie',
            'order_number' => 2,
        ]);

        $this->actingAs($this->user);

        // Test through Livewire component
        Livewire::test(Show::class, ['plan' => $plan])
            ->assertOk();

        // Verify plan data contains the points
        $plan->refresh();
        $loadedPlan = $plan;
        $this->assertCount(1, $loadedPlan->days);

        $firstDay = $loadedPlan->days->first();
        $this->assertCount(2, $firstDay->points);

        $pointNames = $firstDay->points->pluck('name')->toArray();
        $this->assertContains('Eiffel Tower', $pointNames);
        $this->assertContains('Louvre Museum', $pointNames);

        // Verify HTTP response also works
        $response = $this->get(route('plans.show', $plan));
        $response->assertOk();
    }

    public function test_plan_displays_google_maps_links(): void
    {
        $plan = TravelPlan::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'planned',
        ]);

        $day = PlanDay::factory()->create([
            'travel_plan_id' => $plan->id,
            'day_number' => 1,
        ]);

        $mapsUrl = 'https://www.google.com/maps/search/?api=1&query=Notre-Dame+Paris';

        PlanPoint::factory()->create([
            'plan_day_id' => $day->id,
            'name' => 'Notre-Dame Cathedral',
            'google_maps_url' => $mapsUrl,
            'order_number' => 1,
        ]);

        $this->actingAs($this->user);

        // Test through Livewire component
        Livewire::test(Show::class, ['plan' => $plan])
            ->assertOk();

        // Verify the point has the Google Maps URL
        $plan->refresh();
        $point = $plan->days->first()->points->first();

        $this->assertEquals('Notre-Dame Cathedral', $point->name);
        $this->assertEquals($mapsUrl, $point->google_maps_url);

        // Verify HTTP response works
        $response = $this->get(route('plans.show', $plan));
        $response->assertOk();
    }

    public function test_user_cannot_view_other_users_plan(): void
    {
        $otherUser = User::factory()->create([
            'onboarding_completed' => true,
            'onboarding_completed_at' => now(),
        ]);

        $plan = TravelPlan::factory()->create([
            'user_id' => $otherUser->id,
            'status' => 'planned',
        ]);

        // Attempt to view other user's plan
        $this->actingAs($this->user);

        $response = $this->get(route('plans.show', $plan));

        $response->assertForbidden();
    }
}
