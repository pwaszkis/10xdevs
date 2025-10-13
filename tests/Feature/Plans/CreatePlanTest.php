<?php

declare(strict_types=1);

namespace Tests\Feature\Plans;

use App\Livewire\Plans\CreatePlanForm;
use App\Models\TravelPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Test suite for creating travel plans.
 *
 * Tests cover:
 * - Creating plan drafts
 * - Form validation
 * - Field requirements
 * - Date validation
 * - Plan ownership
 */
class CreatePlanTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'onboarding_completed' => true,
            'onboarding_completed_at' => now(),
            'email_verified_at' => now(),
        ]);
    }

    public function test_user_can_create_plan_draft(): void
    {
        Livewire::actingAs($this->user)
            ->test(CreatePlanForm::class)
            ->set('title', 'Weekend w Krakowie')
            ->set('destination', 'Kraków')
            ->set('departure_date', now()->addDays(10)->format('Y-m-d'))
            ->set('number_of_days', 3)
            ->set('number_of_people', 2)
            ->call('saveAsDraft')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('travel_plans', [
            'user_id' => $this->user->id,
            'title' => 'Weekend w Krakowie',
            'destination' => 'Kraków',
            'status' => 'draft',
            'number_of_days' => 3,
            'number_of_people' => 2,
        ]);
    }

    public function test_user_can_save_plan_with_all_fields(): void
    {
        Livewire::actingAs($this->user)
            ->test(CreatePlanForm::class)
            ->set('title', 'Wakacje w Paryżu')
            ->set('destination', 'Paryż')
            ->set('departure_date', now()->addDays(30)->format('Y-m-d'))
            ->set('number_of_days', 7)
            ->set('number_of_people', 2)
            ->set('budget_per_person', 2500.50)
            ->set('budget_currency', 'EUR')
            ->set('user_notes', 'Chcemy zwiedzić Luwr i Wieżę Eiffla')
            ->call('saveAsDraft')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('travel_plans', [
            'user_id' => $this->user->id,
            'title' => 'Wakacje w Paryżu',
            'destination' => 'Paryż',
            'budget_per_person' => 2500.50,
            'budget_currency' => 'EUR',
            'user_notes' => 'Chcemy zwiedzić Luwr i Wieżę Eiffla',
        ]);
    }

    public function test_plan_requires_title_and_destination(): void
    {
        Livewire::actingAs($this->user)
            ->test(CreatePlanForm::class)
            ->set('title', '')
            ->set('destination', '')
            ->set('departure_date', now()->addDays(5)->format('Y-m-d'))
            ->set('number_of_days', 3)
            ->set('number_of_people', 2)
            ->call('saveAsDraft')
            ->assertHasErrors(['title', 'destination']);
    }

    public function test_plan_requires_departure_date_number_of_days_and_people(): void
    {
        Livewire::actingAs($this->user)
            ->test(CreatePlanForm::class)
            ->set('title', 'Test Plan')
            ->set('destination', 'Warsaw')
            ->set('departure_date', null)
            ->call('saveAsDraft')
            ->assertHasErrors(['departure_date']);

        // Test missing number_of_days (requires validation rule check)
        $component = Livewire::actingAs($this->user)
            ->test(CreatePlanForm::class)
            ->set('title', 'Test Plan')
            ->set('destination', 'Warsaw')
            ->set('departure_date', now()->addDays(5)->format('Y-m-d'))
            ->set('number_of_days', 0) // Invalid: below minimum
            ->call('saveAsDraft');

        $this->assertTrue(
            $component->errors()->has('number_of_days') ||
            $component->errors()->has('number_of_people')
        );
    }

    public function test_departure_date_must_be_in_future(): void
    {
        // Test past date
        Livewire::actingAs($this->user)
            ->test(CreatePlanForm::class)
            ->set('title', 'Past Trip')
            ->set('destination', 'Rome')
            ->set('departure_date', now()->subDay()->format('Y-m-d'))
            ->set('number_of_days', 3)
            ->set('number_of_people', 2)
            ->call('saveAsDraft')
            ->assertHasErrors(['departure_date']);

        // Test today (rule is after:today, so today should fail)
        Livewire::actingAs($this->user)
            ->test(CreatePlanForm::class)
            ->set('title', 'Today Trip')
            ->set('destination', 'Rome')
            ->set('departure_date', now()->format('Y-m-d'))
            ->set('number_of_days', 3)
            ->set('number_of_people', 2)
            ->call('saveAsDraft')
            ->assertHasErrors(['departure_date']);
    }

    public function test_number_of_days_is_between_1_and_30(): void
    {
        // Test 0 days
        Livewire::actingAs($this->user)
            ->test(CreatePlanForm::class)
            ->set('title', 'Test')
            ->set('destination', 'Test')
            ->set('departure_date', now()->addDays(10)->format('Y-m-d'))
            ->set('number_of_days', 0)
            ->set('number_of_people', 2)
            ->call('saveAsDraft')
            ->assertHasErrors(['number_of_days']);

        // Test 31 days
        Livewire::actingAs($this->user)
            ->test(CreatePlanForm::class)
            ->set('title', 'Test')
            ->set('destination', 'Test')
            ->set('departure_date', now()->addDays(10)->format('Y-m-d'))
            ->set('number_of_days', 31)
            ->set('number_of_people', 2)
            ->call('saveAsDraft')
            ->assertHasErrors(['number_of_days']);

        // Test valid: 1 day
        Livewire::actingAs($this->user)
            ->test(CreatePlanForm::class)
            ->set('title', 'One Day Trip')
            ->set('destination', 'Zakopane')
            ->set('departure_date', now()->addDays(5)->format('Y-m-d'))
            ->set('number_of_days', 1)
            ->set('number_of_people', 1)
            ->call('saveAsDraft')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('travel_plans', [
            'title' => 'One Day Trip',
            'number_of_days' => 1,
        ]);

        // Test valid: 30 days
        Livewire::actingAs($this->user)
            ->test(CreatePlanForm::class)
            ->set('title', 'Long Trip')
            ->set('destination', 'Australia')
            ->set('departure_date', now()->addDays(60)->format('Y-m-d'))
            ->set('number_of_days', 30)
            ->set('number_of_people', 2)
            ->call('saveAsDraft')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('travel_plans', [
            'title' => 'Long Trip',
            'number_of_days' => 30,
        ]);
    }

    public function test_number_of_people_is_between_1_and_10(): void
    {
        // Test 0 people
        Livewire::actingAs($this->user)
            ->test(CreatePlanForm::class)
            ->set('title', 'Test')
            ->set('destination', 'Test')
            ->set('departure_date', now()->addDays(10)->format('Y-m-d'))
            ->set('number_of_days', 3)
            ->set('number_of_people', 0)
            ->call('saveAsDraft')
            ->assertHasErrors(['number_of_people']);

        // Test 11 people
        Livewire::actingAs($this->user)
            ->test(CreatePlanForm::class)
            ->set('title', 'Test')
            ->set('destination', 'Test')
            ->set('departure_date', now()->addDays(10)->format('Y-m-d'))
            ->set('number_of_days', 3)
            ->set('number_of_people', 11)
            ->call('saveAsDraft')
            ->assertHasErrors(['number_of_people']);

        // Test valid: 1 person
        Livewire::actingAs($this->user)
            ->test(CreatePlanForm::class)
            ->set('title', 'Solo Trip')
            ->set('destination', 'Barcelona')
            ->set('departure_date', now()->addDays(20)->format('Y-m-d'))
            ->set('number_of_days', 5)
            ->set('number_of_people', 1)
            ->call('saveAsDraft')
            ->assertHasNoErrors();

        // Test valid: 10 people
        Livewire::actingAs($this->user)
            ->test(CreatePlanForm::class)
            ->set('title', 'Group Trip')
            ->set('destination', 'Berlin')
            ->set('departure_date', now()->addDays(40)->format('Y-m-d'))
            ->set('number_of_days', 4)
            ->set('number_of_people', 10)
            ->call('saveAsDraft')
            ->assertHasNoErrors();
    }

    public function test_budget_is_optional(): void
    {
        Livewire::actingAs($this->user)
            ->test(CreatePlanForm::class)
            ->set('title', 'Budget Optional Trip')
            ->set('destination', 'Gdańsk')
            ->set('departure_date', now()->addDays(15)->format('Y-m-d'))
            ->set('number_of_days', 3)
            ->set('number_of_people', 2)
            ->set('budget_per_person', null)
            ->call('saveAsDraft')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('travel_plans', [
            'title' => 'Budget Optional Trip',
            'budget_per_person' => null,
        ]);
    }

    public function test_plan_belongs_to_creator(): void
    {
        Livewire::actingAs($this->user)
            ->test(CreatePlanForm::class)
            ->set('title', 'My Plan')
            ->set('destination', 'Vienna')
            ->set('departure_date', now()->addDays(25)->format('Y-m-d'))
            ->set('number_of_days', 4)
            ->set('number_of_people', 2)
            ->call('saveAsDraft')
            ->assertHasNoErrors();

        $plan = TravelPlan::where('title', 'My Plan')->first();

        $this->assertNotNull($plan);
        $this->assertEquals($this->user->id, $plan->user_id);
    }

    public function test_plan_defaults_to_draft_status(): void
    {
        Livewire::actingAs($this->user)
            ->test(CreatePlanForm::class)
            ->set('title', 'Draft Status Test')
            ->set('destination', 'Prague')
            ->set('departure_date', now()->addDays(20)->format('Y-m-d'))
            ->set('number_of_days', 3)
            ->set('number_of_people', 2)
            ->call('saveAsDraft')
            ->assertHasNoErrors();

        $plan = TravelPlan::where('title', 'Draft Status Test')->first();

        $this->assertNotNull($plan);
        $this->assertEquals('draft', $plan->status);
        $this->assertTrue($plan->isDraft());
    }
}
