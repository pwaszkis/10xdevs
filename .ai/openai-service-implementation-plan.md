# Plan Wdrożenia OpenAI Service - VibeTravels

## 1. Opis Usługi

Usługa OpenAI Service jest centralnym komponentem odpowiedzialnym za komunikację z OpenAI API w aplikacji VibeTravels. Zapewnia spójny interfejs do generowania planów wycieczek wykorzystując GPT-4o-mini, obsługuje structured outputs (JSON schema), oraz implementuje strategię mock dla środowiska deweloperskiego.

### Kluczowe Cechy:
- **Dual Mode Operation**: Real API (production) i Mock (development)
- **Structured Outputs**: Wykorzystanie JSON Schema dla deterministycznych odpowiedzi
- **Async Processing**: Integracja z Laravel Queue dla długotrwałych operacji
- **Error Handling**: Kompleksowa obsługa błędów z retry logic
- **Cost Optimization**: Mock mode w dev, GPT-4o-mini w production

### Architektura:
```
OpenAIService (Interface)
├── RealOpenAIService (Implementation)
├── MockOpenAIService (Implementation)
├── OpenAIRequestBuilder (Helper)
├── OpenAIResponseHandler (Helper)
└── Exceptions (Custom)
```

---

## 2. Opis Konstruktora

### RealOpenAIService Constructor

```php
public function __construct(
    protected string $apiKey,
    protected string $defaultModel,
    protected HttpClient $client,
    protected LoggerInterface $logger,
    protected int $timeout = 30,
    protected int $maxRetries = 3
)
```

**Parametry:**
- `$apiKey`: Klucz API OpenAI (z config/services.php)
- `$defaultModel`: Domyślny model (gpt-4o-mini z config)
- `$client`: HTTP client (Guzzle przez Laravel HTTP)
- `$logger`: Logger instance dla monitoringu
- `$timeout`: Timeout requestu w sekundach (default: 30s)
- `$maxRetries`: Liczba retry przy rate limiting (default: 3)

**Walidacja w konstruktorze:**
- Weryfikacja obecności API key
- Sprawdzenie formatu API key (sk-...)
- Walidacja timeout > 0
- Walidacja maxRetries >= 0

### MockOpenAIService Constructor

```php
public function __construct(
    protected string $defaultModel,
    protected LoggerInterface $logger,
    protected array $mockScenarios = []
)
```

**Parametry:**
- `$defaultModel`: Model name dla consistency (nie używany w mock)
- `$logger`: Logger dla debugging mock responses
- `$mockScenarios`: Opcjonalne predefined mock scenarios

---

## 3. Publiczne Metody i Pola

### 3.1 OpenAIService Interface

```php
interface OpenAIService
{
    /**
     * Inicjalizuje nowy chat completion request builder
     */
    public function chat(): OpenAIRequestBuilder;

    /**
     * Wykonuje chat completion z podstawowymi parametrami
     *
     * @param string $systemMessage Instrukcje dla modelu
     * @param string $userMessage Wiadomość użytkownika
     * @param array|null $responseFormat Optional JSON schema
     * @param array $parameters Dodatkowe parametry (temperature, max_tokens, etc.)
     * @return OpenAIResponse
     */
    public function completion(
        string $systemMessage,
        string $userMessage,
        ?array $responseFormat = null,
        array $parameters = []
    ): OpenAIResponse;

    /**
     * Sprawdza czy service jest w mock mode
     */
    public function isMock(): bool;

    /**
     * Pobiera nazwę używanego modelu
     */
    public function getModel(): string;
}
```

### 3.2 OpenAIRequestBuilder (Fluent Interface)

```php
class OpenAIRequestBuilder
{
    /**
     * Ustawia system message (instrukcje dla AI)
     */
    public function withSystemMessage(string $message): self;

    /**
     * Ustawia user message (pojedyncza wiadomość)
     */
    public function withUserMessage(string $message): self;

    /**
     * Ustawia pełną konwersację (multi-turn)
     */
    public function withMessages(array $messages): self;

    /**
     * Ustawia response format (JSON Schema)
     *
     * @param array $format Format zgodny z OpenAI spec:
     *   [
     *     'type' => 'json_schema',
     *     'json_schema' => [
     *       'name' => 'schema_name',
     *       'strict' => true,
     *       'schema' => [...] // JSON Schema object
     *     ]
     *   ]
     */
    public function withResponseFormat(array $format): self;

    /**
     * Nadpisuje domyślny model
     */
    public function withModel(string $model): self;

    /**
     * Temperature (0.0 - 2.0): kontroluje randomness
     * 0.0 = deterministyczny, 2.0 = bardzo kreatywny
     */
    public function withTemperature(float $temperature): self;

    /**
     * Max tokens do wygenerowania
     */
    public function withMaxTokens(int $tokens): self;

    /**
     * Top P (0.0 - 1.0): nucleus sampling
     */
    public function withTopP(float $topP): self;

    /**
     * Frequency penalty (-2.0 - 2.0): redukuje powtarzanie
     */
    public function withFrequencyPenalty(float $penalty): self;

    /**
     * Presence penalty (-2.0 - 2.0): zachęca do nowych tematów
     */
    public function withPresencePenalty(float $penalty): self;

    /**
     * Preset dla kreatywnego generowania
     */
    public function useCreativePreset(): self;

    /**
     * Preset dla precyzyjnego generowania
     */
    public function usePrecisePreset(): self;

    /**
     * Preset dla zbalansowanego generowania
     */
    public function useBalancedPreset(): self;

    /**
     * Wykonuje request i zwraca response
     */
    public function send(): OpenAIResponse;

    /**
     * Buduje array gotowy do wysłania do API (internal)
     */
    public function build(): array;
}
```

### 3.3 OpenAIResponse (DTO)

```php
class OpenAIResponse
{
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
     * Zwraca content jako string
     */
    public function getContent(): string;

    /**
     * Zwraca sparsowany JSON (jeśli response_format był ustawiony)
     */
    public function getParsedContent(): ?array;

    /**
     * Sprawdza czy response jest structured (JSON)
     */
    public function isStructured(): bool;

    /**
     * Mapuje parsed content do DTO/Model
     */
    public function mapTo(string $className): object;

    /**
     * Zwraca usage statistics
     */
    public function getUsage(): array;

    /**
     * Szacowany koszt requestu (USD)
     */
    public function estimatedCost(): float;
}
```

---

## 4. Prywatne Metody i Pola

### 4.1 RealOpenAIService Private Methods

```php
/**
 * Wykonuje HTTP request do OpenAI API
 */
private function makeRequest(array $payload): array;

/**
 * Obsługuje retry logic dla rate limiting
 */
private function retryWithBackoff(callable $callback, int $attempts): mixed;

/**
 * Waliduje response format przed wysłaniem
 */
private function validateResponseFormat(array $format): void;

/**
 * Parsuje response z API
 */
private function parseResponse(array $response): OpenAIResponse;

/**
 * Loguje request dla debugging
 */
private function logRequest(array $payload): void;

/**
 * Loguje response dla debugging
 */
private function logResponse(array $response, float $duration): void;

/**
 * Oblicza exponential backoff delay
 */
private function calculateBackoffDelay(int $attempt): int;
```

### 4.2 Private Fields

```php
// RealOpenAIService
private const API_BASE_URL = 'https://api.openai.com/v1';
private const API_VERSION = 'v1';
private const PRICING = [
    'gpt-4o' => ['input' => 0.0025, 'output' => 0.01],
    'gpt-4o-mini' => ['input' => 0.000150, 'output' => 0.000600],
];

// MockOpenAIService
private array $callHistory = [];
private int $callCount = 0;
```

---

## 5. Obsługa Błędów

### 5.1 Exception Hierarchy

```php
// Base exception
OpenAIException extends Exception

// Specific exceptions
├── OpenAIAuthenticationException (401)
├── OpenAIRateLimitException (429)
├── OpenAIInvalidRequestException (400)
├── OpenAIServerException (500, 502, 503)
├── OpenAITimeoutException
├── OpenAINetworkException
└── OpenAIValidationException
```

### 5.2 Scenariusze Błędów i Obsługa

#### 1. Authentication Error (401)
```php
// Wykrycie
if ($response->status() === 401) {
    throw new OpenAIAuthenticationException(
        'Invalid API key. Check OPENAI_API_KEY in .env',
        401
    );
}

// Obsługa w kontrolerze
catch (OpenAIAuthenticationException $e) {
    Log::critical('OpenAI auth failed', ['error' => $e->getMessage()]);
    return response()->json(['error' => 'AI service configuration error'], 500);
}
```

#### 2. Rate Limiting (429)
```php
// Retry logic z exponential backoff
private function retryWithBackoff(callable $callback, int $attempts): mixed
{
    $attempt = 0;

    while ($attempt < $attempts) {
        try {
            return $callback();
        } catch (OpenAIRateLimitException $e) {
            $attempt++;

            if ($attempt >= $attempts) {
                throw $e;
            }

            $delay = $this->calculateBackoffDelay($attempt);
            Log::warning("Rate limited, retrying in {$delay}s", [
                'attempt' => $attempt
            ]);

            sleep($delay);
        }
    }
}

private function calculateBackoffDelay(int $attempt): int
{
    return min(pow(2, $attempt), 60); // Max 60s
}
```

#### 3. Invalid Request (400)
```php
// Walidacja przed wysłaniem
private function validateResponseFormat(array $format): void
{
    if (!isset($format['type']) || $format['type'] !== 'json_schema') {
        throw new OpenAIValidationException(
            'response_format must have type: json_schema'
        );
    }

    $jsonSchema = $format['json_schema'] ?? null;

    if (!$jsonSchema || !isset($jsonSchema['name'], $jsonSchema['schema'])) {
        throw new OpenAIValidationException(
            'json_schema must contain name and schema'
        );
    }

    if (!isset($jsonSchema['strict'])) {
        throw new OpenAIValidationException(
            'json_schema must have strict: true for reliable parsing'
        );
    }
}
```

#### 4. Server Errors (500, 502, 503)
```php
// Retry dla temporary failures
if (in_array($response->status(), [500, 502, 503])) {
    throw new OpenAIServerException(
        'OpenAI API temporary error',
        $response->status()
    );
}

// W retry logic - traktuj podobnie jak 429
```

#### 5. Timeout
```php
// Laravel HTTP timeout configuration
try {
    $response = Http::timeout($this->timeout)
        ->withHeaders([...])
        ->post($url, $payload);
} catch (ConnectionException $e) {
    throw new OpenAITimeoutException(
        "Request timed out after {$this->timeout}s",
        0,
        $e
    );
}
```

#### 6. Network Errors
```php
catch (ConnectException $e) {
    throw new OpenAINetworkException(
        'Failed to connect to OpenAI API: ' . $e->getMessage(),
        0,
        $e
    );
}
```

### 5.3 Centralized Error Handling

```php
// W Service Provider
public function register(): void
{
    $this->app->bind(OpenAIService::class, function ($app) {
        try {
            if (config('services.openai.use_real_api')) {
                return new RealOpenAIService(
                    apiKey: config('services.openai.api_key'),
                    defaultModel: config('services.openai.model'),
                    client: Http::asJson(),
                    logger: Log::channel('openai')
                );
            }

            return new MockOpenAIService(
                defaultModel: config('services.openai.model'),
                logger: Log::channel('openai')
            );
        } catch (\Exception $e) {
            Log::critical('Failed to initialize OpenAI service', [
                'error' => $e->getMessage()
            ]);

            // Fallback to mock in case of configuration error
            return new MockOpenAIService(
                defaultModel: 'gpt-4o-mini',
                logger: Log::channel('openai')
            );
        }
    });
}
```

---

## 6. Kwestie Bezpieczeństwa

### 6.1 API Key Management

```php
// .env
OPENAI_API_KEY=sk-proj-xxxxxxxxxxxxx
AI_USE_REAL_API=false

// config/services.php
'openai' => [
    'api_key' => env('OPENAI_API_KEY'),
    'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    'use_real_api' => env('AI_USE_REAL_API', false),
    'timeout' => env('OPENAI_TIMEOUT', 30),
    'max_retries' => env('OPENAI_MAX_RETRIES', 3),
],
```

**Zasady:**
1. **NIE** commitować .env do git
2. API key w .env.example ustawiony na: `your-api-key-here`
3. W production używać Laravel Secrets lub Environment Variables
4. Rotate API keys co 90 dni
5. Monitor usage w OpenAI dashboard

### 6.2 Input Validation

```php
// Walidacja user input przed wysłaniem do AI
class GeneratePlanRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'destination' => 'required|string|max:200',
            'days' => 'required|integer|min:1|max:30',
            'budget' => 'required|numeric|min:0',
            'preferences' => 'nullable|array',
            'preferences.*' => 'string|max:100',
        ];
    }

    // Sanitization
    public function sanitized(): array
    {
        return [
            'destination' => strip_tags($this->destination),
            'days' => (int) $this->days,
            'budget' => (float) $this->budget,
            'preferences' => array_map('strip_tags', $this->preferences ?? []),
        ];
    }
}
```

### 6.3 Output Sanitization

```php
// Przed wyświetleniem AI-generated content
class OpenAIResponse
{
    public function getSafeContent(): string
    {
        // Dla HTML output - escape
        return htmlspecialchars($this->content, ENT_QUOTES, 'UTF-8');
    }

    public function getSafeHtml(): string
    {
        // Dla HTML output - purify
        return clean($this->content); // HTMLPurifier przez package
    }
}
```

### 6.4 Rate Limiting

```php
// Rate limit dla AI generation endpoints
// routes/web.php
Route::middleware(['auth', 'throttle:ai-generation'])->group(function () {
    Route::post('/plans/generate', [PlanController::class, 'generate']);
});

// app/Providers/RouteServiceProvider.php
RateLimiter::for('ai-generation', function (Request $request) {
    return Limit::perMinute(3) // Max 3 generowania/minutę
        ->by($request->user()->id)
        ->response(function () {
            return response()->json([
                'error' => 'Too many AI generations. Please wait before trying again.'
            ], 429);
        });
});
```

### 6.5 Cost Protection

```php
// Monitor daily costs
class OpenAICostMonitor
{
    public function trackUsage(OpenAIResponse $response, User $user): void
    {
        DailyAIUsage::create([
            'user_id' => $user->id,
            'tokens_used' => $response->totalTokens,
            'estimated_cost' => $response->estimatedCost(),
            'model' => $response->model,
            'created_at' => now(),
        ]);

        // Alert jeśli daily cost > threshold
        $todayCost = DailyAIUsage::whereDate('created_at', today())
            ->sum('estimated_cost');

        if ($todayCost > 10.0) { // $10 daily limit
            Log::alert('Daily AI cost exceeded $10', ['cost' => $todayCost]);

            // Optional: disable AI generation
            Cache::put('ai_generation_disabled', true, now()->endOfDay());
        }
    }
}
```

### 6.6 Content Policy Compliance

```php
// Pre-flight check dla potentially problematic content
private function checkContentPolicy(string $userMessage): void
{
    $blockedPatterns = [
        '/\b(hack|exploit|vulnerability)\b/i',
        '/\b(illegal|fraud|scam)\b/i',
        // Add more patterns
    ];

    foreach ($blockedPatterns as $pattern) {
        if (preg_match($pattern, $userMessage)) {
            Log::warning('Blocked AI request - policy violation', [
                'pattern' => $pattern,
                'user_id' => auth()->id(),
            ]);

            throw new OpenAIValidationException(
                'Request violates content policy'
            );
        }
    }
}
```

---

## 7. Plan Wdrożenia Krok Po Kroku

### FAZA 1: Setup i Konfiguracja (1-2 godziny)

#### Krok 1.1: Instalacja Package

```bash
composer require openai-php/laravel
```

#### Krok 1.2: Konfiguracja

```bash
php artisan vendor:publish --provider="OpenAI\Laravel\ServiceProvider"
```

Edytuj `config/openai.php`:
```php
return [
    'api_key' => env('OPENAI_API_KEY'),
    'organization' => env('OPENAI_ORGANIZATION'),
];
```

Edytuj `config/services.php`:
```php
'openai' => [
    'api_key' => env('OPENAI_API_KEY'),
    'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    'use_real_api' => env('AI_USE_REAL_API', false),
    'timeout' => env('OPENAI_TIMEOUT', 30),
    'max_retries' => env('OPENAI_MAX_RETRIES', 3),
],
```

Edytuj `.env`:
```env
OPENAI_API_KEY=your-api-key-here
OPENAI_MODEL=gpt-4o-mini
AI_USE_REAL_API=false
OPENAI_TIMEOUT=30
OPENAI_MAX_RETRIES=3
```

#### Krok 1.3: Logging Channel

Edytuj `config/logging.php`:
```php
'channels' => [
    // ... inne channels

    'openai' => [
        'driver' => 'daily',
        'path' => storage_path('logs/openai.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 14,
    ],
],
```

---

### FAZA 2: Exception Classes (30 minut)

#### Krok 2.1: Utwórz Base Exception

```bash
php artisan make:exception OpenAIException
```

Edytuj `app/Exceptions/OpenAIException.php`:
```php
<?php

namespace App\Exceptions;

use Exception;

class OpenAIException extends Exception
{
    protected array $context = [];

    public function __construct(
        string $message = "",
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function report(): void
    {
        \Log::channel('openai')->error($this->getMessage(), [
            'code' => $this->getCode(),
            'context' => $this->context,
            'trace' => $this->getTraceAsString(),
        ]);
    }
}
```

#### Krok 2.2: Utwórz Specific Exceptions

```bash
php artisan make:exception OpenAIAuthenticationException
php artisan make:exception OpenAIRateLimitException
php artisan make:exception OpenAIInvalidRequestException
php artisan make:exception OpenAIServerException
php artisan make:exception OpenAITimeoutException
php artisan make:exception OpenAINetworkException
php artisan make:exception OpenAIValidationException
```

Dla każdej z nich:
```php
<?php

namespace App\Exceptions;

class OpenAIAuthenticationException extends OpenAIException
{
    // Specific implementation if needed
}
```

---

### FAZA 3: DTOs (Data Transfer Objects) (1 godzina)

#### Krok 3.1: OpenAIResponse DTO

Utwórz `app/DataTransferObjects/OpenAIResponse.php`:
```php
<?php

namespace App\DataTransferObjects;

class OpenAIResponse
{
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

    public static function fromArray(array $data): self
    {
        $choice = $data['choices'][0];
        $content = $choice['message']['content'];

        // Parse JSON if structured
        $parsedContent = null;
        if (str_starts_with(trim($content), '{')) {
            try {
                $parsedContent = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                \Log::channel('openai')->warning('Failed to parse structured response', [
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

    public function getParsedContent(): ?array
    {
        return $this->parsedContent;
    }

    public function isStructured(): bool
    {
        return $this->parsedContent !== null;
    }

    public function mapTo(string $className): object
    {
        if (!$this->isStructured()) {
            throw new \RuntimeException('Cannot map unstructured response to DTO');
        }

        return new $className(...$this->parsedContent);
    }

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
```

---

### FAZA 4: Request Builder (2 godziny)

#### Krok 4.1: Utwórz Builder

Utwórz `app/Services/OpenAI/OpenAIRequestBuilder.php`:
```php
<?php

namespace App\Services\OpenAI;

use App\DataTransferObjects\OpenAIResponse;
use App\Exceptions\OpenAIValidationException;

class OpenAIRequestBuilder
{
    private array $messages = [];
    private ?string $model = null;
    private ?array $responseFormat = null;
    private float $temperature = 0.7;
    private ?int $maxTokens = null;
    private float $topP = 1.0;
    private float $frequencyPenalty = 0.0;
    private float $presencePenalty = 0.0;

    public function __construct(
        private readonly OpenAIService $service,
        private readonly string $defaultModel
    ) {
        $this->model = $defaultModel;
    }

    public function withSystemMessage(string $message): self
    {
        $this->messages[] = [
            'role' => 'system',
            'content' => $message,
        ];

        return $this;
    }

    public function withUserMessage(string $message): self
    {
        $this->messages[] = [
            'role' => 'user',
            'content' => $message,
        ];

        return $this;
    }

    public function withMessages(array $messages): self
    {
        $this->messages = $messages;
        return $this;
    }

    public function withResponseFormat(array $format): self
    {
        $this->validateResponseFormat($format);
        $this->responseFormat = $format;
        return $this;
    }

    public function withModel(string $model): self
    {
        $this->model = $model;
        return $this;
    }

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

    public function withMaxTokens(int $tokens): self
    {
        if ($tokens < 1) {
            throw new OpenAIValidationException('Max tokens must be positive');
        }

        $this->maxTokens = $tokens;
        return $this;
    }

    public function withTopP(float $topP): self
    {
        if ($topP < 0.0 || $topP > 1.0) {
            throw new OpenAIValidationException('Top P must be between 0.0 and 1.0');
        }

        $this->topP = $topP;
        return $this;
    }

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

    public function useCreativePreset(): self
    {
        $this->temperature = 0.9;
        $this->topP = 0.95;
        $this->frequencyPenalty = 0.5;
        $this->presencePenalty = 0.5;

        return $this;
    }

    public function usePrecisePreset(): self
    {
        $this->temperature = 0.2;
        $this->topP = 0.8;
        $this->frequencyPenalty = 0.0;
        $this->presencePenalty = 0.0;

        return $this;
    }

    public function useBalancedPreset(): self
    {
        $this->temperature = 0.7;
        $this->topP = 1.0;
        $this->frequencyPenalty = 0.0;
        $this->presencePenalty = 0.0;

        return $this;
    }

    public function send(): OpenAIResponse
    {
        if (empty($this->messages)) {
            throw new OpenAIValidationException('At least one message is required');
        }

        $payload = $this->build();
        return $this->service->execute($payload);
    }

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

    private function validateResponseFormat(array $format): void
    {
        if (!isset($format['type']) || $format['type'] !== 'json_schema') {
            throw new OpenAIValidationException(
                'response_format must have type: json_schema'
            );
        }

        $jsonSchema = $format['json_schema'] ?? null;

        if (!$jsonSchema || !isset($jsonSchema['name'], $jsonSchema['schema'])) {
            throw new OpenAIValidationException(
                'json_schema must contain name and schema'
            );
        }

        if (!isset($jsonSchema['strict']) || $jsonSchema['strict'] !== true) {
            throw new OpenAIValidationException(
                'json_schema must have strict: true for reliable parsing'
            );
        }
    }
}
```

---

### FAZA 5: Service Interface i Implementacje (3-4 godziny)

#### Krok 5.1: Interface

Utwórz `app/Services/OpenAI/OpenAIService.php`:
```php
<?php

namespace App\Services\OpenAI;

use App\DataTransferObjects\OpenAIResponse;

interface OpenAIService
{
    public function chat(): OpenAIRequestBuilder;

    public function completion(
        string $systemMessage,
        string $userMessage,
        ?array $responseFormat = null,
        array $parameters = []
    ): OpenAIResponse;

    public function execute(array $payload): OpenAIResponse;

    public function isMock(): bool;

    public function getModel(): string;
}
```

#### Krok 5.2: Real Implementation

Utwórz `app/Services/OpenAI/RealOpenAIService.php`:
```php
<?php

namespace App\Services\OpenAI;

use App\DataTransferObjects\OpenAIResponse;
use App\Exceptions\{
    OpenAIAuthenticationException,
    OpenAIRateLimitException,
    OpenAIInvalidRequestException,
    OpenAIServerException,
    OpenAITimeoutException,
    OpenAINetworkException
};
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;

class RealOpenAIService implements OpenAIService
{
    private const API_BASE_URL = 'https://api.openai.com/v1';

    public function __construct(
        protected string $apiKey,
        protected string $defaultModel,
        protected int $timeout = 30,
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

    public function execute(array $payload): OpenAIResponse
    {
        $this->logRequest($payload);

        $startTime = microtime(true);

        $response = $this->retryWithBackoff(
            fn() => $this->makeRequest($payload),
            $this->maxRetries
        );

        $duration = microtime(true) - $startTime;

        $this->logResponse($response, $duration);

        return OpenAIResponse::fromArray($response);
    }

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
                throw new OpenAIInvalidRequestException(
                    'Invalid request: ' . ($response->json()['error']['message'] ?? 'Unknown error'),
                    400
                );
            }

            if (in_array($response->status(), [500, 502, 503])) {
                throw new OpenAIServerException(
                    'OpenAI server error',
                    $response->status()
                );
            }

            if (!$response->successful()) {
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
    }

    private function calculateBackoffDelay(int $attempt): int
    {
        // Exponential backoff: 2^attempt seconds, max 60s
        return min(pow(2, $attempt), 60);
    }

    private function logRequest(array $payload): void
    {
        Log::channel('openai')->debug('OpenAI Request', [
            'model' => $payload['model'],
            'messages_count' => count($payload['messages']),
            'has_response_format' => isset($payload['response_format']),
            'temperature' => $payload['temperature'],
        ]);
    }

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
```

#### Krok 5.3: Mock Implementation

Utwórz `app/Services/OpenAI/MockOpenAIService.php`:
```php
<?php

namespace App\Services\OpenAI;

use App\DataTransferObjects\OpenAIResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MockOpenAIService implements OpenAIService
{
    private array $callHistory = [];
    private int $callCount = 0;

    public function __construct(
        protected string $defaultModel,
        protected array $mockScenarios = []
    ) {}

    public function chat(): OpenAIRequestBuilder
    {
        return new OpenAIRequestBuilder($this, $this->defaultModel);
    }

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

    private function generateStructuredMockResponse(array $payload): array
    {
        $schemaName = $payload['response_format']['json_schema']['name'] ?? 'response';

        // Generate mock data based on schema
        $mockContent = match ($schemaName) {
            'travel_itinerary' => json_encode([
                'destination' => 'Paris, France',
                'days' => [
                    [
                        'day_number' => 1,
                        'activities' => [
                            [
                                'time' => '09:00',
                                'activity' => 'Visit Eiffel Tower',
                                'location' => 'Champ de Mars',
                                'cost_estimate' => 26.0,
                            ],
                            [
                                'time' => '14:00',
                                'activity' => 'Louvre Museum',
                                'location' => 'Rue de Rivoli',
                                'cost_estimate' => 17.0,
                            ],
                        ],
                    ],
                    [
                        'day_number' => 2,
                        'activities' => [
                            [
                                'time' => '10:00',
                                'activity' => 'Notre-Dame Cathedral',
                                'location' => 'Île de la Cité',
                                'cost_estimate' => 0.0,
                            ],
                        ],
                    ],
                ],
            ]),
            default => json_encode([
                'result' => 'Mock structured response',
                'schema' => $schemaName,
            ]),
        };

        return [
            'id' => 'chatcmpl-mock-' . Str::random(10),
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

    private function generateTextMockResponse(array $payload): array
    {
        $mockContent = "This is a mock response from the OpenAI service. " .
                      "In production, this would be a real AI-generated response. " .
                      "Your request had " . count($payload['messages']) . " messages.";

        return [
            'id' => 'chatcmpl-mock-' . Str::random(10),
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

    public function getCallHistory(): array
    {
        return $this->callHistory;
    }

    public function getCallCount(): int
    {
        return $this->callCount;
    }
}
```

---

### FAZA 6: Service Provider (30 minut)

#### Krok 6.1: Zarejestruj Service

Edytuj `app/Providers/AppServiceProvider.php`:
```php
<?php

namespace App\Providers;

use App\Services\OpenAI\OpenAIService;
use App\Services\OpenAI\RealOpenAIService;
use App\Services\OpenAI\MockOpenAIService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OpenAIService::class, function ($app) {
            $config = config('services.openai');
            $useRealApi = $config['use_real_api'] ?? false;
            $model = $config['model'] ?? 'gpt-4o-mini';

            Log::channel('openai')->info('Initializing OpenAI Service', [
                'mode' => $useRealApi ? 'real' : 'mock',
                'model' => $model,
            ]);

            if ($useRealApi) {
                try {
                    return new RealOpenAIService(
                        apiKey: $config['api_key'],
                        defaultModel: $model,
                        timeout: $config['timeout'] ?? 30,
                        maxRetries: $config['max_retries'] ?? 3
                    );
                } catch (\Exception $e) {
                    Log::channel('openai')->critical('Failed to initialize Real OpenAI Service', [
                        'error' => $e->getMessage(),
                    ]);

                    // Fallback to mock
                    return new MockOpenAIService(
                        defaultModel: $model
                    );
                }
            }

            return new MockOpenAIService(
                defaultModel: $model
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
```

---

### FAZA 7: Przykładowe Użycie (1 godzina)

#### Krok 7.1: Utwórz JSON Schema

Utwórz `app/Services/OpenAI/Schemas/TravelItinerarySchema.php`:
```php
<?php

namespace App\Services\OpenAI\Schemas;

class TravelItinerarySchema
{
    public static function get(): array
    {
        return [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => 'travel_itinerary',
                'strict' => true,
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'destination' => [
                            'type' => 'string',
                            'description' => 'The destination city and country',
                        ],
                        'duration_days' => [
                            'type' => 'integer',
                            'description' => 'Number of days for the trip',
                        ],
                        'days' => [
                            'type' => 'array',
                            'description' => 'Daily itinerary',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'day_number' => [
                                        'type' => 'integer',
                                        'description' => 'Day number (1-indexed)',
                                    ],
                                    'date' => [
                                        'type' => 'string',
                                        'description' => 'Date in YYYY-MM-DD format',
                                    ],
                                    'activities' => [
                                        'type' => 'array',
                                        'description' => 'Activities for the day',
                                        'items' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'time' => [
                                                    'type' => 'string',
                                                    'description' => 'Time in HH:MM format',
                                                ],
                                                'activity' => [
                                                    'type' => 'string',
                                                    'description' => 'Description of activity',
                                                ],
                                                'location' => [
                                                    'type' => 'string',
                                                    'description' => 'Location name',
                                                ],
                                                'cost_estimate' => [
                                                    'type' => 'number',
                                                    'description' => 'Estimated cost in USD',
                                                ],
                                                'category' => [
                                                    'type' => 'string',
                                                    'enum' => ['sightseeing', 'food', 'entertainment', 'shopping', 'relaxation', 'transport'],
                                                    'description' => 'Activity category',
                                                ],
                                            ],
                                            'required' => ['time', 'activity', 'location', 'cost_estimate', 'category'],
                                            'additionalProperties' => false,
                                        ],
                                    ],
                                    'daily_budget' => [
                                        'type' => 'number',
                                        'description' => 'Total budget for the day',
                                    ],
                                ],
                                'required' => ['day_number', 'date', 'activities', 'daily_budget'],
                                'additionalProperties' => false,
                            ],
                        ],
                        'total_cost_estimate' => [
                            'type' => 'number',
                            'description' => 'Total estimated cost for entire trip',
                        ],
                        'tips' => [
                            'type' => 'array',
                            'description' => 'General tips for the trip',
                            'items' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                    'required' => ['destination', 'duration_days', 'days', 'total_cost_estimate', 'tips'],
                    'additionalProperties' => false,
                ],
            ],
        ];
    }
}
```

#### Krok 7.2: Utwórz Action/Job

```bash
php artisan make:job GenerateTravelPlanJob
```

Edytuj `app/Jobs/GenerateTravelPlanJob.php`:
```php
<?php

namespace App\Jobs;

use App\Models\Plan;
use App\Services\OpenAI\OpenAIService;
use App\Services\OpenAI\Schemas\TravelItinerarySchema;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateTravelPlanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120; // 2 minutes
    public $tries = 2;

    public function __construct(
        public Plan $plan
    ) {}

    public function handle(OpenAIService $openAI): void
    {
        Log::channel('openai')->info('Starting plan generation', [
            'plan_id' => $this->plan->id,
        ]);

        try {
            // Update status
            $this->plan->update(['status' => 'generating']);

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

            // Save result
            $this->plan->update([
                'status' => 'completed',
                'content' => $response->getParsedContent(),
                'ai_response_id' => $response->id,
                'tokens_used' => $response->totalTokens,
                'cost' => $response->estimatedCost(),
                'completed_at' => now(),
            ]);

            Log::channel('openai')->info('Plan generated successfully', [
                'plan_id' => $this->plan->id,
                'tokens' => $response->totalTokens,
                'cost' => $response->estimatedCost(),
            ]);

        } catch (\Exception $e) {
            Log::channel('openai')->error('Plan generation failed', [
                'plan_id' => $this->plan->id,
                'error' => $e->getMessage(),
            ]);

            $this->plan->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function buildSystemMessage(): string
    {
        return <<<PROMPT
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
        $preferences = $this->plan->preferences;

        return <<<MESSAGE
Create a {$this->plan->days}-day travel itinerary for {$this->plan->destination}.

Budget: ${$this->plan->budget} USD
Traveler preferences:
- Interests: {$this->formatArray($preferences['interests'] ?? [])}
- Travel style: {$preferences['travel_style'] ?? 'balanced'}
- Dietary restrictions: {$this->formatArray($preferences['dietary_restrictions'] ?? [])}
- Accommodation type: {$preferences['accommodation_type'] ?? 'hotel'}

Please provide a comprehensive day-by-day itinerary with activities,
locations, times, and cost estimates.
MESSAGE;
    }

    private function formatArray(array $items): string
    {
        return empty($items) ? 'None specified' : implode(', ', $items);
    }
}
```

#### Krok 7.3: Przykład użycia w kontrolerze

```php
<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateTravelPlanJob;
use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'destination' => 'required|string|max:200',
            'days' => 'required|integer|min:1|max:30',
            'budget' => 'required|numeric|min:0',
            'preferences' => 'nullable|array',
        ]);

        // Create plan record
        $plan = Plan::create([
            'user_id' => auth()->id(),
            'destination' => $validated['destination'],
            'days' => $validated['days'],
            'budget' => $validated['budget'],
            'preferences' => $validated['preferences'] ?? [],
            'status' => 'pending',
        ]);

        // Dispatch job
        GenerateTravelPlanJob::dispatch($plan);

        return response()->json([
            'message' => 'Plan generation started',
            'plan_id' => $plan->id,
        ]);
    }
}
```

---

### FAZA 8: Testing (2 godziny)

#### Krok 8.1: Feature Test

```bash
php artisan make:test OpenAIServiceTest
```

```php
<?php

namespace Tests\Feature;

use App\Services\OpenAI\OpenAIService;
use App\Services\OpenAI\MockOpenAIService;
use App\Services\OpenAI\Schemas\TravelItinerarySchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpenAIServiceTest extends TestCase
{
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
    }
}
```

---

### FAZA 9: Monitoring i Usage Tracking (1 godzina)

#### Krok 9.1: Migration dla AI Usage

```bash
php artisan make:migration create_ai_usage_logs_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('model');
            $table->integer('prompt_tokens');
            $table->integer('completion_tokens');
            $table->integer('total_tokens');
            $table->decimal('estimated_cost', 10, 6);
            $table->string('request_type')->default('chat');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
    }
};
```

#### Krok 9.2: Model

```bash
php artisan make:model AIUsageLog
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AIUsageLog extends Model
{
    protected $fillable = [
        'user_id',
        'model',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'estimated_cost',
        'request_type',
        'error_message',
    ];

    protected $casts = [
        'estimated_cost' => 'decimal:6',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

#### Krok 9.3: Observer

```bash
php artisan make:observer AIUsageLogObserver
```

Zintegruj w `GenerateTravelPlanJob`:
```php
// Po successful generation
AIUsageLog::create([
    'user_id' => $this->plan->user_id,
    'model' => $response->model,
    'prompt_tokens' => $response->promptTokens,
    'completion_tokens' => $response->completionTokens,
    'total_tokens' => $response->totalTokens,
    'estimated_cost' => $response->estimatedCost(),
    'request_type' => 'travel_plan',
]);
```

---

## 8. Checklist Implementacji

### Setup
- [ ] Zainstalować `openai-php/laravel` package
- [ ] Skonfigurować `config/services.php`
- [ ] Dodać zmienne do `.env`
- [ ] Utworzyć logging channel `openai`
- [ ] Dodać API key do OpenAI (production)

### Exceptions
- [ ] Utworzyć `OpenAIException` base class
- [ ] Utworzyć specific exceptions (7 klas)
- [ ] Zaimplementować `report()` method w każdej

### DTOs
- [ ] Utworzyć `OpenAIResponse` DTO
- [ ] Zaimplementować `fromArray()` factory
- [ ] Dodać helper methods (mapTo, estimatedCost, etc.)

### Services
- [ ] Utworzyć `OpenAIService` interface
- [ ] Zaimplementować `RealOpenAIService`
- [ ] Zaimplementować `MockOpenAIService`
- [ ] Utworzyć `OpenAIRequestBuilder`
- [ ] Dodać validation w builder

### Configuration
- [ ] Zarejestrować service w `AppServiceProvider`
- [ ] Skonfigurować mode switching (mock/real)
- [ ] Dodać fallback logic

### Schemas
- [ ] Utworzyć `TravelItinerarySchema`
- [ ] Walidować schema format
- [ ] Dodać więcej schemas według potrzeb

### Integration
- [ ] Utworzyć `GenerateTravelPlanJob`
- [ ] Zaimplementować system i user messages
- [ ] Dodać error handling w job
- [ ] Utworzyć controller endpoint

### Database
- [ ] Migrations dla `plans` table
- [ ] Migration dla `ai_usage_logs`
- [ ] Utworzyć Models

### Testing
- [ ] Unit testy dla builder
- [ ] Feature testy dla service
- [ ] Integration testy dla jobs
- [ ] Test mock scenarios

### Monitoring
- [ ] Logging wszystkich requestów
- [ ] Tracking usage i costs
- [ ] Alert dla daily limits
- [ ] Dashboard dla usage statistics

### Security
- [ ] Rate limiting na endpoints
- [ ] Input sanitization
- [ ] Output sanitization
- [ ] API key rotation plan
- [ ] Cost protection mechanisms

---

## 9. Najczęstsze Problemy i Rozwiązania

### Problem 1: "Invalid API Key"
**Objawy**: 401 error przy każdym requeście

**Rozwiązanie**:
```bash
# Sprawdź czy API key jest ustawiony
php artisan tinker
config('services.openai.api_key')

# Jeśli null, sprawdź .env
cat .env | grep OPENAI

# Clear config cache
php artisan config:clear
```

### Problem 2: "Rate Limit Exceeded"
**Objawy**: 429 error, zbyt wiele requestów

**Rozwiązanie**:
- Zwiększ `max_retries` w config
- Dodaj delay między requestami
- Sprawdź limits w OpenAI dashboard
- Rozważ tier upgrade

### Problem 3: Schema Validation Fails
**Objawy**: Model zwraca text zamiast JSON

**Rozwiązanie**:
```php
// Upewnij się że schema ma strict: true
'json_schema' => [
    'name' => 'schema_name',
    'strict' => true,  // REQUIRED!
    'schema' => [...]
]

// Sprawdź czy schema jest valid JSON Schema
// Użyj online validator: https://www.jsonschemavalidator.net/
```

### Problem 4: Timeout
**Objawy**: Request timeout po 30s

**Rozwiązanie**:
```php
// Zwiększ timeout w config
'timeout' => 60, // 60 seconds

// Lub per-request
$service->chat()
    ->withMaxTokens(1000) // Ogranicz response size
    ->send();
```

### Problem 5: Mock nie działa w testach
**Objawy**: Real API calls w testach

**Rozwiązanie**:
```php
// W phpunit.xml
<env name="AI_USE_REAL_API" value="false"/>

// Lub w test
protected function setUp(): void
{
    parent::setUp();
    config(['services.openai.use_real_api' => false]);
}
```

---

## 10. Podsumowanie

Ten przewodnik zawiera kompletną implementację OpenAI Service dla aplikacji VibeTravels. Kluczowe punkty:

1. **Dual Mode**: Mock (dev) i Real API (production) - zero kosztów w development
2. **Structured Outputs**: JSON Schema dla deterministycznych odpowiedzi
3. **Error Handling**: Comprehensive exception handling z retry logic
4. **Security**: Input validation, rate limiting, cost protection
5. **Monitoring**: Logging, usage tracking, cost estimation
6. **Testing**: Mock service dla szybkich testów bez kosztów

### Next Steps po implementacji:
1. Przetestować wszystkie flows w mock mode
2. Dodać API key do production
3. Ustawić `AI_USE_REAL_API=true` w production .env
4. Monitor costs w pierwszych dniach
5. Dostosować rate limits według użycia
6. Rozszerzyć schemas dla innych use cases

### Szacowany czas implementacji: 12-16 godzin
### Oczekiwane koszty MVP: $3-30/miesiąc
