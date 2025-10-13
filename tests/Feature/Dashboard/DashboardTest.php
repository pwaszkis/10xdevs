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
 * Dashboard Component Tests
 *
 * Tests basic dashboard functionality:
 * - Access control (auth required)
 * - Displaying user's travel plans
 * - Empty state handling
 * - Personalized greeting
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Authenticated user can access dashboard
     *
     * Scenario: Logged-in user with completed onboarding
     * navigates to dashboard. Should see personalized greeting.
     */
    public function test_authenticated_user_can_access_dashboard(): void
    {
        // Arrange: User with completed onboarding and preferences
        $user = User::factory()->create([
            'nickname' => 'JohnDoe',
            'onboarding_completed' => true,
            'onboarding_completed_at' => now(), // Required by hasCompletedOnboarding()
            'email_verified_at' => now(),
            'home_location' => 'Warsaw, Poland',
        ]);

        // Create user preferences (required by onboarding completion)
        $user->preferences()->create([
            'interests' => ['historia_kultura'],
            'travel_pace' => 'umiarkowane',
            'budget_level' => 'standardowy',
            'transport_preference' => 'pieszo_publiczny',
            'restrictions' => 'brak',
        ]);

        $this->actingAs($user);

        // Act: Access dashboard
        $response = $this->get('/dashboard');

        // Assert: Page loads successfully
        $response->assertOk();
        $response->assertSee('Cześć JohnDoe!'); // Personalized greeting from Dashboard component
    }

    /**
     * Test: Guest cannot access dashboard
     *
     * Scenario: Unauthenticated user tries to access dashboard.
     * Should be redirected to login page.
     *
     * Security: Dashboard requires authentication.
     */
    public function test_guest_cannot_access_dashboard(): void
    {
        // Act: Try to access dashboard without authentication
        $response = $this->get('/dashboard');

        // Assert: Redirected to login
        $response->assertRedirect('/login');
    }

    /**
     * Test: Dashboard displays user's plans
     *
     * Scenario: User has 3 travel plans.
     * Dashboard should display all of them.
     */
    public function test_dashboard_displays_user_plans(): void
    {
        // Arrange: User with 3 travel plans
        $user = User::factory()->create([
            'onboarding_completed' => true,
            'email_verified_at' => now(),
        ]);

        $plans = TravelPlan::factory()->count(3)->create([
            'user_id' => $user->id,
            'title' => 'Plan Title',
            'destination' => 'Paris',
        ]);

        // Act: Load dashboard component
        $component = Livewire::actingAs($user)
            ->test(Dashboard::class);

        // Assert: All plans are visible
        $component->assertSee('Plan Title');
        $component->assertSee('Paris');

        // Verify computed property returns correct count
        $this->assertCount(3, $component->get('plans'));
    }

    /**
     * Test: Dashboard shows empty state when no plans
     *
     * Scenario: New user without any travel plans.
     * Dashboard should show helpful empty state message.
     *
     * UX: Encourages user to create first plan.
     */
    public function test_dashboard_shows_empty_state_when_no_plans(): void
    {
        // Arrange: User without any plans
        $user = User::factory()->create([
            'onboarding_completed' => true,
            'email_verified_at' => now(),
        ]);

        // Act: Load dashboard
        $component = Livewire::actingAs($user)
            ->test(Dashboard::class);

        // Assert: Empty state is shown
        $component->assertSee('Nie masz jeszcze żadnych planów');

        // Verify hasPlans computed property
        $this->assertFalse($component->get('hasPlans'));
    }

    /**
     * Test: Dashboard shows personalized greeting
     *
     * Scenario: User has nickname set in profile.
     * Dashboard should greet them by nickname.
     *
     * UX: Personalized experience.
     */
    public function test_dashboard_shows_personalized_greeting(): void
    {
        // Arrange: User with nickname
        $user = User::factory()->create([
            'nickname' => 'Traveler123',
            'name' => 'John Doe',
            'onboarding_completed' => true,
            'email_verified_at' => now(),
        ]);

        // Act: Load dashboard
        $component = Livewire::actingAs($user)
            ->test(Dashboard::class);

        // Assert: Nickname is used in greeting
        $this->assertEquals('Traveler123', $component->get('userNickname'));
    }

    /**
     * Test: Dashboard shows only user's own plans
     *
     * Scenario: Multiple users have plans.
     * Each user should see only their own plans.
     *
     * Security: Data isolation between users.
     */
    public function test_dashboard_shows_only_users_own_plans(): void
    {
        // Arrange: Two users with different plans
        $user1 = User::factory()->create([
            'onboarding_completed' => true,
            'email_verified_at' => now(),
        ]);
        $user2 = User::factory()->create([
            'onboarding_completed' => true,
            'email_verified_at' => now(),
        ]);

        $user1Plan = TravelPlan::factory()->create([
            'user_id' => $user1->id,
            'title' => 'User 1 Plan',
        ]);
        $user2Plan = TravelPlan::factory()->create([
            'user_id' => $user2->id,
            'title' => 'User 2 Plan',
        ]);

        // Act: User1 loads dashboard
        $component = Livewire::actingAs($user1)
            ->test(Dashboard::class);

        // Assert: Only user1's plan is visible
        $component->assertSee('User 1 Plan');
        $component->assertDontSee('User 2 Plan');

        $this->assertCount(1, $component->get('plans'));
    }

    /**
     * Test: Dashboard displays plan counts correctly
     *
     * Scenario: User has plans with different statuses.
     * Count badges should show correct numbers.
     */
    public function test_dashboard_displays_plan_counts_correctly(): void
    {
        // Arrange: User with mixed status plans
        $user = User::factory()->create([
            'onboarding_completed' => true,
            'email_verified_at' => now(),
        ]);

        TravelPlan::factory()->count(2)->create([
            'user_id' => $user->id,
            'status' => 'draft',
        ]);
        TravelPlan::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => 'planned',
        ]);
        TravelPlan::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
        ]);

        // Act: Load dashboard
        $component = Livewire::actingAs($user)
            ->test(Dashboard::class);

        // Assert: Counts are correct
        $counts = $component->get('planCounts');

        $this->assertEquals(6, $counts['all']);
        $this->assertEquals(2, $counts['draft']);
        $this->assertEquals(3, $counts['planned']);
        $this->assertEquals(1, $counts['completed']);
    }
}
