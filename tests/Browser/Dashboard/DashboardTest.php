<?php

declare(strict_types=1);

namespace Tests\Browser\Dashboard;

use App\Models\TravelPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Dashboard Browser Test
 *
 * @group critical
 */
class DashboardTest extends DuskTestCase
{
    use DatabaseTruncation;

    /**
     * Test that dashboard shows welcome message with user nickname.
     */
    public function test_dashboard_shows_welcome_message(): void
    {
        $user = User::factory()
            ->hasPreferences()
            ->create([
                'nickname' => 'JanTest',
                'home_location' => 'Warszawa',
                'onboarding_completed' => true,
                'onboarding_completed_at' => now(),
            ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/dashboard')
                ->assertSee('Cześć JanTest!')
                ->assertSee('Zaplanuj swoją kolejną przygodę');
        });
    }

    /**
     * Test that dashboard shows create plan button.
     */
    public function test_dashboard_shows_create_plan_button(): void
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
                ->visit('/dashboard');

            // Should have link to create plan (either in header or in empty state)
            $browser->assertPresent('a[href*="/plans/create"]');

            // Click button should navigate to create page
            $browser->click('a[href*="/plans/create"]')
                ->waitForLocation('/plans/create', 5)
                ->assertPathIs('/plans/create');
        });
    }

    /**
     * Test that dashboard shows empty state when no plans.
     */
    public function test_dashboard_shows_empty_state_without_plans(): void
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
                ->visit('/dashboard')
                ->assertSee('Nie masz jeszcze żadnych planów');
        });
    }

    /**
     * Test that dashboard displays user's travel plans.
     */
    public function test_dashboard_displays_users_travel_plans(): void
    {
        $user = User::factory()
            ->hasPreferences()
            ->create([
                'nickname' => 'TestUser',
                'home_location' => 'Warszawa',
                'onboarding_completed' => true,
                'onboarding_completed_at' => now(),
            ]);

        // Create some travel plans
        TravelPlan::factory()->create([
            'user_id' => $user->id,
            'title' => 'Wakacje w Rzymie',
            'destination' => 'Rzym, Włochy',
            'status' => 'planned',
        ]);

        TravelPlan::factory()->create([
            'user_id' => $user->id,
            'title' => 'Weekend w Krakowie',
            'destination' => 'Kraków, Polska',
            'status' => 'draft',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/dashboard')
                ->assertSee('Wakacje w Rzymie')
                ->assertSee('Weekend w Krakowie')
                ->assertSee('Rzym, Włochy')
                ->assertSee('Kraków, Polska');
        });
    }

    /**
     * Test that dashboard filter buttons work.
     */
    public function test_dashboard_filter_buttons_work(): void
    {
        $user = User::factory()
            ->hasPreferences()
            ->create([
                'nickname' => 'TestUser',
                'home_location' => 'Warszawa',
                'onboarding_completed' => true,
                'onboarding_completed_at' => now(),
            ]);

        // Create plans with different statuses
        TravelPlan::factory()->create([
            'user_id' => $user->id,
            'title' => 'Plan szkicowy',
            'status' => 'draft',
        ]);

        TravelPlan::factory()->create([
            'user_id' => $user->id,
            'title' => 'Plan zaplanowany',
            'status' => 'planned',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/dashboard');

            // Initially should show all plans
            $browser->assertSee('Plan szkicowy')
                ->assertSee('Plan zaplanowany');

            // Click "SZKICE" filter (buttons use uppercase)
            $browser->press('SZKICE')
                ->pause(1000)
                ->assertSee('Plan szkicowy');

            // Click "ZAPLANOWANE" filter
            $browser->press('ZAPLANOWANE')
                ->pause(1000)
                ->assertSee('Plan zaplanowany');

            // Click "WSZYSTKIE" filter
            $browser->press('WSZYSTKIE')
                ->pause(1000)
                ->assertSee('Plan szkicowy')
                ->assertSee('Plan zaplanowany');
        });
    }

    /**
     * Test that dashboard shows AI generation limit.
     */
    public function test_dashboard_shows_ai_generation_limit(): void
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
                ->visit('/dashboard')
                ->assertSee('GENEROWANIA AI');
        });
    }
}
