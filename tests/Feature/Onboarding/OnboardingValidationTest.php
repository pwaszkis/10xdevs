<?php

declare(strict_types=1);

namespace Tests\Feature\Onboarding;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Onboarding Validation Test
 *
 * Tests validation rules for each step of the onboarding wizard.
 */
class OnboardingValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Step 1 requires nickname
     */
    public function test_step_1_requires_nickname(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_step' => 1,
        ]);

        // Act & Assert
        Livewire::actingAs($user)
            ->test(\App\Livewire\Onboarding\OnboardingWizard::class)
            ->set('nickname', '')
            ->set('homeLocation', 'Warszawa')
            ->call('nextStep')
            ->assertHasErrors(['nickname' => 'required']);
    }

    /**
     * Test: Step 1 requires home location
     */
    public function test_step_1_requires_home_location(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_step' => 1,
        ]);

        // Act & Assert
        Livewire::actingAs($user)
            ->test(\App\Livewire\Onboarding\OnboardingWizard::class)
            ->set('nickname', 'TestNick')
            ->set('homeLocation', '')
            ->call('nextStep')
            ->assertHasErrors(['homeLocation' => 'required']);
    }

    /**
     * Test: Step 1 nickname max length is 50 characters
     */
    public function test_step_1_nickname_max_length_is_50(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_step' => 1,
        ]);

        // Act & Assert
        Livewire::actingAs($user)
            ->test(\App\Livewire\Onboarding\OnboardingWizard::class)
            ->set('nickname', str_repeat('a', 51)) // 51 characters
            ->set('homeLocation', 'Warszawa')
            ->call('nextStep')
            ->assertHasErrors(['nickname' => 'max']);
    }

    /**
     * Test: Step 1 home location max length is 100 characters
     */
    public function test_step_1_home_location_max_length_is_100(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_step' => 1,
        ]);

        // Act & Assert
        Livewire::actingAs($user)
            ->test(\App\Livewire\Onboarding\OnboardingWizard::class)
            ->set('nickname', 'TestNick')
            ->set('homeLocation', str_repeat('a', 101)) // 101 characters
            ->call('nextStep')
            ->assertHasErrors(['homeLocation' => 'max']);
    }

    /**
     * Test: Step 2 requires at least one interest category
     */
    public function test_step_2_requires_at_least_one_interest(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'nickname' => 'TestNick',
            'home_location' => 'Warszawa',
            'onboarding_step' => 2,
        ]);

        // Act & Assert
        Livewire::actingAs($user)
            ->test(\App\Livewire\Onboarding\OnboardingWizard::class)
            ->set('interestCategories', [])
            ->call('nextStep')
            ->assertHasErrors(['interestCategories' => 'required']);
    }

    /**
     * Test: Step 2 validates interest category values
     */
    public function test_step_2_validates_interest_category_values(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'nickname' => 'TestNick',
            'home_location' => 'Warszawa',
            'onboarding_step' => 2,
        ]);

        // Act & Assert: Invalid category
        Livewire::actingAs($user)
            ->test(\App\Livewire\Onboarding\OnboardingWizard::class)
            ->set('interestCategories', ['invalid_category'])
            ->call('nextStep')
            ->assertHasErrors(['interestCategories.0']);
    }

    /**
     * Test: Step 2 accepts valid interest categories
     */
    public function test_step_2_accepts_valid_interest_categories(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'nickname' => 'TestNick',
            'home_location' => 'Warszawa',
            'onboarding_step' => 2,
        ]);

        // Act & Assert
        Livewire::actingAs($user)
            ->test(\App\Livewire\Onboarding\OnboardingWizard::class)
            ->set('interestCategories', ['historia_kultura', 'gastronomia', 'sztuka_muzea'])
            ->call('nextStep')
            ->assertHasNoErrors()
            ->assertSet('currentStep', 3);
    }

    /**
     * Test: Step 3 requires travel pace
     */
    public function test_step_3_requires_travel_pace(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'nickname' => 'TestNick',
            'home_location' => 'Warszawa',
            'onboarding_step' => 3,
        ]);

        UserPreference::create([
            'user_id' => $user->id,
            'interests_categories' => ['historia_kultura'],
        ]);

        // Act & Assert
        Livewire::actingAs($user)
            ->test(\App\Livewire\Onboarding\OnboardingWizard::class)
            ->set('travelPace', '')
            ->set('budgetLevel', 'standardowy')
            ->set('transportPreference', 'pieszo_publiczny')
            ->set('restrictions', 'brak')
            ->call('nextStep')
            ->assertHasErrors(['travelPace' => 'required']);
    }

    /**
     * Test: Step 3 requires budget level
     */
    public function test_step_3_requires_budget_level(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'nickname' => 'TestNick',
            'home_location' => 'Warszawa',
            'onboarding_step' => 3,
        ]);

        UserPreference::create([
            'user_id' => $user->id,
            'interests_categories' => ['historia_kultura'],
        ]);

        // Act & Assert
        Livewire::actingAs($user)
            ->test(\App\Livewire\Onboarding\OnboardingWizard::class)
            ->set('travelPace', 'umiarkowane')
            ->set('budgetLevel', '')
            ->set('transportPreference', 'pieszo_publiczny')
            ->set('restrictions', 'brak')
            ->call('nextStep')
            ->assertHasErrors(['budgetLevel' => 'required']);
    }

    /**
     * Test: Step 3 requires transport preference
     */
    public function test_step_3_requires_transport_preference(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'nickname' => 'TestNick',
            'home_location' => 'Warszawa',
            'onboarding_step' => 3,
        ]);

        UserPreference::create([
            'user_id' => $user->id,
            'interests_categories' => ['historia_kultura'],
        ]);

        // Act & Assert
        Livewire::actingAs($user)
            ->test(\App\Livewire\Onboarding\OnboardingWizard::class)
            ->set('travelPace', 'umiarkowane')
            ->set('budgetLevel', 'standardowy')
            ->set('transportPreference', '')
            ->set('restrictions', 'brak')
            ->call('nextStep')
            ->assertHasErrors(['transportPreference' => 'required']);
    }

    /**
     * Test: Step 3 requires restrictions
     */
    public function test_step_3_requires_restrictions(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'nickname' => 'TestNick',
            'home_location' => 'Warszawa',
            'onboarding_step' => 3,
        ]);

        UserPreference::create([
            'user_id' => $user->id,
            'interests_categories' => ['historia_kultura'],
        ]);

        // Act & Assert
        Livewire::actingAs($user)
            ->test(\App\Livewire\Onboarding\OnboardingWizard::class)
            ->set('travelPace', 'umiarkowane')
            ->set('budgetLevel', 'standardowy')
            ->set('transportPreference', 'pieszo_publiczny')
            ->set('restrictions', '')
            ->call('nextStep')
            ->assertHasErrors(['restrictions' => 'required']);
    }

    /**
     * Test: Step 3 validates travel pace enum values
     */
    public function test_step_3_validates_travel_pace_enum(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'nickname' => 'TestNick',
            'home_location' => 'Warszawa',
            'onboarding_step' => 3,
        ]);

        UserPreference::create([
            'user_id' => $user->id,
            'interests_categories' => ['historia_kultura'],
        ]);

        // Act & Assert
        Livewire::actingAs($user)
            ->test(\App\Livewire\Onboarding\OnboardingWizard::class)
            ->set('travelPace', 'invalid_pace')
            ->set('budgetLevel', 'standardowy')
            ->set('transportPreference', 'pieszo_publiczny')
            ->set('restrictions', 'brak')
            ->call('nextStep')
            ->assertHasErrors(['travelPace']);
    }

    /**
     * Test: Step 3 validates budget level enum values
     */
    public function test_step_3_validates_budget_level_enum(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'nickname' => 'TestNick',
            'home_location' => 'Warszawa',
            'onboarding_step' => 3,
        ]);

        UserPreference::create([
            'user_id' => $user->id,
            'interests_categories' => ['historia_kultura'],
        ]);

        // Act & Assert
        Livewire::actingAs($user)
            ->test(\App\Livewire\Onboarding\OnboardingWizard::class)
            ->set('travelPace', 'umiarkowane')
            ->set('budgetLevel', 'invalid_budget')
            ->set('transportPreference', 'pieszo_publiczny')
            ->set('restrictions', 'brak')
            ->call('nextStep')
            ->assertHasErrors(['budgetLevel']);
    }

    /**
     * Test: Step 3 validates transport preference enum values
     */
    public function test_step_3_validates_transport_preference_enum(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'nickname' => 'TestNick',
            'home_location' => 'Warszawa',
            'onboarding_step' => 3,
        ]);

        UserPreference::create([
            'user_id' => $user->id,
            'interests_categories' => ['historia_kultura'],
        ]);

        // Act & Assert
        Livewire::actingAs($user)
            ->test(\App\Livewire\Onboarding\OnboardingWizard::class)
            ->set('travelPace', 'umiarkowane')
            ->set('budgetLevel', 'standardowy')
            ->set('transportPreference', 'invalid_transport')
            ->set('restrictions', 'brak')
            ->call('nextStep')
            ->assertHasErrors(['transportPreference']);
    }

    /**
     * Test: Step 3 validates restrictions enum values
     */
    public function test_step_3_validates_restrictions_enum(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'nickname' => 'TestNick',
            'home_location' => 'Warszawa',
            'onboarding_step' => 3,
        ]);

        UserPreference::create([
            'user_id' => $user->id,
            'interests_categories' => ['historia_kultura'],
        ]);

        // Act & Assert
        Livewire::actingAs($user)
            ->test(\App\Livewire\Onboarding\OnboardingWizard::class)
            ->set('travelPace', 'umiarkowane')
            ->set('budgetLevel', 'standardowy')
            ->set('transportPreference', 'pieszo_publiczny')
            ->set('restrictions', 'invalid_restriction')
            ->call('nextStep')
            ->assertHasErrors(['restrictions']);
    }

    /**
     * Test: Step 3 accepts all valid enum values
     */
    public function test_step_3_accepts_all_valid_enum_values(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'nickname' => 'TestNick',
            'home_location' => 'Warszawa',
            'onboarding_step' => 3,
        ]);

        UserPreference::create([
            'user_id' => $user->id,
            'interests_categories' => ['historia_kultura'],
        ]);

        // Test all valid combinations
        $validPaces = ['spokojne', 'umiarkowane', 'intensywne'];
        $validBudgets = ['ekonomiczny', 'standardowy', 'premium'];
        $validTransports = ['pieszo_publiczny', 'wynajem_auta', 'mix'];
        $validRestrictions = ['brak', 'dieta', 'mobilnosc'];

        foreach ($validPaces as $pace) {
            foreach ($validBudgets as $budget) {
                foreach ($validTransports as $transport) {
                    foreach ($validRestrictions as $restriction) {
                        Livewire::actingAs($user)
                            ->test(\App\Livewire\Onboarding\OnboardingWizard::class)
                            ->set('travelPace', $pace)
                            ->set('budgetLevel', $budget)
                            ->set('transportPreference', $transport)
                            ->set('restrictions', $restriction)
                            ->call('nextStep')
                            ->assertHasNoErrors();

                        // Reset for next iteration
                        $user->update(['onboarding_step' => 3]);
                    }
                }
            }
        }
    }
}
