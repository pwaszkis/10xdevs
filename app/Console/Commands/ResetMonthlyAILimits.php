<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ResetMonthlyAILimits extends Command
{
    protected $signature = 'limits:reset-monthly';

    protected $description = 'Reset monthly AI generation limits (automatic on 1st of month)';

    public function handle(): int
    {
        // Note: Limits are calculated from ai_generations table, not user fields
        // This command is placeholder for future email notifications
        // Actual limit checking is done by LimitService->getGenerationCount()

        $this->info('Monthly AI limits reset (tracked via AIGeneration table)');
        $this->info('Reset date: ' . now()->toDateString());

        // Future: Send "limits renewed" email to active users
        // Mail::to($activeUsers)->queue(new LimitsRenewedEmail());

        return Command::SUCCESS;
    }
}
