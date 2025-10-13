<?php

declare(strict_types=1);

namespace Tests\Feature\Onboarding;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Onboarding Flow Test
 *
 * Tests the complete 4-step onboarding wizard flow (happy path).
 */
class OnboardingFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: User can complete full onboarding flow successfully
     */
    public function test_user_can_complete_full_onboarding_flow(): void
    {
        // Arrange: Create authenticated and verified user
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => false,
            'onboarding_step' => 1,
        ]);

        // Act & Assert: Step 1 - Basic Information
        Livewire::actingAs($user)
            ->test(\App\Livewire\Onboarding\OnboardingWizard::class)
            ->assertSet('currentStep', 1)
            ->set('nickname', 'TestNick')
            ->set('homeLocation', 'Warszawa, Polska')
            ->call('nextStep')
            ->assertSet('currentStep', 2)
            ->assertHasNoErrors();

        // Verify step 1 data saved to database
        $user->refresh();
        $this->assertEquals('TestNick', $user->nickname);
        $this->assertEquals('Warszawa, Polska', $user->home_location);
        $this->assertEquals(2, $user->onboarding_step);

        // Act & Assert: Step 2 - Interest Categories
        Livewire::actingAs($user)
            ->test(\App\Livewire\Onboarding\OnboardingWizard::class)
            ->assertSet('currentStep', 2)
            ->call('toggleInterest', 'historia_kultura')
            ->call('toggleInterest', 'gastronomia')
            ->assertSet('interestCategories', ['historia_kultura', 'gastronomia'])
            ->call('nextStep')
            ->assertSet('currentStep', 3)
            ->assertHasNoErrors();

        // Verify step 2 data saved to database
        $user->refresh();
        $this->assertNotNull($user->preferences);
        $this->assertEquals(['historia_kultura', 'gastronomia'], $user->preferences->interests_categories);
        $this->assertEquals(3, $user->onboarding_step);

        // Act & Assert: Step 3 - Practical Parameters
        Livewire::actingAs($user)
            ->test(\App\Livewire\Onboarding\OnboardingWizard::class)
            ->assertSet('currentStep', 3)
            ->call('setTravelPace', 'umiarkowane')
            ->call('setBudgetLevel', 'standardowy')
            ->call('setTransportPreference', 'pieszo_publiczny')
            ->call('setRestrictions', 'brak')
            ->call('nextStep')
            ->assertSet('currentStep', 4)
            ->assertHasNoErrors();

        // Verify step 3 data saved to database
        $user->refresh();
        $preferences = $user->preferences;
        $this->assertEquals('umiarkowane', $preferences->travel_pace);
        $this->assertEquals('standardowy', $preferences->budget_level);
        $this->assertEquals('pieszo_publiczny', $preferences->transport_preference);
        $this->assertEquals('brak', $preferences->restrictions);
        $this->assertEquals(4, $user->onboarding_step);

        // Act & Assert: Step 4 - Complete Onboarding
        Livewire::actingAs($user)
            ->test(\App\Livewire\Onboarding\OnboardingWizard::class)
            ->assertSet('currentStep', 4)
            ->call('completeOnboarding')
            ->assertRedirect(route('welcome'));

        // Verify onboarding marked as completed
        $user->refresh();
        $this->assertTrue($user->onboarding_completed);
        $this->assertNotNull($user->onboarding_completed_at);
        $this->assertEquals(4, $user->onboarding_step);
        $this->assertTrue($user->hasCompletedOnboarding());
    }

    /**
     * Test: User can navigate back through onboarding steps
     */
    public function test_user_can_navigate_back_through_steps(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => false,
            'nickname' => 'TestNick',
            'home_location' => 'Warszawa',
            'onboarding_step' => 3,
        ]);

        UserPreference::create([
            'user_id' => $user->id,
            'interests_categories' => ['historia_kultura'],
            'travel_pace' => 'umiarkowane',
            'budget_level' => 'standardowy',
            'transport_preference' => 'pieszo_publiczny',
            'restrictions' => 'brak',
        ]);

        // Act & Assert: Navigate from step 3 to step 2
        Livewire::actingAs($user)
            ->test(\App\Livewire\Onboarding\OnboardingWizard::class)
            ->assertSet('currentStep', 3)
            ->call('previousStep')
            ->assertSet('currentStep', 2)
            ->assertHasNoErrors();

        // Act & Assert: Navigate from step 2 to step 1
        Livewire::actingAs($user)
            ->test(\App\Livewire\Onboarding\OnboardingWizard::class)
            ->assertSet('currentStep', 3)
            ->call('previousStep')
            ->call('previousStep')
            ->assertSet('currentStep', 1)
            ->assertHasNoErrors();
    }

    /**
     * Test: User cannot navigate back from step 1
     */
    public function test_user_cannot_navigate_back_from_step_one(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => false,
            'onboarding_step' => 1,
        ]);

        // Act & Assert
        Livewire::actingAs($user)
            ->test(\App\Livewire\Onboarding\OnboardingWizard::class)
            ->assertSet('currentStep', 1)
            ->call('previousStep')
            ->assertSet('currentStep', 1); // Should remain at step 1
    }

    /**
     * Test: Completed user is redirected to dashboard
     */
    public function test_completed_user_is_redirected_to_dashboard(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => true,
            'onboarding_completed_at' => now(),
            'onboarding_step' => 4,
        ]);

        // Act & Assert
        $response = $this->actingAs($user)->get(route('onboarding'));

        $response->assertRedirect(route('dashboard'));
    }

    /**
     * Test: Welcome screen shows correct user data
     */
    public function test_welcome_screen_shows_correct_user_data(): void
    {
        // Arrange
        $user = User::factory()->create([
            'name' => 'John Doe',
            'nickname' => 'Johnny',
            'email_verified_at' => now(),
            'onboarding_completed' => true,
            'onboarding_completed_at' => now(),
        ]);

        // Act
        Livewire::actingAs($user)
            ->test(\App\Livewire\Welcome::class)
            ->assertSee('Johnny') // Should show nickname
            ->assertSee('Witaj w VibeTravels')
            ->assertHasNoErrors();
    }

    /**
     * Test: Welcome screen redirects incomplete user to onboarding
     */
    public function test_welcome_screen_redirects_incomplete_user(): void
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
     * Test: Interest category can be toggled on and off
     */
    public function test_interest_category_can_be_toggled(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'nickname' => 'Test',
            'home_location' => 'Test',
            'onboarding_step' => 2,
        ]);

        UserPreference::create([
            'user_id' => $user->id,
            'interests_categories' => [],
        ]);

        // Act & Assert: Add interest
        Livewire::actingAs($user)
            ->test(\App\Livewire\Onboarding\OnboardingWizard::class)
            ->call('toggleInterest', 'historia_kultura')
            ->assertSet('interestCategories', ['historia_kultura'])
            ->call('toggleInterest', 'gastronomia')
            ->assertSet('interestCategories', ['historia_kultura', 'gastronomia']);

        // Act & Assert: Remove interest
        Livewire::actingAs($user)
            ->test(\App\Livewire\Onboarding\OnboardingWizard::class)
            ->set('interestCategories', ['historia_kultura', 'gastronomia'])
            ->call('toggleInterest', 'historia_kultura')
            ->assertSet('interestCategories', ['gastronomia']);
    }

    /**
     * Test: isInterestSelected method works correctly
     */
    public function test_is_interest_selected_works_correctly(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'nickname' => 'Test',
            'home_location' => 'Test',
            'onboarding_step' => 2,
        ]);

        UserPreference::create([
            'user_id' => $user->id,
            'interests_categories' => ['historia_kultura', 'gastronomia'],
        ]);

        // Act & Assert
        $component = Livewire::actingAs($user)
            ->test(\App\Livewire\Onboarding\OnboardingWizard::class);

        $this->assertTrue($component->instance()->isInterestSelected('historia_kultura'));
        $this->assertTrue($component->instance()->isInterestSelected('gastronomia'));
        $this->assertFalse($component->instance()->isInterestSelected('przyroda_outdoor'));
    }
}
