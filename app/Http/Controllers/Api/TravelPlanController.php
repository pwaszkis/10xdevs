<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TravelPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TravelPlanController extends Controller
{
    /**
     * Get travel plan details with optional relationships.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $includes = explode(',', $request->query('include', ''));
        $query = TravelPlan::query();

        // Handle nested includes (e.g., "days.points")
        $relationships = $this->parseIncludes($includes);

        if (! empty($relationships)) {
            $query->with($relationships);
        }

        $plan = $query->find($id);

        if (! $plan) {
            return response()->json([
                'message' => 'Travel plan not found.',
            ], 404);
        }

        if ($plan->user_id !== auth()->id()) {
            return response()->json(['message' => 'This plan does not belong to you.'], 403);
        }

        $data = [
            'id' => $plan->id,
            'title' => $plan->title,
            'destination' => $plan->destination,
            'destination_lat' => $plan->destination_lat,
            'destination_lng' => $plan->destination_lng,
            'departure_date' => $plan->departure_date->format('Y-m-d'),
            'number_of_days' => $plan->number_of_days,
            'number_of_people' => $plan->number_of_people,
            'budget_per_person' => $plan->budget_per_person,
            'budget_currency' => $plan->budget_currency,
            'user_notes' => $plan->user_notes,
            'status' => $plan->status,
            'has_ai_plan' => $plan->has_ai_plan,
            'created_at' => $plan->created_at->toIso8601String(),
            'updated_at' => $plan->updated_at->toIso8601String(),
        ];

        // Add days if requested
        if ($plan->relationLoaded('days')) {
            $data['days'] = $plan->days->map(function ($day) {
                $dayData = [
                    'id' => $day->id,
                    'travel_plan_id' => $day->travel_plan_id,
                    'day_number' => $day->day_number,
                    'date' => $day->date->format('Y-m-d'),
                    'summary' => $day->summary,
                    'created_at' => $day->created_at->toIso8601String(),
                ];

                // Add points if requested
                if ($day->relationLoaded('points')) {
                    $dayData['points'] = $day->points->map(function ($point) {
                        return [
                            'id' => $point->id,
                            'plan_day_id' => $point->plan_day_id,
                            'order_number' => $point->order_number,
                            'day_part' => $point->day_part,
                            'name' => $point->name,
                            'description' => $point->description,
                            'justification' => $point->justification,
                            'duration_minutes' => $point->duration_minutes,
                            'google_maps_url' => $point->google_maps_url,
                            'location_lat' => $point->location_lat,
                            'location_lng' => $point->location_lng,
                            'created_at' => $point->created_at->toIso8601String(),
                        ];
                    })->values()->all();
                }

                return $dayData;
            })->values()->all();
        }

        // Add feedback if requested
        if ($plan->relationLoaded('feedback')) {
            $data['feedback'] = $plan->feedback ? [
                'id' => $plan->feedback->id,
                'travel_plan_id' => $plan->feedback->travel_plan_id,
                'satisfied' => $plan->feedback->satisfied,
                'issues' => $plan->feedback->issues,
                'created_at' => $plan->feedback->created_at->toIso8601String(),
            ] : null;
        }

        return response()->json(['data' => $data]);
    }

    /**
     * Delete travel plan (soft delete).
     */
    public function destroy(int $id): JsonResponse
    {
        $plan = TravelPlan::find($id);

        if (! $plan) {
            return response()->json([
                'message' => 'Travel plan not found.',
            ], 404);
        }

        if ($plan->user_id !== auth()->id()) {
            return response()->json(['message' => 'This plan does not belong to you.'], 403);
        }

        $plan->delete();

        return response()->json([
            'message' => 'Travel plan deleted successfully.',
        ]);
    }

    /**
     * Generate or regenerate AI travel plan.
     */
    public function generate(int $id): JsonResponse
    {
        // TEMPORARY: Return mock response for AI generation
        // TODO: Implement actual AI generation logic

        $plan = TravelPlan::find($id);

        if (! $plan) {
            return response()->json([
                'message' => 'Travel plan not found.',
            ], 404);
        }

        // TEMPORARY: Mock response
        return response()->json([
            'message' => 'AI generation started. Check status for progress.',
            'data' => [
                'generation_id' => rand(1, 1000),
                'travel_plan_id' => $id,
                'status' => 'pending',
                'started_at' => null,
                'estimated_duration_seconds' => 30,
            ],
        ], 202);
    }

    /**
     * Check AI generation status.
     */
    public function generationStatus(int $id): JsonResponse
    {
        // TEMPORARY: Return mock response for generation status
        // TODO: Implement actual status checking logic

        $plan = TravelPlan::find($id);

        if (! $plan) {
            return response()->json([
                'message' => 'Travel plan not found.',
            ], 404);
        }

        // TEMPORARY: Mock response - always return completed for now
        return response()->json([
            'data' => [
                'generation_id' => rand(1, 1000),
                'travel_plan_id' => $id,
                'status' => 'completed',
                'model_used' => 'gpt-4o-mini',
                'tokens_used' => 1250,
                'cost_usd' => 0.0375,
                'started_at' => now()->subSeconds(45)->toIso8601String(),
                'completed_at' => now()->toIso8601String(),
                'duration_seconds' => 45,
            ],
        ]);
    }

    /**
     * Export travel plan to PDF.
     */
    public function exportPdf(int $id): Response|JsonResponse
    {
        // TEMPORARY: Return error for now
        // TODO: Implement PDF export using Spatie Laravel PDF

        $plan = TravelPlan::find($id);

        if (! $plan) {
            return response()->json([
                'message' => 'Travel plan not found.',
            ], 404);
        }

        if (! $plan->has_ai_plan) {
            return response()->json([
                'message' => 'Cannot export draft plan. Please generate AI plan first.',
            ], 400);
        }

        // TEMPORARY: Return error
        return response()->json([
            'message' => 'PDF export not yet implemented.',
        ], 501);
    }

    /**
     * Parse include parameter into array of relationships.
     * Handles nested relationships like "days.points".
     *
     * @param  array<int, string>  $includes
     * @return array<int, string>
     */
    private function parseIncludes(array $includes): array
    {
        $relationships = [];

        foreach ($includes as $include) {
            $include = trim($include);
            if (empty($include)) {
                continue;
            }

            // Handle nested relationships
            if (str_contains($include, '.')) {
                // For "days.points", we need to load both "days" and "days.points"
                $parts = explode('.', $include);
                $nested = $parts[0];

                for ($i = 1; $i < count($parts); $i++) {
                    $nested .= '.'.$parts[$i];
                    $relationships[] = $nested;
                }
            } else {
                $relationships[] = $include;
            }
        }

        return array_unique($relationships);
    }
}
