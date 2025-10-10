<?php

declare(strict_types=1);

namespace App\Actions\Export;

use App\Models\Export;
use App\Models\TravelPlan;
use App\Models\User;
use App\Services\ExportService;
use Illuminate\Support\Facades\DB;

/**
 * Export Travel Plan Action
 *
 * Handles exporting a travel plan to various formats.
 */
class ExportTravelPlanAction
{
    /**
     * Create a new action instance.
     */
    public function __construct(
        private readonly ExportService $exportService
    ) {}

    /**
     * Execute the action.
     *
     * @param  string  $format  (pdf, docx, ical, json)
     */
    public function execute(User $user, TravelPlan $travelPlan, string $format): Export
    {
        return DB::transaction(function () use ($user, $travelPlan, $format) {
            // Create export record
            $export = Export::create([
                'user_id' => $user->id,
                'exportable_type' => TravelPlan::class,
                'exportable_id' => $travelPlan->id,
                'type' => 'travel_plan',
                'format' => $format,
                'status' => 'pending',
                'expires_at' => now()->addDays(7),
            ]);

            // Queue export job
            $this->exportService->exportTravelPlan($travelPlan, $format);

            return $export;
        });
    }
}
