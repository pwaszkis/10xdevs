<?php

declare(strict_types=1);

namespace App\Actions\TravelPlan;

use App\Models\Activity;
use App\Models\TravelPlan;
use Illuminate\Support\Facades\DB;

/**
 * Add Activity Action
 *
 * Handles adding an activity to a travel plan.
 */
class AddActivityAction
{
    /**
     * Execute the action.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(TravelPlan $travelPlan, array $data): Activity
    {
        return DB::transaction(function () use ($travelPlan, $data) {
            // Determine the order (last activity + 1)
            $maxOrder = $travelPlan->activities()->max('order') ?? 0;

            $activity = $travelPlan->activities()->create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'type' => $data['type'],
                'date' => $data['date'],
                'start_time' => $data['start_time'] ?? null,
                'end_time' => $data['end_time'] ?? null,
                'location' => $data['location'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'cost' => $data['cost'] ?? null,
                'currency' => $data['currency'] ?? $travelPlan->budget_currency,
                'booking_reference' => $data['booking_reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'order' => $maxOrder + 1,
            ]);

            return $activity;
        });
    }
}
