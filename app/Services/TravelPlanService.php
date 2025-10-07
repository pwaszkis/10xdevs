<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Collection;

/**
 * Service for handling travel plan operations
 */
class TravelPlanService
{
    /**
     * Calculate the total budget for a travel plan
     *
     * @param  int  $budgetPerPerson  Budget per person in the selected currency
     * @param  int  $numberOfPeople  Number of people on the trip
     * @return int Total budget for the entire group
     */
    public function calculateTotalBudget(int $budgetPerPerson, int $numberOfPeople): int
    {
        return $budgetPerPerson * $numberOfPeople;
    }

    /**
     * Generate a list of travel interest categories
     *
     * @return Collection<string, string>
     */
    public function getInterestCategories(): Collection
    {
        return collect([
            'history_culture' => 'History & Culture',
            'nature_outdoor' => 'Nature & Outdoor',
            'gastronomy' => 'Gastronomy',
            'nightlife' => 'Nightlife & Entertainment',
            'beaches' => 'Beaches & Relaxation',
            'sports' => 'Sports & Activities',
            'art_museums' => 'Art & Museums',
        ]);
    }

    /**
     * Validate if the number of days is within acceptable range
     *
     * @param  int  $days  Number of days for the trip
     * @return bool True if valid, false otherwise
     */
    public function isValidTripDuration(int $days): bool
    {
        return $days >= 1 && $days <= 30;
    }

    /**
     * Validate if the number of people is within acceptable range
     *
     * @param  int  $people  Number of people on the trip
     * @return bool True if valid, false otherwise
     */
    public function isValidGroupSize(int $people): bool
    {
        return $people >= 1 && $people <= 10;
    }
}
