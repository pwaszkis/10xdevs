<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AIGeneration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Cleanup Stuck AI Generations Command
 *
 * Finds and marks as failed any AI generations that are stuck in pending/processing
 * status for longer than the job timeout (120 seconds + buffer).
 *
 * Should be run periodically (e.g., every 5 minutes via cron).
 */
class CleanupStuckGenerations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:cleanup-stuck-generations
                            {--dry-run : Show what would be cleaned up without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark stuck AI generations as failed (pending/processing for >3 minutes)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $timeoutMinutes = 3; // Job timeout is 2 minutes + 1 minute buffer
        $cutoffTime = now()->subMinutes($timeoutMinutes);

        $this->info("Looking for stuck AI generations older than {$timeoutMinutes} minutes...");

        // Find stuck generations
        $stuckGenerations = AIGeneration::whereIn('status', ['pending', 'processing'])
            ->where('created_at', '<', $cutoffTime)
            ->get();

        if ($stuckGenerations->isEmpty()) {
            $this->info('No stuck generations found.');

            return self::SUCCESS;
        }

        $count = $stuckGenerations->count();
        $this->warn("Found {$count} stuck generation(s):");

        $stuckGenerations->each(function (AIGeneration $generation) use ($dryRun) {
            $ageMinutes = abs(now()->diffInMinutes($generation->created_at));

            $this->line(sprintf(
                '  - ID: %d | Plan: %d | Status: %s | Age: %d minutes',
                $generation->id,
                $generation->travel_plan_id,
                $generation->status,
                $ageMinutes
            ));

            if (! $dryRun) {
                $generation->markAsFailed('Automatycznie oznaczono jako nieudane (timeout - brak reakcji przez >3 minuty)');

                Log::warning('Cleaned up stuck AI generation', [
                    'ai_generation_id' => $generation->id,
                    'travel_plan_id' => $generation->travel_plan_id,
                    'age_minutes' => $ageMinutes,
                    'original_status' => $generation->status,
                ]);
            }
        });

        if ($dryRun) {
            $this->info("\nDry run mode - no changes made.");
            $this->info('Run without --dry-run to mark these as failed.');
        } else {
            $this->info("\nMarked {$count} generation(s) as failed.");
        }

        return self::SUCCESS;
    }
}
