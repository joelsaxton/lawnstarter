<?php

namespace App\Console\Commands;

use App\Jobs\CalculateStarWarsApiStats;
use Illuminate\Console\Command;

/**
 * This command runs on a schedule and invokes the CalculateStarWarsApiStats
 * queueable job to update and cache Star Wars API statistics.
 */
class CalculateStarWarsApiStatsCommand extends Command
{
    protected $signature = 'stats:calculate-star-wars-api';
    protected $description = 'Calculate Star Wars API statistics and cache the results';

    public function handle(): int
    {
        $this->info('Calculating Star Wars API statistics...');

        CalculateStarWarsApiStats::dispatch();

        $this->info('Job dispatched successfully!');

        return Command::SUCCESS;
    }
}
