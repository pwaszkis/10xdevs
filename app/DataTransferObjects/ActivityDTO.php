<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use Carbon\Carbon;

/**
 * Activity DTO
 *
 * Data Transfer Object for activity data.
 */
readonly class ActivityDTO
{
    /**
     * Create a new DTO instance.
     */
    public function __construct(
        public string $name,
        public string $type,
        public Carbon $date,
        public ?string $description = null,
        public ?string $startTime = null,
        public ?string $endTime = null,
        public ?string $location = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?float $cost = null,
        public ?string $currency = null,
        public ?string $bookingReference = null,
        public ?string $notes = null,
        public int $order = 0,
    ) {}

    /**
     * Create DTO from array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            type: $data['type'],
            date: Carbon::parse($data['date']),
            description: $data['description'] ?? null,
            startTime: $data['start_time'] ?? null,
            endTime: $data['end_time'] ?? null,
            location: $data['location'] ?? null,
            latitude: isset($data['latitude']) ? (float) $data['latitude'] : null,
            longitude: isset($data['longitude']) ? (float) $data['longitude'] : null,
            cost: isset($data['cost']) ? (float) $data['cost'] : null,
            currency: $data['currency'] ?? null,
            bookingReference: $data['booking_reference'] ?? null,
            notes: $data['notes'] ?? null,
            order: (int) ($data['order'] ?? 0),
        );
    }

    /**
     * Convert DTO to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'date' => $this->date->format('Y-m-d'),
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'location' => $this->location,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'cost' => $this->cost,
            'currency' => $this->currency,
            'booking_reference' => $this->bookingReference,
            'notes' => $this->notes,
            'order' => $this->order,
        ];
    }

    /**
     * Check if the activity has location coordinates.
     */
    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * Check if the activity has a time range.
     */
    public function hasTimeRange(): bool
    {
        return $this->startTime !== null && $this->endTime !== null;
    }
}
