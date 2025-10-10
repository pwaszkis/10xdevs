<?php

declare(strict_types=1);

namespace App\Http\Resources\AI;

use App\Http\Resources\TravelPlan\ActivityResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Generated Itinerary Resource
 *
 * Transforms AI-generated itinerary data for API responses.
 *
 * @property array<string, mixed> $resource
 */
class GeneratedItineraryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'summary' => $this->resource['summary'] ?? null,
            'total_estimated_cost' => $this->resource['total_estimated_cost'] ?? null,
            'currency' => $this->resource['currency'] ?? null,
            'activities' => ActivityResource::collection($this->resource['activities'] ?? []),
            'recommendations' => AIRecommendationResource::collection($this->resource['recommendations'] ?? []),
            'tips' => $this->resource['tips'] ?? [],
            'warnings' => $this->resource['warnings'] ?? [],
            'generated_at' => now()->toIso8601String(),
        ];
    }
}
