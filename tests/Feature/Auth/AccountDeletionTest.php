<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\AIGeneration;
use App\Models\TravelPlan;
use App\Models\User;
use App\Models\UserPreference;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Account Deletion Tests (GDPR Compliance)
 *
 * Tests account deletion functionality ensuring:
 * - User can delete their account
 * - All related data is cascaded (hard delete)
 * - Proper authentication and authorization
 */
class AccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: AuthService properly deletes user account with cascade
     */
    public function test_auth_service_deletes_account_with_cascade(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        // Create related data
        $preferences = UserPreference::factory()->create([
            'user_id' => $user->id,
        ]);

        $travelPlan = TravelPlan::factory()->create([
            'user_id' => $user->id,
        ]);

        $aiGeneration = AIGeneration::factory()->create([
            'user_id' => $user->id,
            'travel_plan_id' => $travelPlan->id,
        ]);

        // Act
        $authService = app(AuthService::class);
        $authService->deleteAccount($user);

        // Assert user is deleted (hard delete)
        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);

        // Assert related data is also deleted (cascade)
        $this->assertDatabaseMissing('user_preferences', [
            'id' => $preferences->id,
        ]);

        $this->assertDatabaseMissing('travel_plans', [
            'id' => $travelPlan->id,
        ]);

        $this->assertDatabaseMissing('ai_generations', [
            'id' => $aiGeneration->id,
        ]);
    }

    /**
     * Test: Profile page shows delete account section
     */
    public function test_profile_page_shows_delete_account_section(): void
    {
        $user = User::factory()->create([
            'onboarding_completed' => true,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('profile'));

        $response->assertStatus(200);
        $response->assertSee('Usuń konto');
    }

    /**
     * Test: User model force delete removes user from database
     */
    public function test_user_force_delete_removes_from_database(): void
    {
        $user = User::factory()->create();
        $userId = $user->id;

        $user->forceDelete();

        $this->assertDatabaseMissing('users', [
            'id' => $userId,
        ]);
    }

    /**
     * Test: Deleting user with multiple travel plans cascades properly
     */
    public function test_deleting_user_with_multiple_plans_cascades(): void
    {
        $user = User::factory()->create();

        $plan1 = TravelPlan::factory()->create(['user_id' => $user->id]);
        $plan2 = TravelPlan::factory()->create(['user_id' => $user->id]);
        $plan3 = TravelPlan::factory()->create(['user_id' => $user->id]);

        $authService = app(AuthService::class);
        $authService->deleteAccount($user);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('travel_plans', ['id' => $plan1->id]);
        $this->assertDatabaseMissing('travel_plans', ['id' => $plan2->id]);
        $this->assertDatabaseMissing('travel_plans', ['id' => $plan3->id]);
    }
}
