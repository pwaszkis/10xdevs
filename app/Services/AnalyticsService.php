<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\UserEvent;
use Illuminate\Support\Facades\DB;

/**
 * Analytics Service
 *
 * Handles user event tracking and analytics calculations.
 */
class AnalyticsService
{
    /**
     * Track an event for a user.
     *
     * @param  array<string, mixed>|null  $eventData
     */
    public function trackEvent(string $eventType, ?int $userId = null, ?array $eventData = null): UserEvent
    {
        return UserEvent::log($eventType, $userId, $eventData);
    }

    /**
     * Calculate onboarding completion rate for the last N days.
     *
     * @return array{total_users: int, completed_users: int, completion_rate: float}
     */
    public function getOnboardingCompletionRate(int $days = 30): array
    {
        $startDate = now()->subDays($days);

        // Get total users created in the period
        $totalUsers = User::where('created_at', '>=', $startDate)->count();

        // Get users who completed onboarding in the period
        $completedUsers = User::where('created_at', '>=', $startDate)
            ->where('onboarding_completed', true)
            ->count();

        $completionRate = $totalUsers > 0 ? ($completedUsers / $totalUsers) * 100 : 0;

        return [
            'total_users' => $totalUsers,
            'completed_users' => $completedUsers,
            'completion_rate' => round($completionRate, 2),
        ];
    }

    /**
     * Get onboarding funnel data (step-by-step completion).
     *
     * @return array<string, mixed>
     */
    public function getOnboardingFunnel(int $days = 30): array
    {
        $startDate = now()->subDays($days);

        // Get users who started onboarding
        $usersStarted = UserEvent::where('event_type', UserEvent::EVENT_ONBOARDING_STARTED)
            ->where('created_at', '>=', $startDate)
            ->distinct('user_id')
            ->count('user_id');

        // Get users who completed at least one step
        $usersStepCompleted = UserEvent::where('event_type', UserEvent::EVENT_ONBOARDING_STEP_COMPLETED)
            ->where('created_at', '>=', $startDate)
            ->distinct('user_id')
            ->count('user_id');

        // Get users who completed onboarding
        $usersCompleted = UserEvent::where('event_type', UserEvent::EVENT_ONBOARDING_COMPLETED)
            ->where('created_at', '>=', $startDate)
            ->distinct('user_id')
            ->count('user_id');

        // Get step completion breakdown
        $stepCompletions = UserEvent::where('event_type', UserEvent::EVENT_ONBOARDING_STEP_COMPLETED)
            ->where('created_at', '>=', $startDate)
            ->select(DB::raw('JSON_EXTRACT(event_data, "$.step") as step'), DB::raw('COUNT(DISTINCT user_id) as users'))
            ->groupBy('step')
            ->get()
            ->mapWithKeys(function (mixed $item): array {
                // @phpstan-ignore-next-line
                return [(int) $item->step => (int) $item->users];
            })
            ->toArray();

        return [
            'users_started' => $usersStarted,
            'users_step_completed' => $usersStepCompleted,
            'users_completed' => $usersCompleted,
            'step_completions' => $stepCompletions,
            'completion_rate' => $usersStarted > 0 ? round(($usersCompleted / $usersStarted) * 100, 2) : 0,
        ];
    }

    /**
     * Get user engagement metrics.
     *
     * @return array<string, mixed>
     */
    public function getUserEngagementMetrics(int $days = 30): array
    {
        $startDate = now()->subDays($days);

        // Monthly Active Users (MAU)
        $mau = UserEvent::where('created_at', '>=', $startDate)
            ->distinct('user_id')
            ->count('user_id');

        // Plans created
        $plansCreated = UserEvent::where('event_type', UserEvent::EVENT_PLAN_CREATED)
            ->where('created_at', '>=', $startDate)
            ->count();

        // AI generations
        $aiGenerations = UserEvent::where('event_type', UserEvent::EVENT_AI_GENERATED)
            ->where('created_at', '>=', $startDate)
            ->count();

        // PDF exports
        $pdfExports = UserEvent::where('event_type', UserEvent::EVENT_PDF_EXPORTED)
            ->where('created_at', '>=', $startDate)
            ->count();

        // Average plans per active user
        $avgPlansPerUser = $mau > 0 ? round($plansCreated / $mau, 2) : 0;

        return [
            'monthly_active_users' => $mau,
            'plans_created' => $plansCreated,
            'ai_generations' => $aiGenerations,
            'pdf_exports' => $pdfExports,
            'avg_plans_per_user' => $avgPlansPerUser,
        ];
    }

    /**
     * Get user retention data (percentage of users who return after N days).
     *
     * @return array<string, mixed>
     */
    public function getUserRetention(int $cohortDays = 30, int $retentionDays = 7): array
    {
        $cohortStart = now()->subDays($cohortDays + $retentionDays);
        $cohortEnd = now()->subDays($retentionDays);

        // Get users who signed up in the cohort period
        $cohortUsers = User::whereBetween('created_at', [$cohortStart, $cohortEnd])
            ->pluck('id');

        $cohortSize = $cohortUsers->count();

        if ($cohortSize === 0) {
            return [
                'cohort_size' => 0,
                'retained_users' => 0,
                'retention_rate' => 0,
            ];
        }

        // Get users who were active in the retention period
        $retainedUsers = UserEvent::whereIn('user_id', $cohortUsers)
            ->where('created_at', '>', $cohortEnd)
            ->distinct('user_id')
            ->count('user_id');

        $retentionRate = round(($retainedUsers / $cohortSize) * 100, 2);

        return [
            'cohort_size' => $cohortSize,
            'retained_users' => $retainedUsers,
            'retention_rate' => $retentionRate,
        ];
    }

    /**
     * Get event distribution for analytics.
     *
     * @return array<string, int>
     */
    public function getEventDistribution(int $days = 30): array
    {
        $startDate = now()->subDays($days);

        return UserEvent::where('created_at', '>=', $startDate)
            ->select('event_type', DB::raw('COUNT(*) as count'))
            ->groupBy('event_type')
            ->pluck('count', 'event_type')
            ->toArray();
    }
}
