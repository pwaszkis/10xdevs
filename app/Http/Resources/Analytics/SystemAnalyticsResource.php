<?php

declare(strict_types=1);

namespace App\Http\Resources\Analytics;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * System Analytics Resource
 *
 * Transforms system-wide analytics data for API responses (admin only).
 *
 * @property array<string, mixed> $resource
 */
class SystemAnalyticsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'total_users' => $this->resource['total_users'] ?? 0,
            'active_users_30d' => $this->resource['active_users_30d'] ?? 0,
            'new_users_30d' => $this->resource['new_users_30d'] ?? 0,
            'total_travel_plans' => $this->resource['total_travel_plans'] ?? 0,
            'active_travel_plans' => $this->resource['active_travel_plans'] ?? 0,
            'total_activities' => $this->resource['total_activities'] ?? 0,
            'total_ai_recommendations' => $this->resource['total_ai_recommendations'] ?? 0,
            'ai_recommendations_accepted' => $this->resource['ai_recommendations_accepted'] ?? 0,
            'ai_acceptance_rate' => $this->resource['ai_acceptance_rate'] ?? 0,
            'total_feedback' => $this->resource['total_feedback'] ?? 0,
            'pending_feedback' => $this->resource['pending_feedback'] ?? 0,
            'popular_destinations' => $this->resource['popular_destinations'] ?? [],
            'average_trip_duration' => $this->resource['average_trip_duration'] ?? 0,
            'total_exports' => $this->resource['total_exports'] ?? 0,
            'exports_by_format' => $this->resource['exports_by_format'] ?? [],
        ];
    }
}
