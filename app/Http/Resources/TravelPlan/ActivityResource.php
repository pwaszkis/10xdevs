<?php

declare(strict_types=1);

namespace App\Http\Resources\TravelPlan;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Activity Resource
 *
 * Transforms Activity model data for API responses.
 *
 * @property \App\Models\Activity $resource
 *
 * @mixin \App\Models\Activity
 */
class ActivityResource extends JsonResource
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
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'type' => $this->resource->type,
            'date' => $this->resource->date->format('Y-m-d'),
            'start_time' => $this->resource->start_time,
            'end_time' => $this->resource->end_time,
            'location' => $this->resource->location,
            'coordinates' => $this->when($this->resource->latitude && $this->resource->longitude, [
                'latitude' => $this->resource->latitude,
                'longitude' => $this->resource->longitude,
            ]),
            'cost' => $this->resource->cost,
            'currency' => $this->resource->currency,
            'booking_reference' => $this->resource->booking_reference,
            'notes' => $this->resource->notes,
            'order' => $this->resource->order,
            'created_at' => $this->resource->created_at->toIso8601String(),
            'updated_at' => $this->resource->updated_at->toIso8601String(),
        ];
    }
}
