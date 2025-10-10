<?php

declare(strict_types=1);

namespace App\Http\Resources\Analytics;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Activity Analytics Resource
 *
 * Transforms activity analytics data for API responses.
 *
 * @property array<string, mixed> $resource
 */
class ActivityAnalyticsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'total_activities' => $this->resource['total_activities'] ?? 0,
            'activities_by_type' => $this->resource['activities_by_type'] ?? [],
            'activities_by_month' => $this->resource['activities_by_month'] ?? [],
            'average_cost_by_type' => $this->resource['average_cost_by_type'] ?? [],
            'most_expensive_activity' => $this->resource['most_expensive_activity'] ?? null,
            'total_booking_references' => $this->resource['total_booking_references'] ?? 0,
            'activities_with_location' => $this->resource['activities_with_location'] ?? 0,
        ];
    }
}
