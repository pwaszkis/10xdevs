<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\TravelPlan;
use Illuminate\Console\Command;

class AutoCompletePastTrips extends Command
{
    protected $signature = 'plans:auto-complete';

    protected $description = 'Automatically mark past trips as completed';

    public function handle(): int
    {
        $endDate = now()->subDay();

        $updated = TravelPlan::where('status', 'planned')
            ->whereRaw('DATE_ADD(departure_date, INTERVAL number_of_days DAY) < ?', [$endDate])
            ->update(['status' => 'completed']);

        $this->info("Marked {$updated} past trips as completed.");

        return Command::SUCCESS;
    }
}
