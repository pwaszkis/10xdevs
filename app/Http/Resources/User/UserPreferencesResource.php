<?php

declare(strict_types=1);

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * User Preferences Resource
 *
 * Transforms UserPreferences model data for API responses.
 *
 * @property \App\Models\UserPreference $resource
 *
 * @mixin \App\Models\UserPreference
 */
class UserPreferencesResource extends JsonResource
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
            'language' => $this->resource->language,
            'timezone' => $this->resource->timezone,
            'currency' => $this->resource->currency,
            'notifications_enabled' => $this->resource->notifications_enabled,
            'email_notifications' => $this->resource->email_notifications,
            'push_notifications' => $this->resource->push_notifications,
            'theme' => $this->resource->theme,
            'created_at' => $this->resource->created_at->toIso8601String(),
            'updated_at' => $this->resource->updated_at->toIso8601String(),
        ];
    }
}
