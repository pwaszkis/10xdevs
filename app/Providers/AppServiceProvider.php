<?php

namespace App\Providers;

use App\Services\OpenAI\MockOpenAIService;
use App\Services\OpenAI\OpenAIService;
use App\Services\OpenAI\RealOpenAIService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
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
        // Policies are automatically discovered in Laravel 11
        // TravelPlanPolicy will be auto-registered for TravelPlan model

        $this->configureRateLimiting();
    }

    /**
     * Configure rate limiting for authentication endpoints.
     */
    protected function configureRateLimiting(): void
    {
        // Login rate limiting: 5 attempts per 5 minutes
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email', '');
            $key = 'login:'.$email.':'.$request->ip();

            return Limit::perMinutes(5, 5)->by($key);
        });

        // Registration rate limiting: 3 attempts per hour
        RateLimiter::for('register', function (Request $request) {
            return Limit::perHour(3)->by($request->ip());
        });

        // Email verification resend: 1 per 5 minutes
        RateLimiter::for('email-verification', function (Request $request) {
            $user = $request->user();
            $userId = $user !== null ? $user->id : $request->ip();

            return Limit::perMinutes(5, 1)->by('email-verify:'.$userId);
        });

        // Global API rate limit
        RateLimiter::for('api', function (Request $request) {
            $user = $request->user();
            $identifier = $user !== null ? $user->id : $request->ip();

            return Limit::perMinute(60)->by($identifier);
        });
    }
}
