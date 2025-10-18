<?php

declare(strict_types=1);

namespace Tests\Browser\Plans;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Plan Creation Flow Browser Test
 *
 * @group critical
 */
class PlanCreationTest extends DuskTestCase
{
    use DatabaseTruncation;

    /**
     * Test that user can save plan as draft.
     */
    public function test_user_can_save_plan_as_draft(): void
    {
        // Create user with completed onboarding
        $user = User::factory()
            ->hasPreferences()
            ->create([
                'nickname' => 'TestUser',
                'home_location' => 'Warszawa, Polska',
                'onboarding_completed' => true,
                'onboarding_completed_at' => now(),
            ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/plans/create')
                ->assertSee('Stwórz nowy plan podróży')
                ->assertSee('Tytuł planu');

            // Fill required fields
            $departureDate = now()->addDays(30)->format('Y-m-d');

            $browser->type('input[wire\\:model\\.blur="title"]', 'Wakacje w Rzymie')
                ->type('input[wire\\:model\\.blur="destination"]', 'Rzym, Włochy');

            // For Livewire inputs, use Livewire's @this.$set() to set values directly
            $browser->script("
                window.Livewire.find(document.querySelector('[wire\\\\:id]').getAttribute('wire:id')).\$set('departure_date', '$departureDate');
                window.Livewire.find(document.querySelector('[wire\\\\:id]').getAttribute('wire:id')).\$set('number_of_days', 5);
                window.Livewire.find(document.querySelector('[wire\\\\:id]').getAttribute('wire:id')).\$set('number_of_people', 2);
            ");

            $browser->pause(1000);

            // Save as draft - redirects to plan show page
            $browser->press('Zapisz jako szkic')
                ->pause(2000) // Wait for redirect
                ->assertPathBeginsWith('/plans/')
                ->assertSee('Wakacje w Rzymie')
                ->assertSee('Rzym, Włochy')
                ->assertSee('Szkic');

            // Verify plan was saved in database
            $this->assertDatabaseHas('travel_plans', [
                'user_id' => $user->id,
                'title' => 'Wakacje w Rzymie',
                'destination' => 'Rzym, Włochy',
                'status' => 'draft',
                'number_of_days' => 5,
                'number_of_people' => 2,
            ]);
        });
    }

    /**
     * Test that user can create plan with budget.
     */
    public function test_user_can_create_plan_with_budget(): void
    {
        $user = User::factory()
            ->hasPreferences()
            ->create([
                'nickname' => 'TestUser',
                'home_location' => 'Warszawa, Polska',
                'onboarding_completed' => true,
                'onboarding_completed_at' => now(),
            ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/plans/create');

            // Fill required fields plus budget
            $departureDate = now()->addDays(15)->format('Y-m-d');

            $browser->type('input[wire\\:model\\.blur="title"]', 'Budżetowy wyjazd')
                ->type('input[wire\\:model\\.blur="destination"]', 'Kraków, Polska');

            // Use Livewire $set() for date/number inputs
            $browser->script("
                const component = window.Livewire.find(document.querySelector('[wire\\\\:id]').getAttribute('wire:id'));
                component.\$set('departure_date', '$departureDate');
                component.\$set('number_of_days', 3);
                component.\$set('number_of_people', 1);
                component.\$set('budget_per_person', 1500);
            ");

            $browser->pause(1000);

            // Should show total budget calculation
            $browser->assertSee('Całkowity budżet');

            // Save as draft
            $browser->press('Zapisz jako szkic')
                ->pause(2000)
                ->assertPathBeginsWith('/plans/')
                ->assertSee('Budżetowy wyjazd');

            // Verify budget was saved
            $this->assertDatabaseHas('travel_plans', [
                'user_id' => $user->id,
                'title' => 'Budżetowy wyjazd',
                'budget_per_person' => 1500.00,
                'budget_currency' => 'PLN',
            ]);
        });
    }

    /**
     * Test that user can add notes to plan.
     */
    public function test_user_can_add_notes_to_plan(): void
    {
        $user = User::factory()
            ->hasPreferences()
            ->create([
                'nickname' => 'TestUser',
                'home_location' => 'Warszawa, Polska',
                'onboarding_completed' => true,
                'onboarding_completed_at' => now(),
            ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/plans/create');

            $notes = 'Chcę zobaczyć Koloseum, Forum Romanum i Fontannę di Trevi. Preferuję hotelе w centrum.';
            $departureDate = now()->addDays(20)->format('Y-m-d');

            $browser->type('input[wire\\:model\\.blur="title"]', 'Rzym z notatkami')
                ->type('input[wire\\:model\\.blur="destination"]', 'Rzym, Włochy');

            // Use Livewire $set() for date/number inputs
            $browser->script("
                const component = window.Livewire.find(document.querySelector('[wire\\\\:id]').getAttribute('wire:id'));
                component.\$set('departure_date', '$departureDate');
                component.\$set('number_of_days', 4);
                component.\$set('number_of_people', 2);
            ");

            $browser->type('textarea[wire\\:model\\.blur="user_notes"]', $notes)
                ->pause(1000);

            // Save as draft
            $browser->press('Zapisz jako szkic')
                ->pause(2000)
                ->assertPathBeginsWith('/plans/')
                ->assertSee('Rzym z notatkami');

            // Verify notes were saved
            $this->assertDatabaseHas('travel_plans', [
                'user_id' => $user->id,
                'title' => 'Rzym z notatkami',
                'user_notes' => $notes,
            ]);
        });
    }

    /**
     * Test that required fields are validated.
     */
    public function test_plan_creation_validates_required_fields(): void
    {
        $user = User::factory()
            ->hasPreferences()
            ->create([
                'nickname' => 'TestUser',
                'home_location' => 'Warszawa, Polska',
                'onboarding_completed' => true,
                'onboarding_completed_at' => now(),
            ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/plans/create');

            // Try to save without filling required fields
            $browser->press('Zapisz jako szkic')
                ->pause(1500);

            // Should show validation errors
            $browser->assertPresent('.text-red-600');
        });
    }

    /**
     * Test that user can cancel plan creation.
     */
    public function test_user_can_cancel_plan_creation(): void
    {
        $user = User::factory()
            ->hasPreferences()
            ->create([
                'nickname' => 'TestUser',
                'home_location' => 'Warszawa, Polska',
                'onboarding_completed' => true,
                'onboarding_completed_at' => now(),
            ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/plans/create')
                ->assertSee('Stwórz nowy plan podróży');

            // Click cancel button
            $browser->clickLink('Anuluj')
                ->waitForLocation('/dashboard', 5)
                ->assertPathIs('/dashboard');
        });
    }
}
