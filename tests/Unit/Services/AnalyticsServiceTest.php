<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\UserEvent;
use App\Services\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private AnalyticsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AnalyticsService;
    }

    public function test_tracks_event(): void
    {
        $user = User::factory()->create();

        $event = $this->service->trackEvent(
            UserEvent::EVENT_ONBOARDING_STARTED,
            $user->id,
            ['test' => 'data']
        );

        $this->assertInstanceOf(UserEvent::class, $event);
        $this->assertEquals(UserEvent::EVENT_ONBOARDING_STARTED, $event->event_type);
        $this->assertEquals($user->id, $event->user_id);
    }

    public function test_calculates_onboarding_completion_rate(): void
    {
        $users = User::factory()->count(10)->create([
            'created_at' => now()->subDays(5),
        ]);

        // 7 users completed onboarding
        foreach ($users->take(7) as $user) {
            $user->update(['onboarding_completed' => true]);
        }

        $result = $this->service->getOnboardingCompletionRate(30);

        $this->assertEquals(10, $result['total_users']);
        $this->assertEquals(7, $result['completed_users']);
        $this->assertEquals(70.0, $result['completion_rate']);
    }

    public function test_tracks_onboarding_funnel(): void
    {
        $user = User::factory()->create();

        UserEvent::log(UserEvent::EVENT_ONBOARDING_STARTED, $user->id);
        UserEvent::log(UserEvent::EVENT_ONBOARDING_STEP_COMPLETED, $user->id, ['step' => 1]);
        UserEvent::log(UserEvent::EVENT_ONBOARDING_STEP_COMPLETED, $user->id, ['step' => 2]);
        UserEvent::log(UserEvent::EVENT_ONBOARDING_COMPLETED, $user->id);

        $funnel = $this->service->getOnboardingFunnel(30);

        $this->assertEquals(1, $funnel['users_started']);
        $this->assertEquals(1, $funnel['users_completed']);
        $this->assertEquals(100.0, $funnel['completion_rate']);
    }
}
