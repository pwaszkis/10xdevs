<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\TravelPlan;
use App\Models\User;
use App\Models\UserEvent;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_preferences_relationship(): void
    {
        $user = User::factory()->create();
        $preferences = UserPreference::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(UserPreference::class, $user->preferences);
        $this->assertEquals($preferences->id, $user->preferences->id);
    }

    /** @test */
    public function it_has_travel_plans_relationship(): void
    {
        $user = User::factory()->create();
        $plan = TravelPlan::factory()->create(['user_id' => $user->id]);

        $this->assertCount(1, $user->travelPlans);
        $this->assertEquals($plan->id, $user->travelPlans->first()->id);
    }

    /** @test */
    public function it_has_user_events_relationship(): void
    {
        $user = User::factory()->create();
        UserEvent::create([
            'user_id' => $user->id,
            'event_type' => UserEvent::EVENT_LOGIN,
            'event_data' => ['foo' => 'bar'],
        ]);

        $this->assertCount(1, $user->userEvents);
        $this->assertEquals(UserEvent::EVENT_LOGIN, $user->userEvents->first()->event_type);
    }

    /** @test */
    public function has_completed_onboarding_returns_true_when_onboarding_completed(): void
    {
        $user = User::factory()->create([
            'onboarding_completed' => true,
            'onboarding_completed_at' => now(),
        ]);

        $this->assertTrue($user->hasCompletedOnboarding());
    }

    /** @test */
    public function has_completed_onboarding_returns_false_when_not_completed(): void
    {
        $user = User::factory()->create([
            'onboarding_completed' => false,
        ]);

        $this->assertFalse($user->hasCompletedOnboarding());
    }

    /** @test */
    public function needs_onboarding_returns_true_when_not_completed(): void
    {
        $user = User::factory()->create([
            'onboarding_completed' => false,
        ]);

        $this->assertTrue($user->needsOnboarding());
    }

    /** @test */
    public function needs_onboarding_returns_false_when_completed(): void
    {
        $user = User::factory()->create([
            'onboarding_completed' => true,
        ]);

        $this->assertFalse($user->needsOnboarding());
    }

    /** @test */
    public function has_completed_profile_checks_all_required_fields(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'nickname' => 'tester',
            'home_location' => 'Warsaw',
            'onboarding_completed' => true,
            'onboarding_completed_at' => now(),
        ]);

        UserPreference::factory()->create([
            'user_id' => $user->id,
            'interests_categories' => ['historia_kultura', 'przyroda_outdoor'],
            'travel_pace' => 'spokojne',
            'budget_level' => 'ekonomiczny',
            'transport_preference' => 'pieszo_publiczny',
            'restrictions' => 'brak',
        ]);

        $this->assertTrue($user->hasCompletedProfile());
    }

    /** @test */
    public function has_completed_profile_returns_false_when_missing_nickname(): void
    {
        $user = User::factory()->create([
            'nickname' => null,
            'home_location' => 'Warsaw',
            'onboarding_completed' => true,
        ]);

        UserPreference::factory()->create(['user_id' => $user->id]);

        $this->assertFalse($user->hasCompletedProfile());
    }

    /** @test */
    public function has_completed_profile_returns_false_when_missing_home_location(): void
    {
        $user = User::factory()->create([
            'nickname' => 'tester',
            'home_location' => null,
            'onboarding_completed' => true,
        ]);

        UserPreference::factory()->create(['user_id' => $user->id]);

        $this->assertFalse($user->hasCompletedProfile());
    }

    /** @test */
    public function has_completed_profile_returns_false_when_preferences_missing(): void
    {
        $user = User::factory()->create([
            'nickname' => 'tester',
            'home_location' => 'Warsaw',
            'onboarding_completed' => true,
        ]);

        // No preferences created

        $this->assertFalse($user->hasCompletedProfile());
    }

    /** @test */
    public function has_completed_profile_returns_false_when_onboarding_not_completed(): void
    {
        $user = User::factory()->create([
            'nickname' => 'tester',
            'home_location' => 'Warsaw',
            'onboarding_completed' => false,
        ]);

        UserPreference::factory()->create(['user_id' => $user->id]);

        $this->assertFalse($user->hasCompletedProfile());
    }

    /** @test */
    public function user_can_be_soft_deleted(): void
    {
        $user = User::factory()->create();
        $userId = $user->id;

        $user->delete();

        $this->assertSoftDeleted('users', ['id' => $userId]);
    }

    /** @test */
    public function user_email_is_hidden_from_array(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
    }

    /** @test */
    public function is_admin_returns_false_by_default(): void
    {
        $user = User::factory()->create();

        // Default implementation always returns false (to be implemented later)
        $this->assertFalse($user->isAdmin());
    }

    /** @test */
    public function user_fillable_attributes_are_set_correctly(): void
    {
        $user = User::factory()->make([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'nickname' => 'tester',
            'home_location' => 'Warsaw',
        ]);

        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertEquals('tester', $user->nickname);
        $this->assertEquals('Warsaw', $user->home_location);
    }

    /** @test */
    public function user_casts_email_verified_at_to_datetime(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $user->email_verified_at);
    }

    /** @test */
    public function user_has_correct_table_name(): void
    {
        $user = new User;

        $this->assertEquals('users', $user->getTable());
    }
}
