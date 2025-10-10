<?php

declare(strict_types=1);

namespace App\Http\Resources\Export;

use App\Http\Resources\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Export Resource
 *
 * Transforms Export model data for API responses.
 *
 * @property \App\Models\Export $resource
 *
 * @mixin \App\Models\Export
 */
class ExportResource extends JsonResource
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
            'type' => $this->resource->type,
            'format' => $this->resource->format,
            'status' => $this->resource->status,
            'file_path' => $this->when($this->resource->status === 'completed', $this->resource->file_path),
            'file_url' => $this->when($this->resource->status === 'completed', $this->resource->file_url),
            'file_size' => $this->when($this->resource->status === 'completed', $this->resource->file_size),
            'expires_at' => $this->resource->expires_at?->toIso8601String(),
            'error_message' => $this->when($this->resource->status === 'failed', $this->resource->error_message),
            'created_at' => $this->resource->created_at->toIso8601String(),
            'updated_at' => $this->resource->updated_at->toIso8601String(),
        ];
    }
}
