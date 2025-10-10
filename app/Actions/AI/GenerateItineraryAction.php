<?php

declare(strict_types=1);

namespace App\Actions\AI;

use App\DataTransferObjects\ItineraryPreferencesDTO;
use App\Models\TravelPlan;
use App\Services\AIService;
use Illuminate\Support\Facades\DB;

/**
 * Generate Itinerary Action
 *
 * Handles AI-powered itinerary generation for a travel plan.
 */
class GenerateItineraryAction
{
    /**
     * Create a new action instance.
     */
    public function __construct(
        private readonly AIService $aiService
    ) {}

    /**
     * Execute the action.
     *
     * @return array<string, mixed>
     */
    public function execute(TravelPlan $travelPlan, ItineraryPreferencesDTO $preferences): array
    {
        return DB::transaction(function () use ($travelPlan, $preferences) {
            // Generate itinerary using AI service
            $itinerary = $this->aiService->generateItinerary($preferences);

            // Store AI recommendations
            foreach ($itinerary['recommendations'] ?? [] as $recommendation) {
                $travelPlan->aiRecommendations()->create([
                    'type' => $recommendation['type'] ?? 'itinerary',
                    'category' => $recommendation['category'] ?? 'general',
                    'title' => $recommendation['title'],
                    'description' => $recommendation['description'],
                    'content' => $recommendation['content'] ?? [],
                    'metadata' => $recommendation['metadata'] ?? [],
                    'confidence_score' => $recommendation['confidence_score'] ?? 0.8,
                    'status' => 'pending',
                ]);
            }

            return $itinerary;
        });
    }
}
