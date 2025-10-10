<?php

declare(strict_types=1);

namespace App\Actions\TravelPlan;

use App\Models\TravelPlan;
use Illuminate\Support\Facades\DB;

/**
 * Delete Travel Plan Action
 *
 * Handles deletion of a travel plan and its related data.
 */
class DeleteTravelPlanAction
{
    /**
     * Execute the action.
     */
    public function execute(TravelPlan $travelPlan): bool
    {
        return DB::transaction(function () use ($travelPlan) {
            // Delete all activities
            $travelPlan->activities()->delete();

            // Delete all AI recommendations
            $travelPlan->aiRecommendations()->delete();

            // Delete the travel plan
            return $travelPlan->delete();
        });
    }
}
