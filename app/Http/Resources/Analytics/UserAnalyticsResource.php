<?php

declare(strict_types=1);

namespace App\Http\Resources\Analytics;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * User Analytics Resource
 *
 * Transforms user analytics data for API responses.
 *
 * @property array<string, mixed> $resource
 */
class UserAnalyticsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'total_plans' => $this->resource['total_plans'] ?? 0,
            'active_plans' => $this->resource['active_plans'] ?? 0,
            'completed_plans' => $this->resource['completed_plans'] ?? 0,
            'total_activities' => $this->resource['total_activities'] ?? 0,
            'total_destinations' => $this->resource['total_destinations'] ?? 0,
            'favorite_destinations' => $this->resource['favorite_destinations'] ?? [],
            'total_budget' => $this->resource['total_budget'] ?? 0,
            'average_trip_duration' => $this->resource['average_trip_duration'] ?? 0,
            'most_common_activity_type' => $this->resource['most_common_activity_type'] ?? null,
            'ai_recommendations_used' => $this->resource['ai_recommendations_used'] ?? 0,
            'account_age_days' => $this->resource['account_age_days'] ?? 0,
            'last_active_at' => $this->resource['last_active_at'] ?? null,
        ];
    }
}
