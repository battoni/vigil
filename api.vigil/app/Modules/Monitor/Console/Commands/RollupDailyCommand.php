<?php

namespace App\Modules\Monitor\Console\Commands;

use App\Modules\Monitor\Services\RollupService;
use Illuminate\Console\Command;

class RollupDailyCommand extends Command
{
    protected $signature = 'rollup:daily';

    protected $description = 'Aggregate the previous day of hourly rollups into monitor_uptime_daily.';

    public function handle(RollupService $rollupService): int
    {
        $day = now()->subDay()->startOfDay();
        $rollupService->rollupDay($day);

        $this->info("Daily rollup complete for {$day->toDateString()}.");

        return self::SUCCESS;
    }
}
