<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TravelPlanFeedback;

class FeedbackService
{
    /**
     * Calculate the overall satisfaction rate across all feedback.
     *
     * @return float Satisfaction rate as a percentage (0-100)
     */
    public function getSatisfactionRate(): float
    {
        $total = TravelPlanFeedback::count();

        if ($total === 0) {
            return 0.0;
        }

        $satisfied = TravelPlanFeedback::where('satisfied', true)->count();

        return round(($satisfied / $total) * 100, 1);
    }

    /**
     * Calculate the satisfaction rate for a specific user.
     *
     * @param  int  $userId  User ID
     * @return float Satisfaction rate as a percentage (0-100)
     */
    public function getUserSatisfactionRate(int $userId): float
    {
        $total = TravelPlanFeedback::whereHas('travelPlan', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->count();

        if ($total === 0) {
            return 0.0;
        }

        $satisfied = TravelPlanFeedback::where('satisfied', true)
            ->whereHas('travelPlan', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->count();

        return round(($satisfied / $total) * 100, 1);
    }

    /**
     * Get the most common issues reported in feedback.
     *
     * @param  int|null  $limit  Number of top issues to return
     * @return array<string, int> Issue type => count
     */
    public function getTopIssues(?int $limit = 5): array
    {
        $feedbacks = TravelPlanFeedback::where('satisfied', false)
            ->whereNotNull('issues')
            ->get();

        $issueCounts = [];

        foreach ($feedbacks as $feedback) {
            if (! is_array($feedback->issues)) {
                continue;
            }

            foreach ($feedback->issues as $issue) {
                // Filter out 'other: ...' comments and count only base issue types
                $issueType = str_starts_with($issue, 'other:') ? 'other' : $issue;

                if (! isset($issueCounts[$issueType])) {
                    $issueCounts[$issueType] = 0;
                }

                $issueCounts[$issueType]++;
            }
        }

        arsort($issueCounts);

        if ($limit !== null) {
            return array_slice($issueCounts, 0, $limit, true);
        }

        return $issueCounts;
    }

    /**
     * Get satisfaction statistics.
     *
     * @return array{total: int, satisfied: int, unsatisfied: int, rate: float}
     */
    public function getStatistics(): array
    {
        $total = TravelPlanFeedback::count();
        $satisfied = TravelPlanFeedback::where('satisfied', true)->count();
        $unsatisfied = $total - $satisfied;

        return [
            'total' => $total,
            'satisfied' => $satisfied,
            'unsatisfied' => $unsatisfied,
            'rate' => $this->getSatisfactionRate(),
        ];
    }
}
