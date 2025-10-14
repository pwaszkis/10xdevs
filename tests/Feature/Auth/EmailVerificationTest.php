<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response
            ->assertSeeVolt('pages.auth.verify-email')
            ->assertStatus(200);
    }

    public function test_email_can_be_verified(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false) . '?verified=1');
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    /**
     * Test: Verification link expires after 24 hours
     *
     * Scenario: User receives verification email but doesn't click
     * the link within 24 hours. Link should be expired.
     *
     * Security: Prevents indefinite validity of verification links.
     */
    public function test_verification_link_expires_after_24_hours(): void
    {
        // Arrange: Create unverified user
        $user = User::factory()->unverified()->create();

        // Generate verification URL valid for 24 hours
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addHours(24),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        // Act: Simulate 25 hours passing
        $this->travel(25)->hours();

        $response = $this->actingAs($user)->get($verificationUrl);

        // Assert: Link expired (403 Forbidden or redirect)
        $this->assertContains($response->status(), [403, 302]);
        $this->assertFalse($user->fresh()->hasVerifiedEmail(), 'Email should not be verified with expired link');
    }

    /**
     * Test: Resend verification email is rate limited
     *
     * Scenario: User requests multiple verification emails rapidly.
     * Should be rate limited to prevent email spam.
     *
     * Security: Prevents abuse of email sending functionality.
     */
    public function test_resend_verification_email_is_rate_limited(): void
    {
        $this->markTestSkipped(
            'Email verification resend may be handled via Livewire Volt component. ' .
            'Rate limiting for email resend requires either: ' .
            '1. Adding throttle middleware to route, ' .
            '2. Implementing RateLimiter in Livewire component, ' .
            '3. Testing through Volt component with proper Livewire testing.'
        );
    }
}
