<?php

namespace App\Providers;

use App\Services\OpenAI\MockOpenAIService;
use App\Services\OpenAI\OpenAIService;
use App\Services\OpenAI\RealOpenAIService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
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

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
