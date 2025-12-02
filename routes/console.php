<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily: Auto-complete past trips
Schedule::command('plans:auto-complete')->daily();

// Monthly: Reset AI limits (on 1st of month at 00:01)
Schedule::command('limits:reset-monthly')
    ->monthlyOn(1, '00:01')
    ->timezone('Europe/Warsaw');

// Queue Monitoring & Maintenance
// Monitor queue health - alert if queue size exceeds 10 jobs (indicates worker issues)
Schedule::command('queue:monitor redis:ai-generation,redis:default --max=10')
    ->everyFiveMinutes()
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::channel('stack')->critical('Queue backlog detected! Worker may be stuck.', [
            'ai_generation_queue_size' => \Illuminate\Support\Facades\Redis::llen('queues:ai-generation'),
            'default_queue_size' => \Illuminate\Support\Facades\Redis::llen('queues:default'),
            'timestamp' => now()->toIso8601String(),
        ]);
    });

// Clean up old failed jobs (older than 7 days) to prevent database bloat
Schedule::command('queue:prune-failed --hours=168')
    ->daily()
    ->at('02:00');

// Gracefully restart queue workers daily at 3am (off-peak hours)
// Forces fresh start, clears any memory leaks or zombie states
Schedule::command('queue:restart')
    ->dailyAt('03:00')
    ->before(function () {
        \Illuminate\Support\Facades\Log::channel('stack')->info('Scheduled queue restart initiated');
    })
    ->after(function () {
        \Illuminate\Support\Facades\Log::channel('stack')->info('Scheduled queue restart completed');
    });
