<?php

declare(strict_types=1);

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * User Resource
 *
 * Transforms User model data for API responses.
 *
 * @property \App\Models\User $resource
 *
 * @mixin \App\Models\User
 */
class UserResource extends JsonResource
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
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'email_verified_at' => $this->resource->email_verified_at?->toIso8601String(),
            'nickname' => $this->resource->nickname,
            'home_location' => $this->resource->home_location,
            'onboarding_completed_at' => $this->resource->onboarding_completed_at?->toIso8601String(),
            'onboarding_step' => $this->resource->onboarding_step,
            'preferences' => $this->whenLoaded('preferences', fn () => new UserPreferencesResource($this->resource->preferences)),
            'created_at' => $this->resource->created_at->toIso8601String(),
            'updated_at' => $this->resource->updated_at->toIso8601String(),
        ];
    }
}
