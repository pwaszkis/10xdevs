<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitFeedbackRequest;
use App\Models\TravelPlan;
use App\Models\TravelPlanFeedback;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class TravelPlanFeedbackController extends Controller
{
    use AuthorizesRequests;
    /**
     * Submit or update feedback for a travel plan.
     */
    public function store(SubmitFeedbackRequest $request, TravelPlan $plan): JsonResponse
    {
        try {
            // Authorization is handled in SubmitFeedbackRequest

            // Get sanitized data
            $data = array_merge(
                $request->sanitized(),
                ['travel_plan_id' => $plan->id]
            );

            // Create or update feedback
            $feedback = TravelPlanFeedback::updateOrCreate(
                ['travel_plan_id' => $plan->id],
                $data
            );

            // Track analytics event
            $this->trackFeedbackEvent($plan, $feedback);

            return response()->json([
                'success' => true,
                'message' => 'Dziękujemy za feedback!',
                'data' => [
                    'id' => $feedback->id,
                    'satisfied' => $feedback->satisfied,
                    'issues' => $feedback->issues,
                    'created_at' => $feedback->created_at->toISOString(),
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to submit feedback', [
                'plan_id' => $plan->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Nie udało się zapisać feedbacku. Spróbuj ponownie.',
            ], 500);
        }
    }

    /**
     * Get feedback for a travel plan.
     */
    public function show(TravelPlan $plan): JsonResponse
    {
        // Check authorization: plan belongs to user
        if ($plan->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $feedback = $plan->feedback;

        if (! $feedback) {
            return response()->json([
                'success' => false,
                'message' => 'Brak feedbacku dla tego planu.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $feedback->id,
                'satisfied' => $feedback->satisfied,
                'issues' => $feedback->issues,
                'formatted_issues' => $feedback->getFormattedIssues(),
                'created_at' => $feedback->created_at->toISOString(),
                'updated_at' => $feedback->updated_at->toISOString(),
            ],
        ]);
    }

    /**
     * Delete feedback for a travel plan.
     */
    public function destroy(TravelPlan $plan): JsonResponse
    {
        // Check authorization: plan belongs to user
        if ($plan->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $feedback = $plan->feedback;

        if (! $feedback) {
            return response()->json([
                'success' => false,
                'message' => 'Brak feedbacku do usunięcia.',
            ], 404);
        }

        $feedback->delete();

        return response()->json([
            'success' => true,
            'message' => 'Feedback został usunięty.',
        ]);
    }

    /**
     * Track feedback event for analytics.
     */
    private function trackFeedbackEvent(TravelPlan $plan, TravelPlanFeedback $feedback): void
    {
        // TODO: Implement analytics tracking when analytics system is set up
        // This could be integrated with Google Analytics, Plausible, or custom DB tracking
        // as defined in PRD section 3.14

        Log::info('Feedback submitted', [
            'plan_id' => $plan->id,
            'user_id' => $plan->user_id,
            'satisfied' => $feedback->satisfied,
            'has_issues' => ! empty($feedback->issues),
        ]);
    }
}
