<?php

declare(strict_types=1);

namespace App\Http\Resources\Feedback;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Feedback Resource
 *
 * Transforms TravelPlanFeedback model data for API responses.
 *
 * @property \App\Models\TravelPlanFeedback $resource
 *
 * @mixin \App\Models\TravelPlanFeedback
 */
class FeedbackResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'travel_plan_id' => $this->resource->travel_plan_id,
            'travel_plan' => $this->whenLoaded('travelPlan', fn () => ['id' => $this->resource->travelPlan->id, 'title' => $this->resource->travelPlan->title]),
            'satisfied' => $this->resource->satisfied,
            'issues' => $this->resource->issues,
            'created_at' => $this->resource->created_at->toIso8601String(),
            'updated_at' => $this->resource->updated_at->toIso8601String(),
        ];
    }
}
