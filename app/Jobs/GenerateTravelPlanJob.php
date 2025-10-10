<?php

namespace App\Jobs;

use App\Models\AIUsageLog;
use App\Services\OpenAI\OpenAIService;
use App\Services\OpenAI\Schemas\TravelItinerarySchema;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateTravelPlanJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120; // 2 minutes

    public int $tries = 2;

    /**
     * @param  array<string, mixed>  $planData
     */
    public function __construct(
        public int $userId,
        public array $planData
    ) {}

    /**
     * Execute the job.
     */
    public function handle(OpenAIService $openAI): void
    {
        Log::channel('openai')->info('Starting plan generation', [
            'user_id' => $this->userId,
            'destination' => $this->planData['destination'] ?? 'Unknown',
        ]);

        try {
            // Build system message
            $systemMessage = $this->buildSystemMessage();

            // Build user message
            $userMessage = $this->buildUserMessage();

            // Generate plan
            $response = $openAI->chat()
                ->withSystemMessage($systemMessage)
                ->withUserMessage($userMessage)
                ->withResponseFormat(TravelItinerarySchema::get())
                ->useBalancedPreset()
                ->withMaxTokens(4000)
                ->send();

            // Log AI usage
            AIUsageLog::create([
                'user_id' => $this->userId,
                'model' => $response->model,
                'prompt_tokens' => $response->promptTokens,
                'completion_tokens' => $response->completionTokens,
                'total_tokens' => $response->totalTokens,
                'estimated_cost' => $response->estimatedCost(),
                'request_type' => 'travel_plan',
            ]);

            Log::channel('openai')->info('Plan generated successfully', [
                'user_id' => $this->userId,
                'tokens' => $response->totalTokens,
                'cost' => $response->estimatedCost(),
            ]);

            // Here you would typically save the plan to database
            // For now, we just log the result
            Log::channel('openai')->debug('Generated itinerary', [
                'itinerary' => $response->getParsedContent(),
            ]);

        } catch (\Exception $e) {
            Log::channel('openai')->error('Plan generation failed', [
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
            ]);

            // Log failed attempt
            AIUsageLog::create([
                'user_id' => $this->userId,
                'model' => 'unknown',
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'total_tokens' => 0,
                'estimated_cost' => 0,
                'request_type' => 'travel_plan',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function buildSystemMessage(): string
    {
        return <<<'PROMPT'
You are an expert travel planner assistant. Your task is to create detailed,
personalized travel itineraries based on user preferences.

Guidelines:
- Create realistic daily schedules (not too packed)
- Include diverse activities (sightseeing, food, culture, relaxation)
- Provide accurate cost estimates in USD
- Consider travel time between locations
- Suggest optimal times for each activity
- Include helpful tips specific to the destination
- Respect the user's budget constraints
- Be creative but practical

Always respond with valid JSON following the provided schema.
PROMPT;
    }

    private function buildUserMessage(): string
    {
        $destination = $this->planData['destination'] ?? 'Paris';
        $days = $this->planData['days'] ?? 3;
        $budget = $this->planData['budget'] ?? 1000;
        $preferences = $this->planData['preferences'] ?? [];

        $interests = $this->formatArray($preferences['interests'] ?? []);
        $travelStyle = $preferences['travel_style'] ?? 'balanced';
        $dietaryRestrictions = $this->formatArray($preferences['dietary_restrictions'] ?? []);
        $accommodationType = $preferences['accommodation_type'] ?? 'hotel';

        return <<<MESSAGE
Create a {$days}-day travel itinerary for {$destination}.

Budget: ${$budget} USD
Traveler preferences:
- Interests: {$interests}
- Travel style: {$travelStyle}
- Dietary restrictions: {$dietaryRestrictions}
- Accommodation type: {$accommodationType}

Please provide a comprehensive day-by-day itinerary with activities,
locations, times, and cost estimates.
MESSAGE;
    }

    /**
     * @param  array<int, string>  $items
     */
    private function formatArray(array $items): string
    {
        return empty($items) ? 'None specified' : implode(', ', $items);
    }
}
