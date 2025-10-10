<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

/**
 * Itinerary Preferences DTO
 *
 * Data Transfer Object for itinerary generation preferences.
 */
readonly class ItineraryPreferencesDTO
{
    /**
     * Create a new DTO instance.
     *
     * @param  array<int, string>  $focus
     */
    public function __construct(
        public ?string $pace = null,
        public ?string $style = null,
        public array $focus = [],
        public bool $includeMeals = false,
        public bool $includeTransport = false,
        public ?string $accessibilityNeeds = null,
        public ?string $additionalNotes = null,
    ) {}

    /**
     * Create DTO from array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $preferences = $data['preferences'] ?? [];

        return new self(
            pace: $preferences['pace'] ?? null,
            style: $preferences['style'] ?? null,
            focus: $preferences['focus'] ?? [],
            includeMeals: $preferences['include_meals'] ?? false,
            includeTransport: $preferences['include_transport'] ?? false,
            accessibilityNeeds: $preferences['accessibility_needs'] ?? null,
            additionalNotes: $data['additional_notes'] ?? null,
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
            'pace' => $this->pace,
            'style' => $this->style,
            'focus' => $this->focus,
            'include_meals' => $this->includeMeals,
            'include_transport' => $this->includeTransport,
            'accessibility_needs' => $this->accessibilityNeeds,
            'additional_notes' => $this->additionalNotes,
        ];
    }
}
