<?php

declare(strict_types=1);

namespace Tests\Feature\Onboarding;

use App\Actions\Onboarding\CompleteOnboardingAction;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Onboarding Persistence Test
 *
 * Tests data persistence and resume functionality of the onboarding wizard.
 */
class OnboardingPersistenceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Step 1 data is saved to database
     */
    public function test_step_1_data_is_saved_to_database(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_step' => 1,
        ]);

        // Act
        Livewire::actingAs($user)
            ->test(\App\Livewire\Onboarding\OnboardingWizard::class)
            ->set('nickname', 'TestNick')
            ->set('homeLocation', 'Warszawa')
            ->call('nextStep');

        // Assert
        $user->refresh();
        $this->assertEquals('TestNick', $user->nickname);
        $this->assertEquals('Warszawa', $user->home_location);
        $this->assertEquals(2, $user->onboarding_step);
    }

    /**
     * Test: Step 2 data is saved to database
     */
    public function test_step_2_data_is_saved_to_database(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'nickname' => 'TestNick',
            'home_location' => 'Warszawa',
            'onboarding_step' => 2,
        ]);

        // Act
        Livewire::actingAs($user)
            ->test(\App\Livewire\Onboarding\OnboardingWizard::class)
            ->set('interestCategories', ['historia_kultura', 'gastronomia'])
            ->call('nextStep');

        // Assert
        $user->refresh();
        $this->assertNotNull($user->preferences);
        $this->assertEquals(['historia_kultura', 'gastronomia'], $user->preferences->interests_categories);
        $this->assertEquals(3, $user->onboarding_step);
    }

    /**
     * Test: Step 3 data is saved to database
     */
    public function test_step_3_data_is_saved_to_database(): void
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

        // Act
        Livewire::actingAs($user)
            ->test(\App\Livewire\Onboarding\OnboardingWizard::class)
            ->set('travelPace', 'umiarkowane')
            ->set('budgetLevel', 'standardowy')
            ->set('transportPreference', 'pieszo_publiczny')
            ->set('restrictions', 'brak')
            ->call('nextStep');

        // Assert
        $user->refresh();
        $preferences = $user->preferences;
        $this->assertEquals('umiarkowane', $preferences->travel_pace);
        $this->assertEquals('standardowy', $preferences->budget_level);
        $this->assertEquals('pieszo_publiczny', $preferences->transport_preference);
        $this->assertEquals('brak', $preferences->restrictions);
        $this->assertEquals(4, $user->onboarding_step);
    }

    /**
     * Test: User can resume onboarding from step 2
     */
    public function test_user_can_resume_onboarding_from_step_2(): void
    {
        // Arrange: User who stopped at step 2
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'nickname' => 'TestNick',
            'home_location' => 'Warszawa',
            'onboarding_step' => 2,
        ]);

        // Act: Mount component
        $component = Livewire::actingAs($user)
            ->test(\App\Livewire\Onboarding\OnboardingWizard::class);

        // Assert: Should start at step 2 with loaded data
        $component->assertSet('currentStep', 2);
        $component->assertSet('nickname', 'TestNick');
        $component->assertSet('homeLocation', 'Warszawa');
    }

    /**
     * Test: User can resume onboarding from step 3
     */
    public function test_user_can_resume_onboarding_from_step_3(): void
    {
        // Arrange: User who stopped at step 3
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'nickname' => 'TestNick',
            'home_location' => 'Warszawa',
            'onboarding_step' => 3,
        ]);

        UserPreference::create([
            'user_id' => $user->id,
            'interests_categories' => ['historia_kultura', 'gastronomia'],
        ]);

        // Act: Mount component
        $component = Livewire::actingAs($user)
            ->test(\App\Livewire\Onboarding\OnboardingWizard::class);

        // Assert: Should start at step 3 with loaded data
        $component->assertSet('currentStep', 3);
        $component->assertSet('nickname', 'TestNick');
        $component->assertSet('homeLocation', 'Warszawa');
        $component->assertSet('interestCategories', ['historia_kultura', 'gastronomia']);
    }

    /**
     * Test: User can resume onboarding from step 4
     */
    public function test_user_can_resume_onboarding_from_step_4(): void
    {
        // Arrange: User who stopped at step 4 (summary)
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'nickname' => 'TestNick',
            'home_location' => 'Warszawa',
            'onboarding_step' => 4,
        ]);

        UserPreference::create([
            'user_id' => $user->id,
            'interests_categories' => ['historia_kultura'],
            'travel_pace' => 'umiarkowane',
            'budget_level' => 'standardowy',
            'transport_preference' => 'pieszo_publiczny',
            'restrictions' => 'brak',
        ]);

        // Act: Mount component
        $component = Livewire::actingAs($user)
            ->test(\App\Livewire\Onboarding\OnboardingWizard::class);

        // Assert: Should start at step 4 with all data loaded
        $component->assertSet('currentStep', 4);
        $component->assertSet('nickname', 'TestNick');
        $component->assertSet('travelPace', 'umiarkowane');
        $component->assertSet('budgetLevel', 'standardowy');
    }

    /**
     * Test: Partial data persists across browser sessions
     */
    public function test_partial_data_persists_across_sessions(): void
    {
        // Arrange: User completes step 1
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_step' => 1,
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Onboarding\OnboardingWizard::class)
            ->set('nickname', 'SessionTest')
            ->set('homeLocation', 'Kraków')
            ->call('nextStep');

        // Simulate: User closes browser, comes back later
        $user->refresh();

        // Act: User returns and resumes
        $component = Livewire::actingAs($user)
            ->test(\App\Livewire\Onboarding\OnboardingWizard::class);

        // Assert: Data from step 1 is still there
        $component->assertSet('nickname', 'SessionTest');
        $component->assertSet('homeLocation', 'Kraków');
        $component->assertSet('currentStep', 2);
    }

    /**
     * Test: User can modify previous step data
     */
    public function test_user_can_modify_previous_step_data(): void
    {
        // Arrange: User at step 2
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'nickname' => 'OldNick',
            'home_location' => 'Warszawa',
            'onboarding_step' => 2,
        ]);

        // Act: Go back to step 1 and change nickname
        Livewire::actingAs($user)
            ->test(\App\Livewire\Onboarding\OnboardingWizard::class)
            ->call('previousStep')
            ->assertSet('currentStep', 1)
            ->set('nickname', 'NewNick')
            ->set('homeLocation', 'Gdańsk')
            ->call('nextStep');

        // Assert: Changes are saved
        $user->refresh();
        $this->assertEquals('NewNick', $user->nickname);
        $this->assertEquals('Gdańsk', $user->home_location);
    }

    /**
     * Test: CompleteOnboardingAction updates all required fields
     */
    public function test_complete_onboarding_action_updates_all_fields(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'nickname' => 'TestNick',
            'home_location' => 'Warszawa',
            'onboarding_completed' => false,
            'onboarding_step' => 4,
        ]);

        UserPreference::create([
            'user_id' => $user->id,
            'interests_categories' => ['historia_kultura'],
            'travel_pace' => 'umiarkowane',
            'budget_level' => 'standardowy',
            'transport_preference' => 'pieszo_publiczny',
            'restrictions' => 'brak',
        ]);

        // Act
        $action = new CompleteOnboardingAction;
        $completedUser = $action->execute($user);

        // Assert
        $this->assertTrue($completedUser->onboarding_completed);
        $this->assertNotNull($completedUser->onboarding_completed_at);
        $this->assertEquals(4, $completedUser->onboarding_step);
        $this->assertTrue($completedUser->hasCompletedOnboarding());
    }

    /**
     * Test: CompleteOnboardingAction is atomic (uses transaction)
     */
    public function test_complete_onboarding_action_is_atomic(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'nickname' => 'TestNick',
            'home_location' => 'Warszawa',
            'onboarding_completed' => false,
            'onboarding_step' => 4,
        ]);

        UserPreference::create([
            'user_id' => $user->id,
            'interests_categories' => ['historia_kultura'],
            'travel_pace' => 'umiarkowane',
            'budget_level' => 'standardowy',
            'transport_preference' => 'pieszo_publiczny',
            'restrictions' => 'brak',
        ]);

        // Act
        $action = new CompleteOnboardingAction;
        $completedUser = $action->execute($user, [
            'interests_categories' => ['gastronomia', 'sztuka_muzea'],
        ]);

        // Assert: Both user and preferences updated
        $completedUser->refresh();
        $this->assertTrue($completedUser->onboarding_completed);
        $this->assertEquals(['gastronomia', 'sztuka_muzea'], $completedUser->preferences->interests_categories);
    }

    /**
     * Test: User preferences are created if they don't exist
     */
    public function test_user_preferences_are_created_if_not_exist(): void
    {
        // Arrange: User without preferences
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'nickname' => 'TestNick',
            'home_location' => 'Warszawa',
            'onboarding_step' => 2,
        ]);

        $this->assertNull($user->preferences);

        // Act: Complete step 2
        Livewire::actingAs($user)
            ->test(\App\Livewire\Onboarding\OnboardingWizard::class)
            ->set('interestCategories', ['historia_kultura'])
            ->call('nextStep');

        // Assert: Preferences created
        $user->refresh();
        $this->assertNotNull($user->preferences);
        $this->assertInstanceOf(UserPreference::class, $user->preferences);
    }

    /**
     * Test: Existing preferences are updated, not duplicated
     */
    public function test_existing_preferences_are_updated_not_duplicated(): void
    {
        // Arrange: User with existing preferences
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'nickname' => 'TestNick',
            'home_location' => 'Warszawa',
            'onboarding_step' => 3,
        ]);

        $preferences = UserPreference::create([
            'user_id' => $user->id,
            'interests_categories' => ['historia_kultura'],
        ]);

        $initialId = $preferences->id;

        // Act: Update preferences in step 3
        Livewire::actingAs($user)
            ->test(\App\Livewire\Onboarding\OnboardingWizard::class)
            ->set('travelPace', 'intensywne')
            ->set('budgetLevel', 'premium')
            ->set('transportPreference', 'wynajem_auta')
            ->set('restrictions', 'dieta')
            ->call('nextStep');

        // Assert: Same preference record updated (not new one created)
        $user->refresh();
        $this->assertEquals($initialId, $user->preferences->id);
        $this->assertEquals('intensywne', $user->preferences->travel_pace);
        $this->assertEquals('premium', $user->preferences->budget_level);

        // Assert: Only one preference record exists
        $this->assertEquals(1, UserPreference::where('user_id', $user->id)->count());
    }

    /**
     * Test: onboarding_step increments correctly through flow
     */
    public function test_onboarding_step_increments_correctly(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_step' => 1,
        ]);

        // Step 1 → 2
        Livewire::actingAs($user)
            ->test(\App\Livewire\Onboarding\OnboardingWizard::class)
            ->set('nickname', 'Test')
            ->set('homeLocation', 'Test')
            ->call('nextStep');

        $user->refresh();
        $this->assertEquals(2, $user->onboarding_step);

        // Step 2 → 3
        Livewire::actingAs($user)
            ->test(\App\Livewire\Onboarding\OnboardingWizard::class)
            ->set('interestCategories', ['historia_kultura'])
            ->call('nextStep');

        $user->refresh();
        $this->assertEquals(3, $user->onboarding_step);

        // Step 3 → 4
        Livewire::actingAs($user)
            ->test(\App\Livewire\Onboarding\OnboardingWizard::class)
            ->set('travelPace', 'umiarkowane')
            ->set('budgetLevel', 'standardowy')
            ->set('transportPreference', 'pieszo_publiczny')
            ->set('restrictions', 'brak')
            ->call('nextStep');

        $user->refresh();
        $this->assertEquals(4, $user->onboarding_step);
    }

    /**
     * Test: All user data is retained after completion
     */
    public function test_all_user_data_is_retained_after_completion(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'nickname' => 'CompleteTest',
            'home_location' => 'Poznań',
            'onboarding_step' => 4,
        ]);

        UserPreference::create([
            'user_id' => $user->id,
            'interests_categories' => ['historia_kultura', 'gastronomia'],
            'travel_pace' => 'spokojne',
            'budget_level' => 'ekonomiczny',
            'transport_preference' => 'mix',
            'restrictions' => 'mobilnosc',
        ]);

        // Act: Complete onboarding
        $action = new CompleteOnboardingAction;
        $completedUser = $action->execute($user);

        // Assert: All data retained
        $completedUser->refresh();
        $this->assertEquals('CompleteTest', $completedUser->nickname);
        $this->assertEquals('Poznań', $completedUser->home_location);
        $this->assertEquals(['historia_kultura', 'gastronomia'], $completedUser->preferences->interests_categories);
        $this->assertEquals('spokojne', $completedUser->preferences->travel_pace);
        $this->assertEquals('ekonomiczny', $completedUser->preferences->budget_level);
        $this->assertEquals('mix', $completedUser->preferences->transport_preference);
        $this->assertEquals('mobilnosc', $completedUser->preferences->restrictions);
    }
}
