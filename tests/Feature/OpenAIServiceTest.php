<?php

namespace Tests\Feature;

use App\Services\OpenAI\MockOpenAIService;
use App\Services\OpenAI\OpenAIService;
use App\Services\OpenAI\Schemas\TravelItinerarySchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpenAIServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_is_mock_in_testing(): void
    {
        $service = app(OpenAIService::class);

        $this->assertInstanceOf(MockOpenAIService::class, $service);
        $this->assertTrue($service->isMock());
    }

    public function test_can_generate_text_response(): void
    {
        $service = app(OpenAIService::class);

        $response = $service->chat()
            ->withSystemMessage('You are a helpful assistant')
            ->withUserMessage('Say hello')
            ->send();

        $this->assertNotEmpty($response->getContent());
        $this->assertGreaterThan(0, $response->totalTokens);
        $this->assertEquals('stop', $response->finishReason);
    }

    public function test_can_generate_structured_response(): void
    {
        $service = app(OpenAIService::class);

        $response = $service->chat()
            ->withSystemMessage('You are a travel planner')
            ->withUserMessage('Plan a 3-day trip to Paris')
            ->withResponseFormat(TravelItinerarySchema::get())
            ->send();

        $this->assertTrue($response->isStructured());
        $this->assertIsArray($response->getParsedContent());
        $this->assertArrayHasKey('destination', $response->getParsedContent());
        $this->assertArrayHasKey('days', $response->getParsedContent());
        $this->assertArrayHasKey('total_cost_estimate', $response->getParsedContent());
    }

    public function test_presets_work_correctly(): void
    {
        $service = app(OpenAIService::class);

        $builder = $service->chat()
            ->withUserMessage('Test')
            ->useCreativePreset();

        $payload = $builder->build();

        $this->assertEquals(0.9, $payload['temperature']);
        $this->assertEquals(0.95, $payload['top_p']);
        $this->assertEquals(0.5, $payload['frequency_penalty']);
        $this->assertEquals(0.5, $payload['presence_penalty']);
    }

    public function test_precise_preset(): void
    {
        $service = app(OpenAIService::class);

        $builder = $service->chat()
            ->withUserMessage('Test')
            ->usePrecisePreset();

        $payload = $builder->build();

        $this->assertEquals(0.2, $payload['temperature']);
        $this->assertEquals(0.8, $payload['top_p']);
    }

    public function test_balanced_preset(): void
    {
        $service = app(OpenAIService::class);

        $builder = $service->chat()
            ->withUserMessage('Test')
            ->useBalancedPreset();

        $payload = $builder->build();

        $this->assertEquals(0.7, $payload['temperature']);
        $this->assertEquals(1.0, $payload['top_p']);
    }

    public function test_can_set_custom_parameters(): void
    {
        $service = app(OpenAIService::class);

        $builder = $service->chat()
            ->withUserMessage('Test')
            ->withTemperature(0.5)
            ->withMaxTokens(1000)
            ->withTopP(0.9)
            ->withFrequencyPenalty(0.3)
            ->withPresencePenalty(0.2);

        $payload = $builder->build();

        $this->assertEquals(0.5, $payload['temperature']);
        $this->assertEquals(1000, $payload['max_tokens']);
        $this->assertEquals(0.9, $payload['top_p']);
        $this->assertEquals(0.3, $payload['frequency_penalty']);
        $this->assertEquals(0.2, $payload['presence_penalty']);
    }

    public function test_completion_method_works(): void
    {
        $service = app(OpenAIService::class);

        $response = $service->completion(
            systemMessage: 'You are a helpful assistant',
            userMessage: 'Say hello'
        );

        $this->assertNotEmpty($response->getContent());
        $this->assertGreaterThan(0, $response->totalTokens);
    }

    public function test_response_has_usage_data(): void
    {
        $service = app(OpenAIService::class);

        $response = $service->chat()
            ->withUserMessage('Test')
            ->send();

        $usage = $response->getUsage();

        $this->assertArrayHasKey('prompt_tokens', $usage);
        $this->assertArrayHasKey('completion_tokens', $usage);
        $this->assertArrayHasKey('total_tokens', $usage);
        $this->assertGreaterThan(0, $usage['total_tokens']);
    }

    public function test_estimated_cost_is_calculated(): void
    {
        $service = app(OpenAIService::class);

        $response = $service->chat()
            ->withUserMessage('Test')
            ->send();

        $cost = $response->estimatedCost();

        $this->assertIsFloat($cost);
        $this->assertGreaterThan(0, $cost);
    }

    public function test_safe_content_is_escaped(): void
    {
        $service = app(OpenAIService::class);

        $response = $service->chat()
            ->withUserMessage('Test')
            ->send();

        $safeContent = $response->getSafeContent();

        $this->assertIsString($safeContent);
        $this->assertStringNotContainsString('<script>', $safeContent);
    }

    public function test_mock_service_tracks_calls(): void
    {
        /** @var MockOpenAIService $service */
        $service = app(OpenAIService::class);

        $this->assertEquals(0, $service->getCallCount());

        $service->chat()
            ->withUserMessage('Test 1')
            ->send();

        $this->assertEquals(1, $service->getCallCount());

        $service->chat()
            ->withUserMessage('Test 2')
            ->send();

        $this->assertEquals(2, $service->getCallCount());

        $history = $service->getCallHistory();
        $this->assertCount(2, $history);
    }

    public function test_travel_itinerary_schema_is_valid(): void
    {
        $schema = TravelItinerarySchema::get();

        $this->assertArrayHasKey('type', $schema);
        $this->assertEquals('json_schema', $schema['type']);
        $this->assertArrayHasKey('json_schema', $schema);
        $this->assertTrue($schema['json_schema']['strict']);
        $this->assertEquals('travel_itinerary', $schema['json_schema']['name']);
    }

    public function test_structured_response_contains_valid_itinerary(): void
    {
        $service = app(OpenAIService::class);

        $response = $service->chat()
            ->withSystemMessage('You are a travel planner')
            ->withUserMessage('Plan a trip to Paris')
            ->withResponseFormat(TravelItinerarySchema::get())
            ->send();

        $content = $response->getParsedContent();

        $this->assertIsArray($content);
        $this->assertArrayHasKey('destination', $content);
        $this->assertArrayHasKey('duration_days', $content);
        $this->assertArrayHasKey('days', $content);
        $this->assertIsArray($content['days']);

        if (count($content['days']) > 0) {
            $firstDay = $content['days'][0];
            $this->assertArrayHasKey('day_number', $firstDay);
            $this->assertArrayHasKey('activities', $firstDay);
            $this->assertIsArray($firstDay['activities']);

            if (count($firstDay['activities']) > 0) {
                $activity = $firstDay['activities'][0];
                $this->assertArrayHasKey('time', $activity);
                $this->assertArrayHasKey('activity', $activity);
                $this->assertArrayHasKey('location', $activity);
                $this->assertArrayHasKey('cost_estimate', $activity);
                $this->assertArrayHasKey('category', $activity);
            }
        }
    }
}
