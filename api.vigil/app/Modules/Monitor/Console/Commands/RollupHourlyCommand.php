<?php

namespace App\Modules\Monitor\Console\Commands;

use App\Modules\Monitor\Services\RollupService;
use Illuminate\Console\Command;

class RollupHourlyCommand extends Command
{
    protected $signature = 'rollup:hourly';

    protected $description = 'Aggregate the previous hour of check_results into monitor_uptime_hourly.';

    public function handle(RollupService $rollupService): int
    {
        $hour = now()->subHour()->startOfHour();
        $rollupService->rollupHour($hour);

        $this->info("Hourly rollup complete for {$hour->toDateTimeString()}.");

        return self::SUCCESS;
    }
}
