<?php

declare(strict_types=1);

namespace App\Http\Resources\AI;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * AI Recommendation Resource
 *
 * Transforms AIRecommendation model data for API responses.
 *
 * @property \App\Models\AIRecommendation $resource
 *
 * @mixin \App\Models\AIRecommendation
 */
class AIRecommendationResource extends JsonResource
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
            'type' => $this->resource->type,
            'content' => $this->resource->content,
            'is_accepted' => $this->resource->is_accepted,
            'metadata' => $this->resource->metadata,
            'created_at' => $this->resource->created_at->toIso8601String(),
            'updated_at' => $this->resource->updated_at->toIso8601String(),
        ];
    }
}
