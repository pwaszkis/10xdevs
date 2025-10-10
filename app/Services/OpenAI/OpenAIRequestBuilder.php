<?php

namespace App\Services\OpenAI;

use App\DataTransferObjects\OpenAIResponse;
use App\Exceptions\OpenAIValidationException;

class OpenAIRequestBuilder
{
    /** @var array<int, array<string, string>> */
    private array $messages = [];

    private ?string $model = null;

    /** @var array<string, mixed>|null */
    private ?array $responseFormat = null;

    private float $temperature = 0.7;

    private ?int $maxTokens = null;

    private float $topP = 1.0;

    private float $frequencyPenalty = 0.0;

    private float $presencePenalty = 0.0;

    public function __construct(
        private readonly OpenAIService $service,
        string $defaultModel
    ) {
        $this->model = $defaultModel;
    }

    /**
     * Ustawia system message (instrukcje dla AI)
     */
    public function withSystemMessage(string $message): self
    {
        $this->messages[] = [
            'role' => 'system',
            'content' => $message,
        ];

        return $this;
    }

    /**
     * Ustawia user message (pojedyncza wiadomość)
     */
    public function withUserMessage(string $message): self
    {
        $this->messages[] = [
            'role' => 'user',
            'content' => $message,
        ];

        return $this;
    }

    /**
     * Ustawia pełną konwersację (multi-turn)
     *
     * @param  array<int, array<string, string>>  $messages
     */
    public function withMessages(array $messages): self
    {
        $this->messages = $messages;

        return $this;
    }

    /**
     * Ustawia response format (JSON Schema)
     *
     * @param  array<string, mixed>  $format  Format zgodny z OpenAI spec
     */
    public function withResponseFormat(array $format): self
    {
        $this->validateResponseFormat($format);
        $this->responseFormat = $format;

        return $this;
    }

    /**
     * Nadpisuje domyślny model
     */
    public function withModel(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Temperature (0.0 - 2.0): kontroluje randomness
     * 0.0 = deterministyczny, 2.0 = bardzo kreatywny
     */
    public function withTemperature(float $temperature): self
    {
        if ($temperature < 0.0 || $temperature > 2.0) {
            throw new OpenAIValidationException(
                'Temperature must be between 0.0 and 2.0'
            );
        }

        $this->temperature = $temperature;

        return $this;
    }

    /**
     * Max tokens do wygenerowania
     */
    public function withMaxTokens(int $tokens): self
    {
        if ($tokens < 1) {
            throw new OpenAIValidationException('Max tokens must be positive');
        }

        $this->maxTokens = $tokens;

        return $this;
    }

    /**
     * Top P (0.0 - 1.0): nucleus sampling
     */
    public function withTopP(float $topP): self
    {
        if ($topP < 0.0 || $topP > 1.0) {
            throw new OpenAIValidationException('Top P must be between 0.0 and 1.0');
        }

        $this->topP = $topP;

        return $this;
    }

    /**
     * Frequency penalty (-2.0 - 2.0): redukuje powtarzanie
     */
    public function withFrequencyPenalty(float $penalty): self
    {
        if ($penalty < -2.0 || $penalty > 2.0) {
            throw new OpenAIValidationException(
                'Frequency penalty must be between -2.0 and 2.0'
            );
        }

        $this->frequencyPenalty = $penalty;

        return $this;
    }

    /**
     * Presence penalty (-2.0 - 2.0): zachęca do nowych tematów
     */
    public function withPresencePenalty(float $penalty): self
    {
        if ($penalty < -2.0 || $penalty > 2.0) {
            throw new OpenAIValidationException(
                'Presence penalty must be between -2.0 and 2.0'
            );
        }

        $this->presencePenalty = $penalty;

        return $this;
    }

    /**
     * Preset dla kreatywnego generowania
     */
    public function useCreativePreset(): self
    {
        $this->temperature = 0.9;
        $this->topP = 0.95;
        $this->frequencyPenalty = 0.5;
        $this->presencePenalty = 0.5;

        return $this;
    }

    /**
     * Preset dla precyzyjnego generowania
     */
    public function usePrecisePreset(): self
    {
        $this->temperature = 0.2;
        $this->topP = 0.8;
        $this->frequencyPenalty = 0.0;
        $this->presencePenalty = 0.0;

        return $this;
    }

    /**
     * Preset dla zbalansowanego generowania
     */
    public function useBalancedPreset(): self
    {
        $this->temperature = 0.7;
        $this->topP = 1.0;
        $this->frequencyPenalty = 0.0;
        $this->presencePenalty = 0.0;

        return $this;
    }

    /**
     * Wykonuje request i zwraca response
     */
    public function send(): OpenAIResponse
    {
        if (empty($this->messages)) {
            throw new OpenAIValidationException('At least one message is required');
        }

        $payload = $this->build();

        return $this->service->execute($payload);
    }

    /**
     * Buduje array gotowy do wysłania do API (internal)
     *
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $payload = [
            'model' => $this->model,
            'messages' => $this->messages,
            'temperature' => $this->temperature,
            'top_p' => $this->topP,
            'frequency_penalty' => $this->frequencyPenalty,
            'presence_penalty' => $this->presencePenalty,
        ];

        if ($this->maxTokens !== null) {
            $payload['max_tokens'] = $this->maxTokens;
        }

        if ($this->responseFormat !== null) {
            $payload['response_format'] = $this->responseFormat;
        }

        return $payload;
    }

    /**
     * Waliduje response format przed wysłaniem
     *
     * @param  array<string, mixed>  $format
     */
    private function validateResponseFormat(array $format): void
    {
        if (! isset($format['type']) || $format['type'] !== 'json_schema') {
            throw new OpenAIValidationException(
                'response_format must have type: json_schema'
            );
        }

        $jsonSchema = $format['json_schema'] ?? null;

        if (! $jsonSchema || ! isset($jsonSchema['name'], $jsonSchema['schema'])) {
            throw new OpenAIValidationException(
                'json_schema must contain name and schema'
            );
        }

        if (! isset($jsonSchema['strict']) || $jsonSchema['strict'] !== true) {
            throw new OpenAIValidationException(
                'json_schema must have strict: true for reliable parsing'
            );
        }
    }
}
