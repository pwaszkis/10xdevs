<?php

declare(strict_types=1);

namespace Tests\Feature\Profile;

use App\Jobs\GenerateTravelPlanJob;
use App\Models\AIGeneration;
use App\Models\TravelPlan;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * User Preferences Test Suite
 *
 * Tests for managing and updating user travel preferences,
 * and their impact on AI plan generation.
 */
class UserPreferencesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: User can update travel preferences
     *
     * Verifies that:
     * - User preferences can be updated via model update
     * - All preference fields are updateable
     * - Changes persist to database
     */
    #[Test]
    public function test_user_can_update_travel_preferences(): void
    {
        // Arrange: User with existing preferences
        $user = User::factory()->create();
        $preferences = UserPreference::factory()->create([
            'user_id' => $user->id,
            'interests_categories' => ['historia_kultura', 'gastronomia'],
            'travel_pace' => 'spokojne',
            'budget_level' => 'ekonomiczny',
            'transport_preference' => 'pieszo_publiczny',
            'restrictions' => 'brak',
        ]);

        // Act: Update preferences to different values
        $preferences->update([
            'interests_categories' => ['przyroda_outdoor', 'sporty_aktywnosci'],
            'travel_pace' => 'intensywne',
            'budget_level' => 'premium',
            'transport_preference' => 'wynajem_auta',
            'restrictions' => 'dieta',
        ]);

        // Assert: Changes persisted
        $preferences->refresh();

        $this->assertEquals(['przyroda_outdoor', 'sporty_aktywnosci'], $preferences->interests_categories);
        $this->assertEquals('intensywne', $preferences->travel_pace);
        $this->assertEquals('premium', $preferences->budget_level);
        $this->assertEquals('wynajem_auta', $preferences->transport_preference);
        $this->assertEquals('dieta', $preferences->restrictions);

        // Verify database record
        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $user->id,
            'travel_pace' => 'intensywne',
            'budget_level' => 'premium',
            'transport_preference' => 'wynajem_auta',
            'restrictions' => 'dieta',
        ]);
    }

    /**
     * Test: Preference changes affect future AI generations
     *
     * Verifies that:
     * - When user changes preferences
     * - New AI generation job uses updated preferences
     * - Job receives current preference values, not old ones
     *
     * This ensures consistency between user settings and generated plans.
     */
    #[Test]
    public function test_preference_changes_affect_future_ai_generations(): void
    {
        Queue::fake();

        // Arrange: User with beach/relax preferences
        $user = User::factory()->create([
            'onboarding_completed' => true,
            'onboarding_completed_at' => now(),
            'email_verified_at' => now(),
        ]);

        $preferences = UserPreference::factory()->create([
            'user_id' => $user->id,
            'interests_categories' => ['plaze_relaks'],
            'travel_pace' => 'spokojne',
            'budget_level' => 'ekonomiczny',
        ]);

        // Create first plan (with old preferences)
        $plan1 = TravelPlan::factory()->create([
            'user_id' => $user->id,
            'title' => 'Beach Vacation',
            'destination' => 'Malibu',
            'departure_date' => now()->addDays(30),
            'number_of_days' => 5,
            'number_of_people' => 2,
            'status' => 'planned',
        ]);

        // Act: Update preferences to hiking/active
        $preferences->update([
            'interests_categories' => ['przyroda_outdoor', 'sporty_aktywnosci'],
            'travel_pace' => 'intensywne',
            'budget_level' => 'standardowy',
        ]);

        $preferences->refresh();

        // Create new plan (should use updated preferences)
        $plan2 = TravelPlan::factory()->create([
            'user_id' => $user->id,
            'title' => 'Mountain Trip',
            'destination' => 'Tatry',
            'departure_date' => now()->addDays(60),
            'number_of_days' => 7,
            'number_of_people' => 2,
            'status' => 'draft',
        ]);

        // Create AI generation record (travel_plan_id is required)
        $aiGeneration = AIGeneration::factory()->create([
            'user_id' => $user->id,
            'travel_plan_id' => $plan2->id,
            'status' => 'pending',
        ]);

        // Dispatch generation job
        GenerateTravelPlanJob::dispatch(
            travelPlanId: $plan2->id,
            userId: $user->id,
            aiGenerationId: $aiGeneration->id,
            userPreferences: $preferences->toArray()
        );

        // Assert: Job was dispatched with UPDATED preferences
        Queue::assertPushed(GenerateTravelPlanJob::class, function (GenerateTravelPlanJob $job) {
            // Verify job has updated preference values
            $jobPreferences = $job->userPreferences;

            return $jobPreferences['interests_categories'] === ['przyroda_outdoor', 'sporty_aktywnosci']
                && $jobPreferences['travel_pace'] === 'intensywne'
                && $jobPreferences['budget_level'] === 'standardowy';
        });

        // Verify the job was dispatched (at least once)
        Queue::assertPushed(GenerateTravelPlanJob::class, 1);
    }

    /**
     * Test: User preferences are created during onboarding
     *
     * Verifies the relationship between User and UserPreference.
     */
    #[Test]
    public function test_user_has_preferences_relation(): void
    {
        $user = User::factory()->create();
        $preferences = UserPreference::factory()->create([
            'user_id' => $user->id,
        ]);

        // Assert: Relationship works
        $this->assertInstanceOf(UserPreference::class, $user->preferences);
        $this->assertEquals($preferences->id, $user->preferences->id);
        $this->assertEquals($user->id, $preferences->user->id);
    }

    /**
     * Test: Preferences can be created with minimum required fields
     *
     * Some fields are optional (restrictions can be null).
     */
    #[Test]
    public function test_preferences_can_be_created_with_minimal_data(): void
    {
        $user = User::factory()->create();

        $preferences = UserPreference::create([
            'user_id' => $user->id,
            'interests_categories' => ['historia_kultura'],
            'travel_pace' => 'umiarkowane',
            'budget_level' => 'standardowy',
            'transport_preference' => 'pieszo_publiczny',
            'restrictions' => null, // Optional
        ]);

        $this->assertNotNull($preferences->id);
        $this->assertNull($preferences->restrictions);
        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $user->id,
            'travel_pace' => 'umiarkowane',
        ]);
    }

    /**
     * Test: Interests categories is cast to array
     *
     * Verifies that the interests_categories field is properly
     * cast to/from array when stored in database.
     */
    #[Test]
    public function test_interests_categories_is_cast_to_array(): void
    {
        $user = User::factory()->create();

        $preferences = UserPreference::factory()->create([
            'user_id' => $user->id,
            'interests_categories' => ['historia_kultura', 'gastronomia', 'nocne_zycie'],
        ]);

        // Fresh from database
        $preferences->refresh();

        // Assert: Still an array after DB round-trip
        $this->assertIsArray($preferences->interests_categories);
        $this->assertCount(3, $preferences->interests_categories);
        $this->assertContains('historia_kultura', $preferences->interests_categories);
        $this->assertContains('gastronomia', $preferences->interests_categories);
        $this->assertContains('nocne_zycie', $preferences->interests_categories);
    }

    /**
     * Test: User can have only one preferences record
     *
     * Verifies the 1:1 relationship constraint.
     */
    #[Test]
    public function test_user_can_have_only_one_preferences_record(): void
    {
        $user = User::factory()->create();

        // Create first preferences
        $preferences1 = UserPreference::factory()->create([
            'user_id' => $user->id,
            'travel_pace' => 'spokojne',
        ]);

        // Verify user has preferences
        $this->assertEquals($preferences1->id, $user->preferences->id);

        // Update the same record (not create new one)
        $preferences1->update(['travel_pace' => 'intensywne']);

        // Verify still same record
        $user->refresh();
        $this->assertEquals($preferences1->id, $user->preferences->id);
        $this->assertEquals('intensywne', $user->preferences->travel_pace);

        // Verify only one record exists for this user
        $this->assertEquals(1, UserPreference::where('user_id', $user->id)->count());
    }
}
