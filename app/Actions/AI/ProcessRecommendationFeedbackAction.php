<?php

declare(strict_types=1);

namespace App\Actions\AI;

use App\Models\AIRecommendation;
use Illuminate\Support\Facades\DB;

/**
 * Process Recommendation Feedback Action
 *
 * Handles user feedback on AI recommendations.
 */
class ProcessRecommendationFeedbackAction
{
    /**
     * Execute the action.
     *
     * @param  string  $feedback  (accepted, rejected, modified)
     * @param  array<string, mixed>  $metadata
     */
    public function execute(AIRecommendation $recommendation, string $feedback, array $metadata = []): AIRecommendation
    {
        return DB::transaction(function () use ($recommendation, $feedback, $metadata) {
            $recommendation->update([
                'status' => $feedback === 'accepted' ? 'accepted' : 'rejected',
                'feedback' => [
                    'type' => $feedback,
                    'metadata' => $metadata,
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);

            return $recommendation;
        });
    }
}
