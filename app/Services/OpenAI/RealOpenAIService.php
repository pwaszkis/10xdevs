<?php

namespace App\Services\OpenAI;

use App\DataTransferObjects\OpenAIResponse;
use App\Exceptions\OpenAIAuthenticationException;
use App\Exceptions\OpenAIInvalidRequestException;
use App\Exceptions\OpenAINetworkException;
use App\Exceptions\OpenAIRateLimitException;
use App\Exceptions\OpenAIServerException;
use App\Exceptions\OpenAITimeoutException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RealOpenAIService implements OpenAIService
{
    private const API_BASE_URL = 'https://api.openai.com/v1';

    public function __construct(
        protected string $apiKey,
        protected string $defaultModel,
        protected int $timeout = 120,
        protected int $maxRetries = 3
    ) {
        if (empty($this->apiKey) || $this->apiKey === 'your-api-key-here') {
            throw new OpenAIAuthenticationException(
                'OpenAI API key is not configured. Set OPENAI_API_KEY in .env'
            );
        }
    }

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

        foreach ($parameters as $key => $value) {
            match ($key) {
                'temperature' => $builder->withTemperature($value),
                'max_tokens' => $builder->withMaxTokens($value),
                'top_p' => $builder->withTopP($value),
                'frequency_penalty' => $builder->withFrequencyPenalty($value),
                'presence_penalty' => $builder->withPresencePenalty($value),
                default => null,
            };
        }

        return $builder->send();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(array $payload): OpenAIResponse
    {
        $this->logRequest($payload);

        $startTime = microtime(true);

        $response = $this->retryWithBackoff(
            fn () => $this->makeRequest($payload),
            $this->maxRetries
        );

        $duration = microtime(true) - $startTime;

        $this->logResponse($response, $duration);

        return OpenAIResponse::fromArray($response);
    }

    /**
     * Wykonuje HTTP request do OpenAI API
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function makeRequest(array $payload): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post(self::API_BASE_URL . '/chat/completions', $payload);

            // Handle errors
            if ($response->status() === 401) {
                throw new OpenAIAuthenticationException(
                    'Invalid API key',
                    401
                );
            }

            if ($response->status() === 429) {
                throw new OpenAIRateLimitException(
                    'Rate limit exceeded',
                    429
                );
            }

            if ($response->status() === 400) {
                $errorMessage = $response->json()['error']['message'] ?? 'Unknown error';
                throw new OpenAIInvalidRequestException(
                    'Invalid request: ' . $errorMessage,
                    400
                );
            }

            if (in_array($response->status(), [500, 502, 503])) {
                throw new OpenAIServerException(
                    'OpenAI server error',
                    $response->status()
                );
            }

            if (! $response->successful()) {
                throw new OpenAIServerException(
                    'Unexpected error: ' . $response->body(),
                    $response->status()
                );
            }

            return $response->json();
        } catch (ConnectionException $e) {
            if (str_contains($e->getMessage(), 'timed out')) {
                throw new OpenAITimeoutException(
                    "Request timed out after {$this->timeout}s",
                    0,
                    $e
                );
            }

            throw new OpenAINetworkException(
                'Network error: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Obsługuje retry logic dla rate limiting
     */
    private function retryWithBackoff(callable $callback, int $maxAttempts): mixed
    {
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            try {
                return $callback();
            } catch (OpenAIRateLimitException|OpenAIServerException $e) {
                $attempt++;

                if ($attempt >= $maxAttempts) {
                    throw $e;
                }

                $delay = $this->calculateBackoffDelay($attempt);

                Log::channel('openai')->warning('Retrying request', [
                    'attempt' => $attempt,
                    'delay' => $delay,
                    'exception' => get_class($e),
                ]);

                sleep($delay);
            }
        }

        return null;
    }

    /**
     * Oblicza exponential backoff delay
     */
    private function calculateBackoffDelay(int $attempt): int
    {
        // Exponential backoff: 2^attempt seconds, max 60s
        return min((int) pow(2, $attempt), 60);
    }

    /**
     * Loguje request dla debugging
     *
     * @param  array<string, mixed>  $payload
     */
    private function logRequest(array $payload): void
    {
        Log::channel('openai')->debug('OpenAI Request', [
            'model' => $payload['model'],
            'messages_count' => count($payload['messages']),
            'has_response_format' => isset($payload['response_format']),
            'temperature' => $payload['temperature'],
        ]);
    }

    /**
     * Loguje response dla debugging
     *
     * @param  array<string, mixed>  $response
     */
    private function logResponse(array $response, float $duration): void
    {
        Log::channel('openai')->info('OpenAI Response', [
            'id' => $response['id'],
            'model' => $response['model'],
            'duration' => round($duration, 2) . 's',
            'tokens' => $response['usage']['total_tokens'],
            'finish_reason' => $response['choices'][0]['finish_reason'],
        ]);
    }

    public function isMock(): bool
    {
        return false;
    }

    public function getModel(): string
    {
        return $this->defaultModel;
    }
}
