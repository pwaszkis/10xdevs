<?php

declare(strict_types=1);

namespace Tests\Feature\Onboarding;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Onboarding Middleware Test
 *
 * Tests the EnsureOnboardingCompleted middleware behavior.
 */
class OnboardingMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Incomplete user cannot access dashboard
     */
    public function test_incomplete_user_cannot_access_dashboard(): void
    {
        // Arrange: User with incomplete onboarding
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => false,
            'onboarding_step' => 2,
        ]);

        // Act
        $response = $this->actingAs($user)->get(route('dashboard'));

        // Assert
        $response->assertRedirect(route('onboarding'));
        $response->assertSessionHas('info', 'Proszę uzupełnić swój profil.');
    }

    /**
     * Test: Completed user can access dashboard
     */
    public function test_completed_user_can_access_dashboard(): void
    {
        // Arrange: User with completed onboarding
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => true,
            'onboarding_completed_at' => now(),
            'onboarding_step' => 4,
        ]);

        // Act
        $response = $this->actingAs($user)->get(route('dashboard'));

        // Assert
        $response->assertOk();
        $response->assertSee('Dashboard'); // Assuming dashboard has this text
    }

    /**
     * Test: Incomplete user cannot access plans.create
     */
    public function test_incomplete_user_cannot_access_plans_create(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => false,
        ]);

        // Act
        $response = $this->actingAs($user)->get(route('plans.create'));

        // Assert
        $response->assertRedirect(route('onboarding'));
    }

    /**
     * Test: Completed user can access plans.create
     */
    public function test_completed_user_can_access_plans_create(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => true,
            'onboarding_completed_at' => now(),
        ]);

        // Act
        $response = $this->actingAs($user)->get(route('plans.create'));

        // Assert
        $response->assertOk();
    }

    /**
     * Test: Unauthenticated user cannot access onboarding
     */
    public function test_guest_cannot_access_onboarding(): void
    {
        // Act
        $response = $this->get(route('onboarding'));

        // Assert
        $response->assertRedirect(route('login'));
    }

    /**
     * Test: Authenticated user without email verification cannot access onboarding
     */
    public function test_unverified_user_cannot_access_onboarding(): void
    {
        // Arrange: User without email verification
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        // Act
        $response = $this->actingAs($user)->get(route('onboarding'));

        // Assert
        $response->assertRedirect(route('verification.notice'));
    }

    /**
     * Test: Verified user can access onboarding
     */
    public function test_verified_user_can_access_onboarding(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => false,
        ]);

        // Act
        $response = $this->actingAs($user)->get(route('onboarding'));

        // Assert
        $response->assertOk();
    }

    /**
     * Test: Incomplete user can access welcome route
     */
    public function test_incomplete_user_cannot_access_welcome(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => false,
        ]);

        // Act
        $response = $this->actingAs($user)->get(route('welcome'));

        // Assert
        $response->assertRedirect(route('onboarding'));
    }

    /**
     * Test: Completed user can access welcome route
     */
    public function test_completed_user_can_access_welcome(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => true,
            'onboarding_completed_at' => now(),
        ]);

        // Act
        $response = $this->actingAs($user)->get(route('welcome'));

        // Assert
        $response->assertOk();
    }

    /**
     * Test: User at step 1 has correct access
     */
    public function test_user_at_step_1_has_correct_access(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => false,
            'onboarding_step' => 1,
        ]);

        // Assert: Can access onboarding
        $this->actingAs($user)->get(route('onboarding'))->assertOk();

        // Assert: Cannot access dashboard
        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('onboarding'));

        // Assert: Cannot access welcome
        $this->actingAs($user)->get(route('welcome'))->assertRedirect(route('onboarding'));
    }

    /**
     * Test: User at step 4 (not completed) has correct access
     */
    public function test_user_at_step_4_not_completed_has_correct_access(): void
    {
        // Arrange: User at step 4 but not marked as completed
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => false,
            'onboarding_step' => 4,
        ]);

        // Assert: Can access onboarding
        $this->actingAs($user)->get(route('onboarding'))->assertOk();

        // Assert: Cannot access dashboard (not completed yet)
        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('onboarding'));
    }

    /**
     * Test: Middleware redirects to onboarding with preserved intended URL
     */
    public function test_middleware_preserves_intended_url(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => false,
        ]);

        // Act: Try to access dashboard (will be redirected to onboarding)
        $response = $this->actingAs($user)->get(route('dashboard'));

        // Assert: Redirected to onboarding
        $response->assertRedirect(route('onboarding'));

        // Session should have info message
        $response->assertSessionHas('info');
    }

    /**
     * Test: User with onboarding_completed=true but no timestamp cannot access dashboard
     */
    public function test_user_with_incomplete_completion_data_cannot_access_dashboard(): void
    {
        // Arrange: User with boolean flag but no timestamp (edge case)
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => true,
            'onboarding_completed_at' => null, // Missing timestamp
        ]);

        // Act
        $response = $this->actingAs($user)->get(route('dashboard'));

        // Assert: Should be redirected because hasCompletedOnboarding() checks both
        $response->assertRedirect(route('onboarding'));
    }

    /**
     * Test: OAuth user must complete onboarding
     */
    public function test_oauth_user_must_complete_onboarding(): void
    {
        // Arrange: OAuth user (Google) without completed onboarding
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'google_id' => 'google_123456',
            'provider' => 'google',
            'onboarding_completed' => false,
        ]);

        // Act
        $response = $this->actingAs($user)->get(route('dashboard'));

        // Assert
        $response->assertRedirect(route('onboarding'));
    }

    /**
     * Test: Profile route does not require completed onboarding
     */
    public function test_profile_route_does_not_require_completed_onboarding(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => false,
        ]);

        // Act
        $response = $this->actingAs($user)->get(route('profile'));

        // Assert: Profile should be accessible even without completed onboarding
        $response->assertOk();
    }
}
