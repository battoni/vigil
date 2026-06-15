<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Vigil check engine — the per-minute tick. The dispatcher only enqueues;
// Horizon workers run the checks concurrently. See PLAN.md §5.
Schedule::command('checks:dispatch')->everyMinute()->withoutOverlapping();
Schedule::command('heartbeats:sweep')->everyMinute()->withoutOverlapping();

// Rollups (status pages read these, never raw) + monthly partition pre-create. §11.
Schedule::command('rollup:hourly')->hourlyAt(5)->withoutOverlapping();
Schedule::command('rollup:daily')->dailyAt('00:30')->withoutOverlapping();
Schedule::command('checks:partition-maintenance')->monthlyOn(1, '02:00')->withoutOverlapping();
