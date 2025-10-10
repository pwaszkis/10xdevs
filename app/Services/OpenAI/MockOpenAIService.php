<?php

namespace App\Services\OpenAI;

use App\DataTransferObjects\OpenAIResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MockOpenAIService implements OpenAIService
{
    /** @var array<int, array<string, mixed>> */
    private array $callHistory = [];

    private int $callCount = 0;

    /**
     * @param  array<string, mixed>  $mockScenarios
     */
    public function __construct(
        protected string $defaultModel,
        protected array $mockScenarios = []
    ) {}

    public function chat(): OpenAIRequestBuilder
    {
        return new OpenAIRequestBuilder($this, $this->defaultModel);
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function completion(
        string $systemMessage,
        string $userMessage,
        ?array $responseFormat = null,
        array $parameters = []
    ): OpenAIResponse {
        $builder = $this->chat()
            ->withSystemMessage($systemMessage)
            ->withUserMessage($userMessage);

        if ($responseFormat) {
            $builder->withResponseFormat($responseFormat);
        }

        return $builder->send();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(array $payload): OpenAIResponse
    {
        $this->callCount++;
        $this->callHistory[] = $payload;

        Log::channel('openai')->debug('Mock OpenAI Request', [
            'call_number' => $this->callCount,
            'model' => $payload['model'],
            'has_response_format' => isset($payload['response_format']),
        ]);

        // Simulate API delay
        usleep(500000); // 0.5s

        $isStructured = isset($payload['response_format']);

        if ($isStructured) {
            $mockData = $this->generateStructuredMockResponse($payload);
        } else {
            $mockData = $this->generateTextMockResponse($payload);
        }

        return OpenAIResponse::fromArray($mockData);
    }

    /**
     * Generuje structured mock response na podstawie schema
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function generateStructuredMockResponse(array $payload): array
    {
        $schemaName = $payload['response_format']['json_schema']['name'] ?? 'response';

        // Generate mock data based on schema
        $mockContent = match ($schemaName) {
            'travel_itinerary' => json_encode([
                'destination' => 'Paris, France',
                'duration_days' => 3,
                'days' => [
                    [
                        'day_number' => 1,
                        'date' => '2025-06-01',
                        'activities' => [
                            [
                                'time' => '09:00',
                                'activity' => 'Visit Eiffel Tower',
                                'location' => 'Champ de Mars',
                                'cost_estimate' => 26.0,
                                'category' => 'sightseeing',
                            ],
                            [
                                'time' => '14:00',
                                'activity' => 'Louvre Museum',
                                'location' => 'Rue de Rivoli',
                                'cost_estimate' => 17.0,
                                'category' => 'sightseeing',
                            ],
                        ],
                        'daily_budget' => 150.0,
                    ],
                    [
                        'day_number' => 2,
                        'date' => '2025-06-02',
                        'activities' => [
                            [
                                'time' => '10:00',
                                'activity' => 'Notre-Dame Cathedral',
                                'location' => 'Île de la Cité',
                                'cost_estimate' => 0.0,
                                'category' => 'sightseeing',
                            ],
                        ],
                        'daily_budget' => 120.0,
                    ],
                    [
                        'day_number' => 3,
                        'date' => '2025-06-03',
                        'activities' => [
                            [
                                'time' => '11:00',
                                'activity' => 'Montmartre Walk',
                                'location' => 'Montmartre',
                                'cost_estimate' => 0.0,
                                'category' => 'sightseeing',
                            ],
                        ],
                        'daily_budget' => 100.0,
                    ],
                ],
                'total_cost_estimate' => 370.0,
                'tips' => [
                    'Buy Paris Museum Pass for unlimited access',
                    'Use metro for transportation',
                    'Book Eiffel Tower tickets in advance',
                ],
            ]),
            default => json_encode([
                'result' => 'Mock structured response',
                'schema' => $schemaName,
            ]),
        };

        return [
            'id' => 'chatcmpl-mock-'.Str::random(10),
            'object' => 'chat.completion',
            'created' => time(),
            'model' => $payload['model'],
            'choices' => [
                [
                    'index' => 0,
                    'message' => [
                        'role' => 'assistant',
                        'content' => $mockContent,
                    ],
                    'finish_reason' => 'stop',
                ],
            ],
            'usage' => [
                'prompt_tokens' => rand(100, 500),
                'completion_tokens' => rand(200, 800),
                'total_tokens' => rand(300, 1300),
            ],
        ];
    }

    /**
     * Generuje text mock response
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function generateTextMockResponse(array $payload): array
    {
        $mockContent = 'This is a mock response from the OpenAI service. '.
                      'In production, this would be a real AI-generated response. '.
                      'Your request had '.count($payload['messages']).' messages.';

        return [
            'id' => 'chatcmpl-mock-'.Str::random(10),
            'object' => 'chat.completion',
            'created' => time(),
            'model' => $payload['model'],
            'choices' => [
                [
                    'index' => 0,
                    'message' => [
                        'role' => 'assistant',
                        'content' => $mockContent,
                    ],
                    'finish_reason' => 'stop',
                ],
            ],
            'usage' => [
                'prompt_tokens' => rand(50, 200),
                'completion_tokens' => rand(50, 200),
                'total_tokens' => rand(100, 400),
            ],
        ];
    }

    public function isMock(): bool
    {
        return true;
    }

    public function getModel(): string
    {
        return $this->defaultModel;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCallHistory(): array
    {
        return $this->callHistory;
    }

    public function getCallCount(): int
    {
        return $this->callCount;
    }
}
