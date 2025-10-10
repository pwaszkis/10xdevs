<?php

declare(strict_types=1);

namespace App\Actions\TravelPlan;

use App\Models\TravelPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Create Travel Plan Action
 *
 * Handles creation of a new travel plan.
 */
class CreateTravelPlanAction
{
    /**
     * Execute the action.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(User $user, array $data): TravelPlan
    {
        return DB::transaction(function () use ($user, $data) {
            $travelPlan = $user->travelPlans()->create([
                'destination' => $data['destination'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'budget' => $data['budget'] ?? null,
                'currency' => $data['currency'] ?? $user->preferences->currency ?? 'USD',
                'travelers_count' => $data['travelers_count'],
                'preferences' => $data['preferences'] ?? [],
                'notes' => $data['notes'] ?? null,
                'status' => 'draft',
            ]);

            return $travelPlan;
        });
    }
}
