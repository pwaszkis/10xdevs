<?php

namespace App\DataTransferObjects;

use Illuminate\Support\Facades\Log;
use JsonException;
use RuntimeException;

class OpenAIResponse
{
    /**
     * @param  array<string, mixed>|null  $parsedContent
     * @param  array<string, mixed>  $rawResponse
     */
    public function __construct(
        public readonly string $id,
        public readonly string $model,
        public readonly string $content,
        public readonly ?array $parsedContent,
        public readonly int $promptTokens,
        public readonly int $completionTokens,
        public readonly int $totalTokens,
        public readonly string $finishReason,
        public readonly array $rawResponse
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $choice = $data['choices'][0];
        $content = $choice['message']['content'];

        // Parse JSON if structured
        $parsedContent = null;
        if (str_starts_with(trim($content), '{')) {
            try {
                $parsedContent = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                Log::channel('openai')->warning('Failed to parse structured response', [
                    'error' => $e->getMessage(),
                    'content' => $content,
                ]);
            }
        }

        return new self(
            id: $data['id'],
            model: $data['model'],
            content: $content,
            parsedContent: $parsedContent,
            promptTokens: $data['usage']['prompt_tokens'],
            completionTokens: $data['usage']['completion_tokens'],
            totalTokens: $data['usage']['total_tokens'],
            finishReason: $choice['finish_reason'],
            rawResponse: $data
        );
    }

    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getParsedContent(): ?array
    {
        return $this->parsedContent;
    }

    public function isStructured(): bool
    {
        return $this->parsedContent !== null;
    }

    /**
     * @param  class-string  $className
     */
    public function mapTo(string $className): object
    {
        if (! $this->isStructured()) {
            throw new RuntimeException('Cannot map unstructured response to DTO');
        }

        return new $className(...$this->parsedContent);
    }

    /**
     * @return array<string, int>
     */
    public function getUsage(): array
    {
        return [
            'prompt_tokens' => $this->promptTokens,
            'completion_tokens' => $this->completionTokens,
            'total_tokens' => $this->totalTokens,
        ];
    }

    public function estimatedCost(): float
    {
        $pricing = [
            'gpt-4o' => ['input' => 0.0025 / 1000, 'output' => 0.01 / 1000],
            'gpt-4o-mini' => ['input' => 0.000150 / 1000, 'output' => 0.000600 / 1000],
        ];

        $modelPricing = $pricing[$this->model] ?? $pricing['gpt-4o-mini'];

        return ($this->promptTokens * $modelPricing['input']) +
               ($this->completionTokens * $modelPricing['output']);
    }

    public function getSafeContent(): string
    {
        return htmlspecialchars($this->content, ENT_QUOTES, 'UTF-8');
    }
}
