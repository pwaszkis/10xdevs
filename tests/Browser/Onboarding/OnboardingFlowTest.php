<?php

declare(strict_types=1);

namespace Tests\Browser\Onboarding;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Onboarding Flow Browser Test
 *
 * @group critical
 */
class OnboardingFlowTest extends DuskTestCase
{
    use DatabaseTruncation;

    /**
     * Test that new user can complete full onboarding.
     */
    public function test_user_can_complete_full_onboarding(): void
    {
        // Create user without onboarding
        $user = User::factory()->create([
            'email' => 'newuser@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            // Login
            $browser->loginAs($user)
                ->visit('/onboarding')
                ->assertSee('Krok 1 z 4')
                ->assertSee('Dane podstawowe');

            // Step 1: Basic data
            $browser->waitFor('input[wire\\:model\\.live="nickname"]', 5)
                ->type('input[wire\\:model\\.live="nickname"]', 'JanKowalski')
                ->type('input[wire\\:model\\.live="homeLocation"]', 'Warszawa, Polska')
                ->pause(500)
                ->press('Dalej')
                ->waitForText('Krok 2', 10)
                ->assertSee('Zainteresowania');

            // Step 2: Interests - click on interest cards
            $browser->waitFor('button[wire\\:click*="historia_kultura"]', 5)
                ->click('button[wire\\:click*="historia_kultura"]')
                ->click('button[wire\\:click*="przyroda_outdoor"]')
                ->click('button[wire\\:click*="gastronomia"]')
                ->pause(500)
                ->press('Dalej')
                ->waitForText('Krok 3', 10)
                ->assertSee('Parametry');

            // Step 3: Parameters - travel pace, budget, transport, restrictions
            $browser->waitFor('button[wire\\:click*="umiarkowane"]', 5)
                ->click('button[wire\\:click*="umiarkowane"]')
                ->pause(500)
                ->click('button[wire\\:click*="standardowy"]')  // Budget level
                ->pause(500)
                ->click('button[wire\\:click*="mix"]')  // Transport preference
                ->pause(500)
                ->click('button[wire\\:click*="brak"]')  // Restrictions
                ->pause(500)
                ->press('Dalej')
                ->waitForText('Krok 4', 10)
                ->assertSee('Podsumujmy');

            // Step 4: Summary and complete
            $browser->press('Zakończ')
                ->waitForText('Witaj w VibeTravels', 10)
                ->assertSee('JanKowalski')
                ->assertSee('Twój profil został pomyślnie skonfigurowany');

            // Verify data saved
            $user->refresh();
            $this->assertEquals('JanKowalski', $user->nickname);
            $this->assertEquals('Warszawa, Polska', $user->home_location);
            $this->assertTrue($user->hasCompletedOnboarding());

            $preferences = $user->preferences;
            $this->assertNotNull($preferences);
            $this->assertContains('historia_kultura', $preferences->interests_categories);
            $this->assertEquals('umiarkowane', $preferences->travel_pace);
            $this->assertEquals('standardowy', $preferences->budget_level);
            $this->assertEquals('mix', $preferences->transport_preference);
            $this->assertEquals('brak', $preferences->restrictions);
        });
    }

    /**
     * Test validation on step 1.
     */
    public function test_step_1_requires_nickname_and_location(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/onboarding')
                ->waitFor('input[wire\\:model\\.live="nickname"]', 5);

            // Try to proceed without filling fields
            $browser->press('Dalej')
                ->pause(1500)
                // Should still be on step 1 due to validation
                ->assertSee('Krok 1');
        });
    }

    /**
     * Test that completed user cannot access onboarding.
     */
    public function test_completed_user_redirected_from_onboarding(): void
    {
        $user = User::factory()
            ->hasPreferences()
            ->create([
                'nickname' => 'TestUser',
                'home_location' => 'Warszawa',
                'onboarding_completed' => true,
                'onboarding_completed_at' => now(),
            ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/onboarding')
                ->waitForLocation('/dashboard', 5)
                ->assertPathIs('/dashboard');
        });
    }
}
