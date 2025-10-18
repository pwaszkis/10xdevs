<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CompleteUserJourneyTest extends DuskTestCase
{
    use DatabaseTruncation;

    /**
     * Test complete user journey: login → create plan → view plan → dashboard
     */
    public function test_complete_user_journey_from_login_to_plan_management(): void
    {
        $this->browse(function (Browser $browser) {
            // Create user with completed onboarding
            $user = User::factory()->create([
                'email' => 'journey.user@example.com',
                'onboarding_completed' => true,
                'onboarding_completed_at' => now(),
            ]);

            $user->preferences()->create([
                'interests_categories' => ['historia_kultura', 'przyroda_outdoor', 'przygoda'],
                'travel_pace' => 'umiarkowane',
                'budget_level' => 'standardowy',
            ]);

            // STEP 1: Login
            $browser->visit('/login')
                ->type('email', 'journey.user@example.com')
                ->type('password', 'password')
                ->press('ZALOGUJ SIĘ')
                ->pause(2000)
                ->assertPathIs('/dashboard');

            // STEP 2: Should see empty dashboard
            $browser->assertSee('Witaj w VibeTravels!')
                ->assertSee('Nie masz jeszcze żadnych planów podróży');

            // STEP 3: Navigate to create plan
            $browser->visit('/plans/create')
                ->pause(1000)
                ->assertSee('Stwórz nowy plan podróży');

            // STEP 4: Fill in plan creation form
            $departureDate = now()->addDays(45)->format('Y-m-d');

            $browser->type('input[wire\\:model\\.blur="title"]', 'Wakacyjna wycieczka do Paryża')
                ->type('input[wire\\:model\\.blur="destination"]', 'Paryż, Francja');

            // Use Livewire's $set() for reactive inputs
            $browser->script("
                const component = window.Livewire.find(document.querySelector('[wire\\\\:id]').getAttribute('wire:id'));
                component.\$set('departure_date', '$departureDate');
                component.\$set('number_of_days', 4);
                component.\$set('number_of_people', 2);
                component.\$set('budget_per_person', 3000);
            ");

            $browser->pause(1000);

            // STEP 5: Save as draft
            $browser->press('Zapisz jako szkic')
                ->pause(2000)
                ->assertPathBeginsWith('/plans/')
                ->assertSee('Wakacyjna wycieczka do Paryża')
                ->assertSee('Paryż, Francja')
                ->assertSee('Szkic');

            // STEP 6: Go back to dashboard
            $browser->visit('/dashboard')
                ->pause(1000)
                ->assertSee('Wakacyjna wycieczka do Paryża')
                ->assertSee('Paryż, Francja')
                ->assertSee('Szkic');

            // STEP 7: View the plan again
            $browser->clickLink('Wakacyjna wycieczka do Paryża')
                ->pause(1000)
                ->assertPathBeginsWith('/plans/')
                ->assertSee('Wakacyjna wycieczka do Paryża');

            // Journey completed successfully - user can now view and manage their plan
        });
    }

    /**
     * Test user journey with multiple plans
     */
    public function test_user_journey_with_multiple_plans(): void
    {
        $this->browse(function (Browser $browser) {
            // Create user with completed onboarding
            $user = User::factory()->create([
                'email' => 'multi.plans@example.com',
                'onboarding_completed' => true,
                'onboarding_completed_at' => now(),
            ]);

            $user->preferences()->create([
                'interests_categories' => ['historia_kultura'],
                'travel_pace' => 'spokojne',
                'budget_level' => 'ekonomiczny',
            ]);

            // STEP 1: Login
            $browser->visit('/login')
                ->type('email', 'multi.plans@example.com')
                ->type('password', 'password')
                ->press('ZALOGUJ SIĘ')
                ->pause(2000)
                ->assertPathIs('/dashboard');

            // STEP 2: Create first plan
            $browser->visit('/plans/create')
                ->pause(1000);

            $departureDate1 = now()->addDays(30)->format('Y-m-d');

            $browser->type('input[wire\\:model\\.blur="title"]', 'Praga Weekend')
                ->type('input[wire\\:model\\.blur="destination"]', 'Praga, Czechy');

            $browser->script("
                const component = window.Livewire.find(document.querySelector('[wire\\\\:id]').getAttribute('wire:id'));
                component.\$set('departure_date', '$departureDate1');
                component.\$set('number_of_days', 3);
                component.\$set('number_of_people', 1);
                component.\$set('budget_per_person', 1000);
            ");

            $browser->pause(1000)
                ->press('Zapisz jako szkic')
                ->pause(2000)
                ->assertPathBeginsWith('/plans/')
                ->assertSee('Praga Weekend');

            // STEP 3: Go to dashboard and verify first plan is there
            $browser->visit('/dashboard')
                ->pause(1000)
                ->assertSee('Praga Weekend');

            // STEP 4: Create second plan
            $browser->visit('/plans/create')
                ->pause(1000);

            $departureDate2 = now()->addDays(60)->format('Y-m-d');

            $browser->type('input[wire\\:model\\.blur="title"]', 'Kraków History Tour')
                ->type('input[wire\\:model\\.blur="destination"]', 'Kraków, Polska');

            $browser->script("
                const component = window.Livewire.find(document.querySelector('[wire\\\\:id]').getAttribute('wire:id'));
                component.\$set('departure_date', '$departureDate2');
                component.\$set('number_of_days', 2);
                component.\$set('number_of_people', 2);
                component.\$set('budget_per_person', 800);
            ");

            $browser->pause(1000)
                ->press('Zapisz jako szkic')
                ->pause(2000)
                ->assertPathBeginsWith('/plans/')
                ->assertSee('Kraków History Tour');

            // STEP 5: Verify both plans are visible in dashboard
            $browser->visit('/dashboard')
                ->pause(1000)
                ->assertSee('Praga Weekend')
                ->assertSee('Kraków History Tour');
        });
    }
}
