<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\OpenAIResponse;
use App\Exceptions\OpenAIException;
use App\Exceptions\OpenAITimeoutException;
use App\Models\TravelPlan;
use App\Services\OpenAI\OpenAIService;
use App\Services\OpenAI\Schemas\TravelItinerarySchema;
use Illuminate\Support\Facades\Log;

/**
 * AI Generation Service
 *
 * Handles AI-powered travel plan generation using OpenAI API.
 * Builds prompts, executes requests, and parses responses.
 */
class AIGenerationService
{
    /**
     * Token costs for GPT-4o-mini (per 1M tokens).
     */
    private const COST_PER_1M_INPUT_TOKENS = 0.150; // $0.150 per 1M input tokens

    private const COST_PER_1M_OUTPUT_TOKENS = 0.600; // $0.600 per 1M output tokens

    public function __construct(
        private readonly OpenAIService $openAI
    ) {}

    /**
     * Generate a travel plan using AI.
     *
     * @param  array<string, mixed>  $userPreferences
     * @return array<string, mixed> Contains: plan, tokens, cost, duration
     *
     * @throws OpenAITimeoutException
     * @throws OpenAIException
     */
    public function generatePlan(TravelPlan $travel, array $userPreferences): array
    {
        $startTime = now();

        try {
            // Build messages for OpenAI
            $messages = $this->buildMessages($travel, $userPreferences);

            // Get structured response schema
            $schema = TravelItinerarySchema::get();

            // Execute AI request with structured output
            $response = $this->openAI->chat()
                ->withSystemMessage($messages['system'])
                ->withUserMessage($messages['user'])
                ->withResponseFormat($schema)
                ->withTemperature(0.7)
                ->withMaxTokens(3000)
                ->send();

            $duration = now()->diffInSeconds($startTime);

            // Parse and validate response
            $parsedPlan = $this->parseResponse($response);

            // Calculate costs
            $usage = $response->getUsage();
            $tokensUsed = $usage['total_tokens'] ?? 0;
            $cost = $this->calculateCost(
                $usage['prompt_tokens'] ?? 0,
                $usage['completion_tokens'] ?? 0
            );

            return [
                'plan' => $parsedPlan,
                'tokens' => $tokensUsed,
                'cost' => $cost,
                'duration' => $duration,
            ];
        } catch (OpenAITimeoutException $e) {
            Log::error('AI generation timeout', [
                'travel_id' => $travel->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (OpenAIException $e) {
            Log::error('AI generation error', [
                'travel_id' => $travel->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Build system and user messages for OpenAI.
     *
     * @param  array<string, mixed>  $preferences
     * @return array<string, string>
     */
    private function buildMessages(TravelPlan $travel, array $preferences): array
    {
        $systemPrompt = $this->getSystemPrompt($preferences);
        $userPrompt = $this->getUserPrompt($travel, $preferences);

        return [
            'system' => $systemPrompt,
            'user' => $userPrompt,
        ];
    }

    /**
     * Build system prompt with user preferences.
     *
     * @param  array<string, mixed>  $preferences
     */
    private function getSystemPrompt(array $preferences): string
    {
        // Handle interests - can be 'interests' or 'interests_categories' key
        $interestsValue = $preferences['interests'] ?? $preferences['interests_categories'] ?? [];
        $interests = $this->formatInterests($interestsValue);

        $pace = $preferences['travel_pace'] ?? 'moderate';
        $budget = $preferences['budget_level'] ?? 'standard';
        $transport = $preferences['transport_preference'] ?? 'mixed';

        // Handle restrictions - can be string (ENUM from DB) or array
        $restrictionsValue = $preferences['restrictions'] ?? [];
        $restrictionsArray = is_array($restrictionsValue) ? $restrictionsValue : [$restrictionsValue];
        $restrictions = $this->formatRestrictions($restrictionsArray);

        return <<<PROMPT
You are an expert travel planner assistant specialized in creating detailed, personalized travel itineraries.

USER PREFERENCES:
- Interests: {$interests}
- Travel Pace: {$pace}
- Budget Level: {$budget}
- Transport Preference: {$transport}
{$restrictions}

Your task is to create a detailed day-by-day itinerary that:
1. Matches the user's interests and preferences
2. Respects their budget level and transport preferences
3. Considers their travel pace (relaxed, moderate, or active)
4. Includes specific attractions, restaurants, and activities with estimated times
5. Provides practical tips and recommendations
6. Is realistic and achievable within the given timeframe

Always respond in Polish language with practical, actionable recommendations.
PROMPT;
    }

    /**
     * Build user prompt with travel details.
     *
     * @param  array<string, mixed>  $preferences
     */
    private function getUserPrompt(TravelPlan $travel, array $preferences): string
    {
        $destination = $travel->destination;
        $days = $travel->number_of_days;
        $people = $travel->number_of_people;
        $departureDate = $travel->departure_date->format('Y-m-d');

        $budgetInfo = '';
        if ($travel->budget_per_person !== null) {
            $budgetInfo = "\n- Budget per person: {$travel->budget_per_person} {$travel->budget_currency}";
        }

        $notesInfo = '';
        if ($travel->user_notes !== null && trim($travel->user_notes) !== '') {
            $notesInfo = "\n- User notes: {$travel->user_notes}";
        }

        return <<<PROMPT
Please create a detailed {$days}-day travel itinerary for the following trip:

TRIP DETAILS:
- Destination: {$destination}
- Duration: {$days} days
- Number of people: {$people}
- Departure date: {$departureDate}{$budgetInfo}{$notesInfo}

Create a day-by-day plan with:
- Morning, afternoon, and evening activities
- Specific attractions, restaurants, and points of interest
- Estimated times for each activity
- Transport recommendations between locations
- Practical tips for each day

Focus on creating an authentic, memorable experience that matches the traveler's preferences.
PROMPT;
    }

    /**
     * Format interests for prompt.
     *
     * @param  list<string>  $interests
     */
    private function formatInterests(array $interests): string
    {
        if (empty($interests)) {
            return 'General sightseeing and local experiences';
        }

        return implode(', ', $interests);
    }

    /**
     * Format restrictions for prompt.
     *
     * @param  list<string>  $restrictions
     */
    private function formatRestrictions(array $restrictions): string
    {
        if (empty($restrictions)) {
            return '';
        }

        $formatted = implode(', ', $restrictions);

        return "- Restrictions/Requirements: {$formatted}";
    }

    /**
     * Parse OpenAI response into structured plan data.
     *
     * @return array<string, mixed>
     */
    private function parseResponse(OpenAIResponse $response): array
    {
        if (! $response->isStructured()) {
            throw new OpenAIException('Expected structured response but got plain text');
        }

        $content = $response->getParsedContent();

        if (! isset($content['days']) || ! is_array($content['days'])) {
            throw new OpenAIException('Invalid response structure: missing days array');
        }

        return $content;
    }

    /**
     * Calculate cost in USD based on tokens used.
     *
     * @param  int  $inputTokens  Prompt tokens
     * @param  int  $outputTokens  Completion tokens
     */
    private function calculateCost(int $inputTokens, int $outputTokens): float
    {
        $inputCost = ($inputTokens / 1_000_000) * self::COST_PER_1M_INPUT_TOKENS;
        $outputCost = ($outputTokens / 1_000_000) * self::COST_PER_1M_OUTPUT_TOKENS;

        return round($inputCost + $outputCost, 4);
    }

    /**
     * Get estimated cost for a plan based on average token usage.
     *
     * Typical usage: ~1500 prompt tokens + ~2000 completion tokens
     */
    public function getEstimatedCost(int $days): float
    {
        // Estimate: 500 tokens base + 200 per day for prompt
        $estimatedInputTokens = 500 + ($days * 200);

        // Estimate: 1000 tokens base + 300 per day for completion
        $estimatedOutputTokens = 1000 + ($days * 300);

        return $this->calculateCost($estimatedInputTokens, $estimatedOutputTokens);
    }
}
