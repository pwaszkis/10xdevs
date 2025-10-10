<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

/**
 * Plan Point ViewModel
 *
 * ViewModel for a single point (attraction/place) in a day.
 */
readonly class PlanPointViewModel
{
    /**
     * Create a new ViewModel instance.
     */
    public function __construct(
        public int $id,
        public int $orderNumber,
        public string $dayPart,
        public string $name,
        public string $description,
        public string $justification,
        public int $durationMinutes,
        public string $googleMapsUrl,
        public ?float $locationLat = null,
        public ?float $locationLng = null,
    ) {}

    /**
     * Format duration (e.g., "2h 30min").
     */
    public function getFormattedDuration(): string
    {
        $hours = floor($this->durationMinutes / 60);
        $minutes = $this->durationMinutes % 60;

        if ($hours > 0 && $minutes > 0) {
            return "{$hours}h {$minutes}min";
        } elseif ($hours > 0) {
            return "{$hours}h";
        } else {
            return "{$minutes}min";
        }
    }

    /**
     * Get day part icon.
     */
    public function getDayPartIcon(): string
    {
        return match ($this->dayPart) {
            'rano' => '🌅',
            'poludnie' => '☀️',
            'popołudnie' => '🌇',
            'wieczór' => '🌙',
            default => '📍',
        };
    }

    /**
     * Get readable day part label.
     */
    public function getDayPartLabel(): string
    {
        return match ($this->dayPart) {
            'rano' => 'Rano',
            'poludnie' => 'Południe',
            'popołudnie' => 'Popołudnie',
            'wieczór' => 'Wieczór',
            default => 'Inne',
        };
    }

    /**
     * Create ViewModel from array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            orderNumber: $data['order_number'],
            dayPart: $data['day_part'],
            name: $data['name'],
            description: $data['description'],
            justification: $data['justification'],
            durationMinutes: $data['duration_minutes'],
            googleMapsUrl: $data['google_maps_url'],
            locationLat: $data['location_lat'] ?? null,
            locationLng: $data['location_lng'] ?? null,
        );
    }

    /**
     * Convert ViewModel to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->orderNumber,
            'day_part' => $this->dayPart,
            'name' => $this->name,
            'description' => $this->description,
            'justification' => $this->justification,
            'duration_minutes' => $this->durationMinutes,
            'google_maps_url' => $this->googleMapsUrl,
            'location_lat' => $this->locationLat,
            'location_lng' => $this->locationLng,
        ];
    }
}
