<?php

use App\Modules\Check\Services\StateMachineService;
use App\Modules\Check\Support\ProbeResult;
use App\Modules\Monitor\Enums\MonitorStatus;
use App\Modules\Monitor\Models\Monitor;

function monitorInState(MonitorStatus $status, int $confirmation = 2, int $recovery = 1): Monitor
{
    $monitor = new Monitor;
    $monitor->status = $status;
    $monitor->consecutive_failures = 0;
    $monitor->consecutive_successes = 0;
    $monitor->confirmation_threshold = $confirmation;
    $monitor->recovery_threshold = $recovery;

    return $monitor;
}

it('moves a pending monitor to up silently on first success', function () {
    $monitor = monitorInState(MonitorStatus::PENDING);

    $transition = (new StateMachineService)->apply($monitor, ProbeResult::up(120));

    expect($monitor->status)->toBe(MonitorStatus::UP)
        ->and($transition->transitioned)->toBeFalse();
});

it('only goes down after the confirmation threshold of consecutive failures', function () {
    $service = new StateMachineService;
    $monitor = monitorInState(MonitorStatus::UP, confirmation: 2);

    $first = $service->apply($monitor, ProbeResult::down('timeout'));
    expect($monitor->status)->toBe(MonitorStatus::UP)
        ->and($monitor->consecutive_failures)->toBe(1)
        ->and($first->transitioned)->toBeFalse();

    $second = $service->apply($monitor, ProbeResult::down('timeout'));
    expect($monitor->status)->toBe(MonitorStatus::DOWN)
        ->and($second->isDown())->toBeTrue()
        ->and($second->cause)->toBe('timeout')
        ->and($second->shouldAlert())->toBeTrue();
});

it('recovers after the recovery threshold of successes', function () {
    $monitor = monitorInState(MonitorStatus::DOWN, recovery: 1);

    $transition = (new StateMachineService)->apply($monitor, ProbeResult::up(95));

    expect($monitor->status)->toBe(MonitorStatus::UP)
        ->and($transition->isUp())->toBeTrue()
        ->and($transition->shouldAlert())->toBeTrue();
});

it('resets the opposing counter on each result', function () {
    $service = new StateMachineService;
    $monitor = monitorInState(MonitorStatus::UP, confirmation: 3);

    $service->apply($monitor, ProbeResult::down('e'));
    $service->apply($monitor, ProbeResult::down('e'));
    expect($monitor->consecutive_failures)->toBe(2);

    $service->apply($monitor, ProbeResult::up(50));
    expect($monitor->consecutive_failures)->toBe(0)
        ->and($monitor->consecutive_successes)->toBe(1);
});

it('suppresses the alert during a maintenance window but still transitions', function () {
    $monitor = monitorInState(MonitorStatus::UP, confirmation: 1);

    $transition = (new StateMachineService)->apply($monitor, ProbeResult::down('timeout'), inMaintenance: true);

    expect($monitor->status)->toBe(MonitorStatus::DOWN)
        ->and($transition->transitioned)->toBeTrue()
        ->and($transition->alertSuppressed)->toBeTrue()
        ->and($transition->shouldAlert())->toBeFalse();
});
