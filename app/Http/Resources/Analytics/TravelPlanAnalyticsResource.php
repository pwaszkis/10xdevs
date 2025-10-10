<?php

declare(strict_types=1);

namespace App\Http\Resources\Analytics;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Travel Plan Analytics Resource
 *
 * Transforms travel plan analytics data for API responses.
 *
 * @property array<string, mixed> $resource
 */
class TravelPlanAnalyticsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'travel_plan_id' => $this->resource['travel_plan_id'] ?? null,
            'total_activities' => $this->resource['total_activities'] ?? 0,
            'activities_by_type' => $this->resource['activities_by_type'] ?? [],
            'total_estimated_cost' => $this->resource['total_estimated_cost'] ?? 0,
            'currency' => $this->resource['currency'] ?? null,
            'cost_breakdown' => $this->resource['cost_breakdown'] ?? [],
            'daily_schedule_density' => $this->resource['daily_schedule_density'] ?? [],
            'busiest_day' => $this->resource['busiest_day'] ?? null,
            'ai_recommendations_count' => $this->resource['ai_recommendations_count'] ?? 0,
            'accepted_recommendations' => $this->resource['accepted_recommendations'] ?? 0,
            'completion_percentage' => $this->resource['completion_percentage'] ?? 0,
        ];
    }
}
