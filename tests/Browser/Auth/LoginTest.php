<?php

declare(strict_types=1);

namespace Tests\Browser\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Login Flow Test
 *
 * @group critical
 */
class LoginTest extends DuskTestCase
{
    use DatabaseTruncation;

    /**
     * Test that existing user can log in.
     */
    public function test_user_can_login(): void
    {
        // Create user with completed profile
        $user = User::factory()
            ->hasPreferences()
            ->create([
                'email' => 'test@example.com',
                'password' => bcrypt('password123'),
                'nickname' => 'TestUser',
                'home_location' => 'Warszawa, Polska',
                'onboarding_completed' => true,
                'onboarding_completed_at' => now(),
            ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/login')
                ->type('email', 'test@example.com')
                ->type('password', 'password123')
                ->press('ZALOGUJ SIĘ')
                ->waitForLocation('/dashboard', 10)
                ->assertPathIs('/dashboard')
                ->assertSee('Witaj')
                ->assertAuthenticatedAs($user);
        });
    }

    /**
     * Test that login fails with invalid credentials.
     */
    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('email', 'test@example.com')
                ->type('password', 'wrong-password')
                ->press('ZALOGUJ SIĘ')
                ->waitFor('.text-red-600', 5)
                ->assertSee('Podane dane logowania są nieprawidłowe');
        });
    }

    /**
     * Test that login requires email.
     */
    public function test_login_requires_email(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->script("document.querySelector('form').setAttribute('novalidate', 'novalidate')");

            $browser->type('password', 'password123')
                ->press('ZALOGUJ SIĘ')
                ->pause(1000)
                ->assertPresent('.text-red-600');
        });
    }

    /**
     * Test that login requires password.
     */
    public function test_login_requires_password(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->script("document.querySelector('form').setAttribute('novalidate', 'novalidate')");

            $browser->type('email', 'test@example.com')
                ->press('ZALOGUJ SIĘ')
                ->pause(1000)
                ->assertPresent('.text-red-600');
        });
    }

    /**
     * Test remember me functionality.
     */
    public function test_remember_me_checkbox_works(): void
    {
        $user = User::factory()
            ->hasPreferences()
            ->create([
                'email' => 'test@example.com',
                'password' => bcrypt('password123'),
                'nickname' => 'TestUser',
                'home_location' => 'Warszawa, Polska',
                'onboarding_completed' => true,
                'onboarding_completed_at' => now(),
            ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('email', 'test@example.com')
                ->type('password', 'password123')
                ->check('remember')
                ->press('ZALOGUJ SIĘ')
                ->waitForLocation('/dashboard', 10)
                ->assertPathIs('/dashboard');

            // Verify remember token was set
            $this->assertNotNull(
                User::where('email', 'test@example.com')->first()->remember_token
            );
        });
    }
}
