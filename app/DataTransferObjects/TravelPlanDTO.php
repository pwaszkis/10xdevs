<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use Carbon\Carbon;

/**
 * Travel Plan DTO
 *
 * Data Transfer Object for travel plan data.
 */
readonly class TravelPlanDTO
{
    /**
     * Create a new DTO instance.
     *
     * @param  array<string, mixed>  $preferences
     */
    public function __construct(
        public string $destination,
        public Carbon $startDate,
        public Carbon $endDate,
        public ?float $budget = null,
        public ?string $currency = null,
        public int $travelersCount = 1,
        public array $preferences = [],
        public ?string $notes = null,
        public string $status = 'draft',
    ) {}

    /**
     * Create DTO from array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            destination: $data['destination'],
            startDate: Carbon::parse($data['start_date']),
            endDate: Carbon::parse($data['end_date']),
            budget: isset($data['budget']) ? (float) $data['budget'] : null,
            currency: $data['currency'] ?? null,
            travelersCount: (int) ($data['travelers_count'] ?? 1),
            preferences: $data['preferences'] ?? [],
            notes: $data['notes'] ?? null,
            status: $data['status'] ?? 'draft',
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
            'destination' => $this->destination,
            'start_date' => $this->startDate->format('Y-m-d'),
            'end_date' => $this->endDate->format('Y-m-d'),
            'budget' => $this->budget,
            'currency' => $this->currency,
            'travelers_count' => $this->travelersCount,
            'preferences' => $this->preferences,
            'notes' => $this->notes,
            'status' => $this->status,
        ];
    }

    /**
     * Get the duration of the trip in days.
     */
    public function getDurationInDays(): int
    {
        return (int) ($this->startDate->diffInDays($this->endDate) + 1);
    }
}
