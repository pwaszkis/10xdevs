<?php

namespace App\Services\OpenAI;

use App\DataTransferObjects\OpenAIResponse;

interface OpenAIService
{
    /**
     * Inicjalizuje nowy chat completion request builder
     */
    public function chat(): OpenAIRequestBuilder;

    /**
     * Wykonuje chat completion z podstawowymi parametrami
     *
     * @param  string  $systemMessage  Instrukcje dla modelu
     * @param  string  $userMessage  Wiadomość użytkownika
     * @param  array<string, mixed>|null  $responseFormat  Optional JSON schema
     * @param  array<string, mixed>  $parameters  Dodatkowe parametry (temperature, max_tokens, etc.)
     */
    public function completion(
        string $systemMessage,
        string $userMessage,
        ?array $responseFormat = null,
        array $parameters = []
    ): OpenAIResponse;

    /**
     * Wykonuje request z payload
     *
     * @param  array<string, mixed>  $payload
     */
    public function execute(array $payload): OpenAIResponse;

    /**
     * Sprawdza czy service jest w mock mode
     */
    public function isMock(): bool;

    /**
     * Pobiera nazwę używanego modelu
     */
    public function getModel(): string;
}
