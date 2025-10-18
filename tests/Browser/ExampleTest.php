<?php

declare(strict_types=1);

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Example Dusk Test
 *
 * This test verifies the basic setup is working.
 */
class ExampleTest extends DuskTestCase
{
    /**
     * Test that the welcome page loads.
     */
    public function test_welcome_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->assertSee('VibeTravels')
                ->assertSee('Zaloguj się')
                ->pause(1000);  // Pause for 1 second to see the page
        });
    }

    /**
     * Test that login page loads.
     */
    public function test_login_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->assertSee('Email')
                ->assertSee('Hasło')
                ->assertSee('ZALOGUJ SIĘ')
                ->pause(1000);
        });
    }
}
