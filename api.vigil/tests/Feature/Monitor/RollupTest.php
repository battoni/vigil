<?php

use App\Modules\Monitor\Enums\CheckOutcome;
use App\Modules\Monitor\Models\CheckResult;
use App\Modules\Monitor\Models\Monitor;
use App\Modules\Monitor\Models\MonitorUptimeDaily;
use App\Modules\Monitor\Models\MonitorUptimeHourly;
use App\Modules\Monitor\Services\RollupService;
use Illuminate\Support\Carbon;

function rollups(): RollupService
{
    return app(RollupService::class);
}

it('aggregates an hour of raw checks into an hourly bucket', function () {
    $monitor = Monitor::factory()->create();
    $hour = Carbon::parse('2026-06-14 10:00:00');

    // 8 up (response times 10..80), 2 down (null response time) within the hour.
    foreach (range(1, 8) as $index) {
        CheckResult::factory()->create([
            'monitor_id' => $monitor->id,
            'checked_at' => $hour->copy()->addMinutes($index),
            'result' => CheckOutcome::UP,
            'response_time_ms' => $index * 10,
        ]);
    }
    CheckResult::factory()->count(2)->create([
        'monitor_id' => $monitor->id,
        'checked_at' => $hour->copy()->addMinutes(50),
        'result' => CheckOutcome::DOWN,
        'response_time_ms' => null,
    ]);
    // A check in the NEXT hour must be excluded.
    CheckResult::factory()->create([
        'monitor_id' => $monitor->id,
        'checked_at' => $hour->copy()->addHour()->addMinute(),
        'result' => CheckOutcome::UP,
        'response_time_ms' => 999,
    ]);

    rollups()->rollupHour($hour);

    $bucket = MonitorUptimeHourly::where('monitor_id', $monitor->id)->first();
    expect($bucket->checks_total)->toBe(10)
        ->and($bucket->checks_up)->toBe(8)
        ->and((float) $bucket->uptime_ratio)->toBe(0.8)
        ->and($bucket->p50_ms)->toBe(40)   // nearest-rank p50 of 10..80
        ->and($bucket->p95_ms)->toBe(80)
        ->and($bucket->max_ms)->toBe(80);
});

it('is idempotent — re-running the hourly rollup updates the same bucket', function () {
    $monitor = Monitor::factory()->create();
    $hour = Carbon::parse('2026-06-14 11:00:00');
    CheckResult::factory()->create(['monitor_id' => $monitor->id, 'checked_at' => $hour->copy()->addMinute(), 'result' => CheckOutcome::UP, 'response_time_ms' => 50]);

    rollups()->rollupHour($hour);
    rollups()->rollupHour($hour);

    expect(MonitorUptimeHourly::where('monitor_id', $monitor->id)->count())->toBe(1);
});

it('rolls daily buckets up from hourly buckets', function () {
    $monitor = Monitor::factory()->create();
    $day = Carbon::parse('2026-06-14 00:00:00');

    MonitorUptimeHourly::insert([
        ['monitor_id' => $monitor->id, 'bucket_start' => '2026-06-14 09:00:00', 'checks_total' => 60, 'checks_up' => 60, 'uptime_ratio' => 1, 'p50_ms' => 100, 'p95_ms' => 180, 'max_ms' => 200, 'created_at' => now(), 'updated_at' => now()],
        ['monitor_id' => $monitor->id, 'bucket_start' => '2026-06-14 10:00:00', 'checks_total' => 40, 'checks_up' => 30, 'uptime_ratio' => 0.75, 'p50_ms' => 200, 'p95_ms' => 300, 'max_ms' => 500, 'created_at' => now(), 'updated_at' => now()],
        // Next day — must be excluded.
        ['monitor_id' => $monitor->id, 'bucket_start' => '2026-06-15 09:00:00', 'checks_total' => 10, 'checks_up' => 0, 'uptime_ratio' => 0, 'p50_ms' => 50, 'p95_ms' => 50, 'max_ms' => 50, 'created_at' => now(), 'updated_at' => now()],
    ]);

    rollups()->rollupDay($day);

    $daily = MonitorUptimeDaily::where('monitor_id', $monitor->id)->first();
    expect($daily->checks_total)->toBe(100)
        ->and($daily->checks_up)->toBe(90)
        ->and((float) $daily->uptime_ratio)->toBe(0.9)
        ->and($daily->max_ms)->toBe(500)
        ->and($daily->p50_ms)->toBe(140); // weighted: (100*60 + 200*40)/100
});

it('runs the rollup commands successfully', function () {
    $this->artisan('rollup:hourly')->assertSuccessful();
    $this->artisan('rollup:daily')->assertSuccessful();
    $this->artisan('checks:partition-maintenance')->assertSuccessful();
});
