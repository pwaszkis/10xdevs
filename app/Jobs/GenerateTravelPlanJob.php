<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\PlanGenerated;
use App\Exceptions\OpenAIException;
use App\Models\AIGeneration;
use App\Models\PlanDay;
use App\Models\PlanPoint;
use App\Models\TravelPlan;
use App\Services\AIGenerationService;
use App\Services\LimitService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Generate Travel Plan Job
 *
 * Asynchronously generates AI-powered travel itinerary.
 * Updates plan status, creates daily schedule, handles errors with retry logic.
 */
class GenerateTravelPlanJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Maximum execution time in seconds (2 minutes).
     */
    public int $timeout = 120;

    /**
     * Number of retry attempts.
     */
    public int $tries = 2;

    /**
     * Maximum number of exceptions to allow before failing.
     */
    public int $maxExceptions = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array<int, int>
     */
    public array $backoff = [10, 30];

    /**
     * Create a new job instance.
     *
     * @param  array<string, mixed>  $userPreferences
     */
    public function __construct(
        public int $travelPlanId,
        public int $userId,
        public int $aiGenerationId,
        public array $userPreferences
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AIGenerationService $aiService): void
    {
        Log::info('Starting travel plan generation', [
            'travel_plan_id' => $this->travelPlanId,
            'user_id' => $this->userId,
            'ai_generation_id' => $this->aiGenerationId,
        ]);

        // Load models
        /** @var TravelPlan $travelPlan */
        $travelPlan = TravelPlan::findOrFail($this->travelPlanId);

        /** @var AIGeneration $aiGeneration */
        $aiGeneration = AIGeneration::findOrFail($this->aiGenerationId);

        try {
            // Mark as processing (keep status as 'draft' until generation completes)
            $aiGeneration->markAsProcessing();

            // Generate plan using AI
            $result = $aiService->generatePlan($travelPlan, $this->userPreferences);

            // Save plan data in transaction
            DB::transaction(function () use ($travelPlan, $aiGeneration, $result) {
                // Delete existing days/points if regenerating
                $travelPlan->days()->delete();

                // Save daily itinerary
                $this->savePlanDays($travelPlan, $result['plan']);

                // Update travel plan status
                $travelPlan->update(['status' => 'planned']);

                // Mark AI generation as completed
                $aiGeneration->markAsCompleted(
                    tokensUsed: $result['tokens'],
                    costUsd: $result['cost']
                );
            });

            // Dispatch event
            event(new PlanGenerated($travelPlan, $aiGeneration));

            Log::info('Travel plan generated successfully', [
                'travel_plan_id' => $this->travelPlanId,
                'tokens' => $result['tokens'],
                'cost' => $result['cost'],
                'duration' => $result['duration'] . 's',
            ]);
        } catch (OpenAIException $e) {
            $this->handleFailure($travelPlan, $aiGeneration, $e);
            throw $e;
        } catch (Throwable $e) {
            $this->handleFailure($travelPlan, $aiGeneration, $e);
            throw $e;
        }
    }

    /**
     * Save generated plan days and points to database.
     *
     * @param  array<string, mixed>  $planData
     */
    private function savePlanDays(TravelPlan $travelPlan, array $planData): void
    {
        if (! isset($planData['days']) || ! is_array($planData['days'])) {
            throw new \InvalidArgumentException('Invalid plan data structure: missing days');
        }

        foreach ($planData['days'] as $dayData) {
            $dayNumber = $dayData['day_number'] ?? 1;

            // Calculate actual calendar date for this day
            $dayDate = $travelPlan->departure_date->copy()->addDays($dayNumber - 1);

            /** @var PlanDay $planDay */
            $planDay = PlanDay::create([
                'travel_plan_id' => $travelPlan->id,
                'day_number' => $dayNumber,
                'date' => $dayDate,
                'summary' => $dayData['summary'] ?? $dayData['description'] ?? null,
            ]);

            // Save points for this day
            // AI returns 'activities' array
            if (isset($dayData['activities']) && is_array($dayData['activities'])) {
                foreach ($dayData['activities'] as $index => $activityData) {
                    PlanPoint::create([
                        'plan_day_id' => $planDay->id,
                        'order_number' => $index + 1,
                        'day_part' => $this->determineDayPart($activityData['time'] ?? null),
                        'name' => $activityData['activity'] ?? 'Activity ' . ($index + 1),
                        'description' => $activityData['activity'] ?? 'Activity description',
                        'justification' => null, // AI doesn't provide this in current schema
                        'duration_minutes' => $activityData['duration_minutes'] ?? 60,
                        'google_maps_url' => $activityData['location'] ? $this->generateGoogleMapsUrl($activityData['location']) : null,
                        'location_lat' => null,
                        'location_lng' => null,
                    ]);
                }
            }
        }
    }

    /**
     * Determine day part from time string (HH:MM format).
     */
    private function determineDayPart(?string $time): string
    {
        if (! $time) {
            return 'rano'; // Default to morning
        }

        // Extract hour from HH:MM format
        $hour = (int) substr($time, 0, 2);

        return match (true) {
            $hour >= 6 && $hour < 12 => 'rano',      // 6:00 - 11:59
            $hour >= 12 && $hour < 15 => 'poludnie',  // 12:00 - 14:59
            $hour >= 15 && $hour < 19 => 'popoludnie', // 15:00 - 18:59
            default => 'wieczor',                      // 19:00 - 5:59
        };
    }

    /**
     * Generate Google Maps search URL from location name.
     */
    private function generateGoogleMapsUrl(string $location): string
    {
        $query = urlencode($location);

        return "https://www.google.com/maps/search/?api=1&query={$query}";
    }

    /**
     * Handle job failure.
     */
    private function handleFailure(
        TravelPlan $travelPlan,
        AIGeneration $aiGeneration,
        Throwable $exception
    ): void {
        Log::error('Travel plan generation failed', [
            'travel_plan_id' => $this->travelPlanId,
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Mark AI generation as failed
        $aiGeneration->markAsFailed($exception->getMessage());

        // Revert travel plan status to draft
        $travelPlan->update(['status' => 'draft']);
    }

    /**
     * Handle a job failure (called when all retries exhausted).
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Travel plan generation failed permanently', [
            'travel_plan_id' => $this->travelPlanId,
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);

        // Rollback generation count (user shouldn't be charged for failed generation)
        /** @var LimitService $limitService */
        $limitService = app(LimitService::class);
        $limitService->rollbackGeneration($this->userId);

        // Optionally: send notification to user about failure
        // Notification::route('mail', User::find($this->userId)->email)
        //     ->notify(new PlanGenerationFailed($this->travelPlanId));
    }
}
