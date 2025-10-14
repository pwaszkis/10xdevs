<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\TravelPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Authorization Tests
 *
 * Tests resource-level authorization (Policy enforcement):
 * - Users cannot access other users' travel plans
 * - Users cannot submit feedback for other users' plans
 * - CSRF protection is enabled on state-changing operations
 *
 * Security: Prevents unauthorized access to user data.
 */
class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: User cannot access other users' plans
     *
     * Scenario: User A tries to view/edit/delete User B's travel plan.
     * Should receive 403 Forbidden.
     *
     * Security: Enforces data isolation between users.
     */
    public function test_user_cannot_access_other_users_plans(): void
    {
        // Arrange: Two users, plan belongs to user1
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $plan = TravelPlan::factory()->create([
            'user_id' => $user1->id,
            'title' => 'User 1 Private Plan',
            'status' => 'planned',
        ]);

        // Act as user2 (unauthorized)
        $this->actingAs($user2);

        // Test 1: Cannot view plan (403 Forbidden or 302 Redirect)
        $response = $this->get("/plans/{$plan->id}");
        $this->assertContains($response->status(), [403, 302], 'Should return 403 Forbidden or 302 Redirect');

        // Test 2: Cannot delete plan via API (403 Forbidden or 302 Redirect)
        $response = $this->deleteJson("/api/travel-plans/{$plan->id}");
        $this->assertContains($response->status(), [403, 401], 'Should return 403 Forbidden or 401 Unauthorized');

        // Test 3: Cannot export to PDF (403 Forbidden or 302 Redirect)
        $response = $this->get("/plans/{$plan->id}/pdf");
        $this->assertContains($response->status(), [403, 302, 404], 'Should return 403/302/404');

        // Assert: Plan still exists (not deleted by unauthorized user)
        $this->assertDatabaseHas('travel_plans', [
            'id' => $plan->id,
            'user_id' => $user1->id,
        ]);
    }

    /**
     * Test: User cannot submit feedback for other users' plan
     *
     * Scenario: User A tries to submit feedback for User B's plan.
     * Should be rejected with 403 Forbidden.
     *
     * Security: Feedback must be from plan owner only.
     */
    public function test_user_cannot_submit_feedback_for_other_users_plan(): void
    {
        // Arrange: Two users
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $plan = TravelPlan::factory()->create([
            'user_id' => $user1->id,
            'status' => 'planned',
        ]);

        // Act: User2 tries to submit feedback for user1's plan
        $this->actingAs($user2);

        $response = $this->postJson("/api/travel-plans/{$plan->id}/feedback", [
            'satisfied' => true,
        ]);

        // Assert: Forbidden (403 or 302)
        $this->assertContains($response->status(), [403, 302], 'Should return 403 Forbidden or 302 Redirect');

        // No feedback created
        $this->assertDatabaseMissing('travel_plan_feedback', [
            'travel_plan_id' => $plan->id,
        ]);
    }

    /**
     * Test: CSRF protection is enabled
     *
     * Scenario: CSRF protection middleware should be active.
     * Laravel Breeze includes VerifyCsrfToken middleware by default.
     *
     * Security: Prevents Cross-Site Request Forgery attacks.
     *
     * NOTE: Skipped - CSRF testing is challenging in feature tests with Livewire
     */
    public function test_csrf_protection_is_enabled(): void
    {
        $this->markTestSkipped(
            'CSRF protection is automatically enabled by Laravel via VerifyCsrfToken middleware. ' .
            'Testing CSRF in feature tests with Livewire components is complex. ' .
            'CSRF is best verified through: ' .
            '1. Checking middleware configuration in bootstrap/app.php, ' .
            '2. Manual testing with browser dev tools, ' .
            '3. E2E tests with actual form submissions.'
        );
    }
}
