<?php

declare(strict_types=1);

namespace Tests\Feature\AI;

use App\Jobs\GenerateTravelPlanJob;
use App\Livewire\Plans\CreatePlanForm;
use App\Models\AIGeneration;
use App\Models\TravelPlan;
use App\Models\User;
use App\Models\UserPreference;
use App\Services\AIGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Test suite for AI plan generation.
 *
 * Tests cover:
 * - Generating plans from form
 * - Checking AI limits
 * - Job processing
 * - AI metadata creation
 */
class PlanGenerationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private UserPreference $preferences;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'onboarding_completed' => true,
            'onboarding_completed_at' => now(),
            'email_verified_at' => now(),
        ]);

        $this->preferences = UserPreference::create([
            'user_id' => $this->user->id,
            'interests_categories' => ['historia_kultura', 'przyroda_outdoor'],
            'travel_pace' => 'umiarkowane',
            'budget_level' => 'standardowy',
            'transport_preference' => 'pieszo_publiczny',
            'restrictions' => 'brak',
        ]);
    }

    public function test_user_can_generate_plan_from_form(): void
    {
        Queue::fake();

        Livewire::actingAs($this->user)
            ->test(CreatePlanForm::class)
            ->set('title', 'Trip to Paris')
            ->set('destination', 'Paris')
            ->set('departure_date', now()->addDays(30)->format('Y-m-d'))
            ->set('number_of_days', 5)
            ->set('number_of_people', 2)
            ->set('budget_per_person', 1000)
            ->set('user_notes', 'Want to see Eiffel Tower')
            ->call('generatePlan')
            ->assertHasNoErrors();

        // Verify job was dispatched
        Queue::assertPushed(GenerateTravelPlanJob::class, function ($job) {
            return $job->userId === $this->user->id;
        });

        // Verify plan was created
        $this->assertDatabaseHas('travel_plans', [
            'user_id' => $this->user->id,
            'title' => 'Trip to Paris',
            'status' => 'draft', // Status before AI generation completes
        ]);

        // Verify AI generation record was created
        $this->assertDatabaseHas('ai_generations', [
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);
    }

    public function test_generate_button_checks_ai_limit(): void
    {
        // Exhaust user's limit (10 generations)
        AIGeneration::factory()->count(10)->create([
            'user_id' => $this->user->id,
            'created_at' => now(),
        ]);

        Livewire::actingAs($this->user)
            ->test(CreatePlanForm::class)
            ->set('title', 'Test')
            ->set('destination', 'Test')
            ->set('departure_date', now()->addDays(10)->format('Y-m-d'))
            ->set('number_of_days', 3)
            ->set('number_of_people', 2)
            ->call('generatePlan')
            ->assertSet('errorMessage', function ($message) {
                return str_contains($message, 'limit');
            });

        // Verify no plan was created
        $this->assertDatabaseMissing('travel_plans', [
            'user_id' => $this->user->id,
            'title' => 'Test',
        ]);
    }

    public function test_plan_generation_job_processes_successfully(): void
    {
        $plan = TravelPlan::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
            'title' => 'Paris Trip',
            'destination' => 'Paris',
            'departure_date' => now()->addDays(30),
            'number_of_days' => 3,
            'number_of_people' => 2,
        ]);

        $aiGeneration = AIGeneration::create([
            'user_id' => $this->user->id,
            'travel_plan_id' => $plan->id,
            'model_used' => 'gpt-4o-mini',
            'status' => 'pending',
        ]);

        // Execute job (sync, using mock OpenAI)
        $job = new GenerateTravelPlanJob(
            travelPlanId: $plan->id,
            userId: $this->user->id,
            aiGenerationId: $aiGeneration->id,
            userPreferences: $this->preferences->toArray()
        );

        // Note: This will use MockOpenAIService in test environment
        $job->handle(app(AIGenerationService::class));

        // Refresh models
        $plan->refresh();
        $aiGeneration->refresh();

        // Verify plan status changed to planned
        $this->assertEquals('planned', $plan->status);

        // Verify AI generation completed
        $this->assertEquals('completed', $aiGeneration->status);
        $this->assertNotNull($aiGeneration->tokens_used);
        $this->assertNotNull($aiGeneration->cost_usd);
        $this->assertGreaterThan(0, $aiGeneration->tokens_used);

        // Verify plan has days
        $this->assertGreaterThan(0, $plan->days()->count());
    }

    public function test_generation_creates_ai_metadata(): void
    {
        $plan = TravelPlan::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);

        // Create AI generation before running job
        $aiGeneration = AIGeneration::create([
            'user_id' => $this->user->id,
            'travel_plan_id' => $plan->id,
            'model_used' => 'gpt-4o-mini',
            'status' => 'pending',
        ]);

        // Execute job
        $job = new GenerateTravelPlanJob(
            travelPlanId: $plan->id,
            userId: $this->user->id,
            aiGenerationId: $aiGeneration->id,
            userPreferences: $this->preferences->toArray()
        );

        $job->handle(app(AIGenerationService::class));

        // Verify AI generation metadata
        $aiGeneration->refresh();

        $this->assertEquals('completed', $aiGeneration->status);
        $this->assertNotNull($aiGeneration->started_at);
        $this->assertNotNull($aiGeneration->completed_at);
        $this->assertGreaterThan(0, $aiGeneration->tokens_used);
        $this->assertGreaterThan(0, $aiGeneration->cost_usd);
        $this->assertEquals('gpt-4o-mini', $aiGeneration->model_used);

        // Verify duration is reasonable (< 60 seconds for mock)
        $duration = $aiGeneration->getDurationInSeconds();
        $this->assertNotNull($duration);
        $this->assertLessThan(60, $duration);
    }

    public function test_regeneration_creates_new_ai_generation_record(): void
    {
        // Create plan with existing generation
        $plan = TravelPlan::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'planned',
        ]);

        $firstGeneration = AIGeneration::factory()->create([
            'user_id' => $this->user->id,
            'travel_plan_id' => $plan->id,
            'status' => 'completed',
        ]);

        Queue::fake();

        // Regenerate plan (confirmRegenerate actually dispatches the job)
        Livewire::actingAs($this->user)
            ->test(\App\Livewire\Plans\Show::class, ['plan' => $plan])
            ->call('confirmRegenerate');

        // Verify new job was dispatched
        Queue::assertPushed(GenerateTravelPlanJob::class);

        // Verify new AI generation was created (should have 2 now)
        $generations = AIGeneration::where('travel_plan_id', $plan->id)->get();
        $this->assertCount(2, $generations);

        // Verify first generation is still completed
        $firstGeneration->refresh();
        $this->assertEquals('completed', $firstGeneration->status);
    }
}
