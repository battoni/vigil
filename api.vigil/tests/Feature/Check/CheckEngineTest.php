<?php

use App\Modules\Check\Events\MonitorRecovered;
use App\Modules\Check\Events\MonitorWentDown;
use App\Modules\Check\Jobs\RunCheckJob;
use App\Modules\Check\Support\SsrfGuard;
use App\Modules\Monitor\Enums\MonitorStatus;
use App\Modules\Monitor\Models\CheckResult;
use App\Modules\Monitor\Models\MaintenanceWindow;
use App\Modules\Monitor\Models\Monitor;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    // Pin DNS resolution to a public IP so probes never hit the network.
    $this->app->instance(SsrfGuard::class, new SsrfGuard(fn () => ['203.0.113.10']));
});

// checks:dispatch -----------------------------------------------------------

it('dispatches a RunCheckJob and leases due monitors', function () {
    Queue::fake();
    $monitor = Monitor::factory()->up()->create(['next_check_at' => now()->subMinute()]);

    $this->artisan('checks:dispatch')->assertSuccessful();

    Queue::assertPushed(RunCheckJob::class, fn (RunCheckJob $job) => $job->monitorId === $monitor->id);
    expect($monitor->fresh()->next_check_at->isFuture())->toBeTrue();
});

it('does not dispatch paused, heartbeat or not-yet-due monitors', function () {
    Queue::fake();
    Monitor::factory()->paused()->create();
    Monitor::factory()->heartbeat()->create();
    Monitor::factory()->up()->create(['next_check_at' => now()->addMinutes(5)]);

    $this->artisan('checks:dispatch')->assertSuccessful();

    Queue::assertNothingPushed();
});

// RunCheckJob ---------------------------------------------------------------

it('records a healthy check and keeps the monitor up', function () {
    Event::fake();
    Http::fake(['*' => Http::response('ok', 200)]);
    $monitor = Monitor::factory()->up()->create();

    RunCheckJob::dispatchSync($monitor->id);

    $monitor->refresh();
    expect($monitor->status)->toBe(MonitorStatus::UP)
        ->and($monitor->next_check_at->isFuture())->toBeTrue()
        ->and($monitor->leased_until)->toBeNull()
        ->and(CheckResult::where('monitor_id', $monitor->id)->where('result', 'up')->count())->toBe(1);
    Event::assertNotDispatched(MonitorWentDown::class);
});

it('transitions to down and fires MonitorWentDown after confirmation', function () {
    Event::fake();
    Http::fake(['*' => Http::response('', 500)]);
    $monitor = Monitor::factory()->up()->create(['confirmation_threshold' => 1]);

    RunCheckJob::dispatchSync($monitor->id);

    expect($monitor->fresh()->status)->toBe(MonitorStatus::DOWN);
    Event::assertDispatched(MonitorWentDown::class);
});

it('transitions to up and fires MonitorRecovered', function () {
    Event::fake();
    Http::fake(['*' => Http::response('ok', 200)]);
    $monitor = Monitor::factory()->down()->create(['recovery_threshold' => 1]);

    RunCheckJob::dispatchSync($monitor->id);

    expect($monitor->fresh()->status)->toBe(MonitorStatus::UP);
    Event::assertDispatched(MonitorRecovered::class);
});

it('suppresses the alert during a maintenance window but records the check', function () {
    Event::fake();
    Http::fake(['*' => Http::response('', 500)]);
    $monitor = Monitor::factory()->up()->create(['confirmation_threshold' => 1]);
    MaintenanceWindow::factory()->create(['monitor_id' => $monitor->id]);

    RunCheckJob::dispatchSync($monitor->id);

    expect($monitor->fresh()->status)->toBe(MonitorStatus::DOWN);
    Event::assertNotDispatched(MonitorWentDown::class);
    expect(CheckResult::where('monitor_id', $monitor->id)->count())->toBe(1);
});

it('skips a paused monitor without recording a check', function () {
    Http::fake(['*' => Http::response('ok', 200)]);
    $monitor = Monitor::factory()->paused()->create();

    RunCheckJob::dispatchSync($monitor->id);

    expect(CheckResult::where('monitor_id', $monitor->id)->count())->toBe(0);
});

// heartbeats:sweep ----------------------------------------------------------

it('flags a heartbeat monitor that missed its grace period', function () {
    Event::fake();
    $monitor = Monitor::factory()->heartbeat()->up()->create([
        'last_ping_at' => now()->subMinutes(10),
        'config' => ['grace_period' => 300],
    ]);

    $this->artisan('heartbeats:sweep')->assertSuccessful();

    expect($monitor->fresh()->status)->toBe(MonitorStatus::DOWN)
        ->and(CheckResult::where('monitor_id', $monitor->id)->count())->toBe(1);
    Event::assertDispatched(MonitorWentDown::class);
});

it('leaves a recently-pinged heartbeat monitor untouched', function () {
    Event::fake();
    $monitor = Monitor::factory()->heartbeat()->up()->create([
        'last_ping_at' => now()->subSeconds(30),
        'config' => ['grace_period' => 300],
    ]);

    $this->artisan('heartbeats:sweep')->assertSuccessful();

    expect($monitor->fresh()->status)->toBe(MonitorStatus::UP);
    Event::assertNotDispatched(MonitorWentDown::class);
});
