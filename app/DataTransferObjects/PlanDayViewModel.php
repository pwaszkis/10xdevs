<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use Carbon\Carbon;

/**
 * Plan Day ViewModel
 *
 * ViewModel for a single day in a travel plan.
 */
readonly class PlanDayViewModel
{
    /**
     * Create a new ViewModel instance.
     *
     * @param  array<int, PlanPointViewModel>  $points
     */
    public function __construct(
        public int $dayNumber,
        public Carbon $date,
        public ?string $summary = null,
        public array $points = [],
    ) {}

    /**
     * Group points by day part (rano, południe, popołudnie, wieczór).
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function getPointsByDayPart(): array
    {
        /** @var \Illuminate\Support\Collection<int, PlanPointViewModel> $collection */
        $collection = collect($this->points);

        return $collection
            ->groupBy('dayPart')
            ->map(fn($points) => $points->map(fn($point) => $point->toArray())->toArray())
            ->toArray();
    }

    /**
     * Create ViewModel from array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        /** @var array<int, array<string, mixed>> $pointsData */
        $pointsData = $data['points'] ?? [];

        return new self(
            dayNumber: $data['day_number'],
            date: Carbon::parse($data['date']),
            summary: $data['summary'] ?? null,
            points: collect($pointsData)
                ->map(fn ($point) => PlanPointViewModel::fromArray($point))
                ->toArray(),
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
            'day_number' => $this->dayNumber,
            'date' => $this->date->format('Y-m-d'),
            'summary' => $this->summary,
            'points' => collect($this->points)
                ->map(fn ($point) => $point->toArray())
                ->toArray(),
        ];
    }
}
