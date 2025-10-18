<?php

declare(strict_types=1);

namespace Tests\Browser\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Registration Flow Test
 *
 * @group critical
 */
class RegistrationTest extends DuskTestCase
{
    use DatabaseTruncation;

    /**
     * Test that new user can register successfully.
     */
    public function test_new_user_can_register(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                ->assertSee('Imię')
                ->type('name', 'Jan Kowalski')
                ->type('email', 'jan.kowalski@example.com')
                ->type('password', 'SecurePassword123!')
                ->type('password_confirmation', 'SecurePassword123!')
                ->press('ZAREJESTRUJ SIĘ')
                ->waitForText('Dziękujemy za rejestrację', 10)
                ->assertSee('zweryfikuj swój adres email');

            // Verify user was created in database
            $this->assertDatabaseHas('users', [
                'email' => 'jan.kowalski@example.com',
                'name' => 'Jan Kowalski',
            ]);
        });
    }

    /**
     * Test that registration validates required fields.
     */
    public function test_registration_validates_required_fields(): void
    {
        $this->browse(function (Browser $browser) {
            // Remove HTML5 validation to test server-side validation
            $browser->visit('/register')
                ->script("document.querySelector('form').setAttribute('novalidate', 'novalidate')");

            $browser->press('ZAREJESTRUJ SIĘ')
                ->pause(1000)
                ->assertPresent('.text-red-600');
        });
    }

    /**
     * Test that registration validates email format.
     */
    public function test_registration_validates_email_format(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                ->script("document.querySelector('form').setAttribute('novalidate', 'novalidate')");

            $browser->type('name', 'Jan Kowalski')
                ->type('email', 'invalid-email')
                ->type('password', 'SecurePassword123!')
                ->type('password_confirmation', 'SecurePassword123!')
                ->press('ZAREJESTRUJ SIĘ')
                ->pause(1000)
                ->assertPresent('.text-red-600');
        });
    }

    /**
     * Test that registration validates password confirmation.
     */
    public function test_registration_validates_password_confirmation(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                ->script("document.querySelector('form').setAttribute('novalidate', 'novalidate')");

            $browser->type('name', 'Jan Kowalski')
                ->type('email', 'jan@example.com')
                ->type('password', 'SecurePassword123!')
                ->type('password_confirmation', 'DifferentPassword123!')
                ->press('ZAREJESTRUJ SIĘ')
                ->pause(1000)
                ->assertPresent('.text-red-600');
        });
    }

    /**
     * Test that duplicate email is rejected.
     */
    public function test_duplicate_email_is_rejected(): void
    {
        // Create existing user
        User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                ->script("document.querySelector('form').setAttribute('novalidate', 'novalidate')");

            $browser->type('name', 'Jan Kowalski')
                ->type('email', 'existing@example.com')
                ->type('password', 'SecurePassword123!')
                ->type('password_confirmation', 'SecurePassword123!')
                ->press('ZAREJESTRUJ SIĘ')
                ->pause(1000)
                ->assertPresent('.text-red-600');
        });
    }
}
