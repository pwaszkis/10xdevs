<?php

declare(strict_types=1);

namespace App\Http\Resources\TravelPlan;

use App\Http\Resources\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Travel Plan Resource
 *
 * Transforms TravelPlan model data for API responses.
 *
 * @property \App\Models\TravelPlan $resource
 *
 * @mixin \App\Models\TravelPlan
 */
class TravelPlanResource extends JsonResource
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
            'user' => $this->whenLoaded('user', fn () => new UserResource($this->resource->user)),
            'title' => $this->resource->title,
            'destination' => $this->resource->destination,
            'destination_lat' => $this->resource->destination_lat,
            'destination_lng' => $this->resource->destination_lng,
            'departure_date' => $this->resource->departure_date->format('Y-m-d'),
            'number_of_days' => $this->resource->number_of_days,
            'number_of_people' => $this->resource->number_of_people,
            'budget_per_person' => $this->resource->budget_per_person,
            'budget_currency' => $this->resource->budget_currency,
            'user_notes' => $this->resource->user_notes,
            'status' => $this->resource->status,
            'has_ai_plan' => $this->resource->has_ai_plan,
            'days' => $this->whenLoaded('days'),
            'activities' => $this->whenLoaded('activities', fn () => ActivityResource::collection($this->resource->activities)),
            'created_at' => $this->resource->created_at->toIso8601String(),
            'updated_at' => $this->resource->updated_at->toIso8601String(),
        ];
    }
}
