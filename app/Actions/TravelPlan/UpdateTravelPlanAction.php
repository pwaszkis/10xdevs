<?php

declare(strict_types=1);

namespace App\Actions\TravelPlan;

use App\Models\TravelPlan;
use Illuminate\Support\Facades\DB;

/**
 * Update Travel Plan Action
 *
 * Handles updating an existing travel plan.
 */
class UpdateTravelPlanAction
{
    /**
     * Execute the action.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(TravelPlan $travelPlan, array $data): TravelPlan
    {
        return DB::transaction(function () use ($travelPlan, $data) {
            // Merge existing preferences with new ones
            if (isset($data['preferences'])) {
                $data['preferences'] = array_merge(
                    $travelPlan->preferences ?? [],
                    $data['preferences']
                );
            }

            $travelPlan->update($data);

            return $travelPlan->fresh();
        });
    }
}
