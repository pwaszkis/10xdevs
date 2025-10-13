<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Livewire\Dashboard;
use App\Models\TravelPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Dashboard Plan Filtering Tests
 *
 * Tests filtering and search functionality in dashboard:
 * - Filter by status (all, draft, planned, completed)
 * - Search by title/destination
 * - Clear filters
 */
class PlanFilteringTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Filter plans by status - all
     *
     * Scenario: User has plans with different statuses.
     * Filter "all" should show all plans.
     */
    public function test_filter_plans_by_status_all(): void
    {
        // Arrange: User with plans in all statuses
        $user = User::factory()->create([
            'onboarding_completed' => true,
            'email_verified_at' => now(),
        ]);

        TravelPlan::factory()->create(['user_id' => $user->id, 'status' => 'draft']);
        TravelPlan::factory()->create(['user_id' => $user->id, 'status' => 'planned']);
        TravelPlan::factory()->create(['user_id' => $user->id, 'status' => 'completed']);

        // Act: Load dashboard with "all" filter
        $component = Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->set('statusFilter', 'all');

        // Assert: All 3 plans are shown
        $this->assertCount(3, $component->get('plans'));
    }

    /**
     * Test: Filter plans by status - drafts only
     *
     * Scenario: User wants to see only draft plans.
     * Only draft plans should be visible.
     */
    public function test_filter_plans_by_status_drafts(): void
    {
        // Arrange: User with 2 drafts and 1 planned
        $user = User::factory()->create([
            'onboarding_completed' => true,
            'email_verified_at' => now(),
        ]);

        TravelPlan::factory()->count(2)->create([
            'user_id' => $user->id,
            'status' => 'draft',
            'title' => 'Draft Plan',
        ]);
        TravelPlan::factory()->create([
            'user_id' => $user->id,
            'status' => 'planned',
            'title' => 'Planned Trip',
        ]);

        // Act: Filter by draft
        $component = Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->set('statusFilter', 'draft');

        // Assert: Only 2 draft plans shown
        $plans = $component->get('plans');
        $this->assertCount(2, $plans);

        // Verify all returned plans are drafts
        foreach ($plans as $plan) {
            $this->assertEquals('draft', $plan->status);
            $this->assertEquals('Draft Plan', $plan->title);
        }
    }

    /**
     * Test: Filter plans by status - planned only
     *
     * Scenario: User wants to see only planned trips.
     */
    public function test_filter_plans_by_status_planned(): void
    {
        // Arrange: Mixed status plans
        $user = User::factory()->create([
            'onboarding_completed' => true,
            'email_verified_at' => now(),
        ]);

        TravelPlan::factory()->create([
            'user_id' => $user->id,
            'status' => 'draft',
        ]);
        TravelPlan::factory()->count(2)->create([
            'user_id' => $user->id,
            'status' => 'planned',
            'title' => 'Ready Trip',
        ]);

        // Act: Filter by planned
        $component = Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->set('statusFilter', 'planned');

        // Assert: Only 2 planned shown
        $plans = $component->get('plans');
        $this->assertCount(2, $plans);

        // Verify all returned plans are planned
        foreach ($plans as $plan) {
            $this->assertEquals('planned', $plan->status);
            $this->assertEquals('Ready Trip', $plan->title);
        }
    }

    /**
     * Test: Filter plans by status - completed only
     *
     * Scenario: User wants to see past trips.
     */
    public function test_filter_plans_by_status_completed(): void
    {
        // Arrange: Mixed status plans
        $user = User::factory()->create([
            'onboarding_completed' => true,
            'email_verified_at' => now(),
        ]);

        TravelPlan::factory()->create([
            'user_id' => $user->id,
            'status' => 'planned',
        ]);
        TravelPlan::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'title' => 'Past Trip',
        ]);

        // Act: Filter by completed
        $component = Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->set('statusFilter', 'completed');

        // Assert: Only completed shown
        $plans = $component->get('plans');
        $this->assertCount(1, $plans);
        $this->assertEquals('completed', $plans->first()->status);
        $this->assertEquals('Past Trip', $plans->first()->title);
    }

    /**
     * Test: Search plans by title
     *
     * Scenario: User searches for "Paris" in title.
     * Only matching plans should appear.
     */
    public function test_search_plans_by_title(): void
    {
        // Arrange: Plans with different titles
        $user = User::factory()->create([
            'onboarding_completed' => true,
            'email_verified_at' => now(),
        ]);

        TravelPlan::factory()->create([
            'user_id' => $user->id,
            'title' => 'Summer in Paris',
            'destination' => 'France',
        ]);
        TravelPlan::factory()->create([
            'user_id' => $user->id,
            'title' => 'Winter Trip',
            'destination' => 'Norway',
        ]);

        // Act: Search for "Paris"
        $component = Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->set('search', 'Paris');

        // Assert: Only Paris trip shown
        $plans = $component->get('plans');
        $this->assertCount(1, $plans);
        $this->assertEquals('Summer in Paris', $plans->first()->title);
    }

    /**
     * Test: Search plans by destination
     *
     * Scenario: User searches for "Norway" in destination.
     */
    public function test_search_plans_by_destination(): void
    {
        // Arrange: Plans with different destinations
        $user = User::factory()->create([
            'onboarding_completed' => true,
            'email_verified_at' => now(),
        ]);

        TravelPlan::factory()->create([
            'user_id' => $user->id,
            'title' => 'Trip 1',
            'destination' => 'Norway',
        ]);
        TravelPlan::factory()->create([
            'user_id' => $user->id,
            'title' => 'Trip 2',
            'destination' => 'Sweden',
        ]);

        // Act: Search for "Norway"
        $component = Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->set('search', 'Norway');

        // Assert: Only Norway trip shown
        $plans = $component->get('plans');
        $this->assertCount(1, $plans);
        $this->assertEquals('Norway', $plans->first()->destination);
    }

    /**
     * Test: Search is case-insensitive
     *
     * Scenario: User searches with different case.
     * Search should work regardless of case.
     */
    public function test_search_is_case_insensitive(): void
    {
        // Arrange: Plan with title "Paris"
        $user = User::factory()->create([
            'onboarding_completed' => true,
            'email_verified_at' => now(),
        ]);

        TravelPlan::factory()->create([
            'user_id' => $user->id,
            'title' => 'Paris Adventure',
            'destination' => 'France',
        ]);

        // Act: Search with lowercase
        $component = Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->set('search', 'paris');

        // Assert: Plan is found
        $plans = $component->get('plans');
        $this->assertCount(1, $plans);
        $this->assertEquals('Paris Adventure', $plans->first()->title);
    }

    /**
     * Test: Clear filters resets to default state
     *
     * Scenario: User has applied filters, then clicks "Clear".
     * All filters should be reset and all plans shown.
     */
    public function test_clear_filters_resets_to_default(): void
    {
        // Arrange: User with plans
        $user = User::factory()->create([
            'onboarding_completed' => true,
            'email_verified_at' => now(),
        ]);

        TravelPlan::factory()->count(3)->create(['user_id' => $user->id]);

        // Act: Apply filters then clear
        $component = Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->set('statusFilter', 'draft')
            ->set('search', 'Something')
            ->call('clearFilters');

        // Assert: Filters are reset
        $this->assertEquals('all', $component->get('statusFilter'));
        $this->assertNull($component->get('search'));
    }

    /**
     * Test: Changing filter resets pagination
     *
     * Scenario: User is on page 2, changes filter.
     * Should return to page 1.
     *
     * UX: Avoid showing empty pages after filter change.
     */
    public function test_changing_filter_resets_pagination(): void
    {
        // Arrange: User with many plans on page 2
        $user = User::factory()->create([
            'onboarding_completed' => true,
            'email_verified_at' => now(),
        ]);

        // Create 25 planned plans (to have multiple pages)
        TravelPlan::factory()->count(25)->create([
            'user_id' => $user->id,
            'status' => 'planned',
        ]);

        // Create 1 draft (so filter change has results)
        TravelPlan::factory()->create([
            'user_id' => $user->id,
            'status' => 'draft',
        ]);

        // Act: First load on page 2 (assuming 20 per page)
        $component = Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->call('gotoPage', 2, 'page'); // Navigate to page 2

        // Verify we're on page 2
        $this->assertEquals(2, $component->get('plans')->currentPage());

        // Now change filter
        $component->set('statusFilter', 'draft');

        // Assert: Back to page 1 after filter change
        // The updated() hook in Dashboard component calls resetPage()
        $this->assertEquals(1, $component->get('plans')->currentPage());
    }

    /**
     * Test: Using setFilter method
     *
     * Scenario: User clicks filter button which calls setFilter().
     * Filter should be applied correctly.
     */
    public function test_set_filter_method_works(): void
    {
        // Arrange: User with plans
        $user = User::factory()->create([
            'onboarding_completed' => true,
            'email_verified_at' => now(),
        ]);

        TravelPlan::factory()->create([
            'user_id' => $user->id,
            'status' => 'draft',
        ]);

        // Act: Call setFilter method
        $component = Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->call('setFilter', 'draft');

        // Assert: Filter is set
        $component->assertSet('statusFilter', 'draft');
    }
}
