<?php

use App\Models\User;
use App\Modules\Monitor\Models\CheckResult;
use App\Modules\Monitor\Models\Monitor;
use App\Modules\Monitor\Models\MonitorUptimeDaily;
use App\Modules\Monitor\Models\MonitorUptimeHourly;

function metricsUser(): User
{
    return User::factory()->create();
}

it('returns uptime percentages from rollups', function () {
    $monitor = Monitor::factory()->create();
    MonitorUptimeHourly::insert([
        'monitor_id' => $monitor->id, 'bucket_start' => now()->subHour()->startOfHour(),
        'checks_total' => 100, 'checks_up' => 99, 'uptime_ratio' => 0.99, 'p50_ms' => 100, 'p95_ms' => 150, 'max_ms' => 200,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $response = $this->actingAs(metricsUser())->getJson("/api/monitors/{$monitor->id}/uptime");

    $response->assertOk()->assertJsonPath('data.24h', 99);
});

it('returns recent raw checks', function () {
    $monitor = Monitor::factory()->create();
    CheckResult::factory()->count(3)->create(['monitor_id' => $monitor->id, 'checked_at' => now()->subMinutes(5)]);
    CheckResult::factory()->create(['monitor_id' => $monitor->id, 'checked_at' => now()->subDays(3)]); // outside default 24h

    $response = $this->actingAs(metricsUser())->getJson("/api/monitors/{$monitor->id}/checks");

    $response->assertOk()->assertJsonCount(3, 'data');
    expect($response->json('data.0'))->toHaveKeys(['result', 'responseTimeMs', 'checkedAt']);
});

it('returns an hourly series for the 24h range', function () {
    $monitor = Monitor::factory()->create();
    MonitorUptimeHourly::insert([
        ['monitor_id' => $monitor->id, 'bucket_start' => now()->subHours(2)->startOfHour(), 'checks_total' => 60, 'checks_up' => 60, 'uptime_ratio' => 1, 'p50_ms' => 100, 'p95_ms' => 150, 'max_ms' => 200, 'created_at' => now(), 'updated_at' => now()],
        ['monitor_id' => $monitor->id, 'bucket_start' => now()->subHour()->startOfHour(), 'checks_total' => 60, 'checks_up' => 54, 'uptime_ratio' => 0.9, 'p50_ms' => 120, 'p95_ms' => 200, 'max_ms' => 400, 'created_at' => now(), 'updated_at' => now()],
    ]);

    $response = $this->actingAs(metricsUser())->getJson("/api/monitors/{$monitor->id}/uptime-series?range=24h");

    $response->assertOk()->assertJsonCount(2, 'data');
    expect($response->json('data.0'))->toHaveKeys(['bucketStart', 'uptimeRatio', 'p50Ms']);
});

it('returns a daily series for the 30d range', function () {
    $monitor = Monitor::factory()->create();
    MonitorUptimeDaily::insert([
        'monitor_id' => $monitor->id, 'bucket_start' => now()->subDays(3)->startOfDay(),
        'checks_total' => 1440, 'checks_up' => 1440, 'uptime_ratio' => 1, 'p50_ms' => 110, 'p95_ms' => 160, 'max_ms' => 210,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $response = $this->actingAs(metricsUser())->getJson("/api/monitors/{$monitor->id}/uptime-series?range=30d");

    $response->assertOk()->assertJsonCount(1, 'data');
});

it('rejects unauthenticated metrics requests and unknown monitors', function () {
    $monitor = Monitor::factory()->create();

    $this->getJson("/api/monitors/{$monitor->id}/uptime")->assertUnauthorized();
    $this->actingAs(metricsUser())->getJson('/api/monitors/999999/uptime')->assertNotFound();
});
