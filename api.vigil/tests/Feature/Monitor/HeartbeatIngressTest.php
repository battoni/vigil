<?php

use App\Modules\Check\Events\MonitorRecovered;
use App\Modules\Incident\Models\Incident;
use App\Modules\Monitor\Enums\MonitorStatus;
use App\Modules\Monitor\Models\CheckResult;
use App\Modules\Monitor\Models\Monitor;
use Illuminate\Support\Facades\Event;

it('records a ping, refreshes last_ping_at and keeps the monitor up', function () {
    Event::fake();
    $monitor = Monitor::factory()->heartbeat()->up()->create(['last_ping_at' => now()->subHour()]);

    $response = $this->postJson("/api/heartbeats/{$monitor->heartbeat_token}");

    $response->assertOk()->assertJsonPath('ok', true);
    $monitor->refresh();
    expect($monitor->status)->toBe(MonitorStatus::UP)
        ->and($monitor->last_ping_at->gt(now()->subMinute()))->toBeTrue()
        ->and(CheckResult::where('monitor_id', $monitor->id)->where('result', 'up')->count())->toBe(1);
    Event::assertNotDispatched(MonitorRecovered::class);
});

it('recovers a down heartbeat monitor and fires MonitorRecovered', function () {
    Event::fake();
    $monitor = Monitor::factory()->heartbeat()->down()->create(['last_ping_at' => now()->subHour()]);

    $this->postJson("/api/heartbeats/{$monitor->heartbeat_token}")->assertOk();

    expect($monitor->fresh()->status)->toBe(MonitorStatus::UP);
    Event::assertDispatched(MonitorRecovered::class);
});

it('returns 404 for an unknown heartbeat token', function () {
    $this->postJson('/api/heartbeats/not-a-real-token')->assertNotFound();
});

it('resolves the open incident when a down heartbeat pings (end to end)', function () {
    $monitor = Monitor::factory()->heartbeat()->down()->create(['last_ping_at' => now()->subHour()]);
    $incident = Incident::factory()->create(['monitor_id' => $monitor->id]);

    $this->postJson("/api/heartbeats/{$monitor->heartbeat_token}")->assertOk();

    expect($incident->fresh()->resolved_at)->not->toBeNull();
});
