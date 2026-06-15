<?php

namespace App\Modules\Check\Services;

use App\Modules\Check\Support\ProbeResult;
use App\Modules\Check\Support\StateTransition;
use App\Modules\Monitor\Enums\MonitorStatus;
use App\Modules\Monitor\Models\Monitor;

/**
 * Applies a probe result to a monitor's in-memory state: updates the
 * consecutive success/failure counters and decides UP/DOWN transitions based on
 * the confirmation and recovery thresholds. Persistence is the caller's job.
 */
class StateMachineService
{
    public function apply(Monitor $monitor, ProbeResult $result, bool $inMaintenance = false): StateTransition
    {
        return $result->up
            ? $this->applySuccess($monitor, $inMaintenance)
            : $this->applyFailure($monitor, $result, $inMaintenance);
    }

    private function applySuccess(Monitor $monitor, bool $inMaintenance): StateTransition
    {
        $monitor->consecutive_failures = 0;
        $monitor->consecutive_successes++;

        $confirmsRecovery = $monitor->consecutive_successes >= $monitor->recovery_threshold;

        if ($monitor->status === MonitorStatus::DOWN && $confirmsRecovery) {
            $monitor->status = MonitorStatus::UP;

            return StateTransition::recovered($inMaintenance);
        }

        if ($monitor->status === MonitorStatus::PENDING) {
            // First confirmed health — establish baseline silently.
            $monitor->status = MonitorStatus::UP;
        }

        return StateTransition::none();
    }

    private function applyFailure(Monitor $monitor, ProbeResult $result, bool $inMaintenance): StateTransition
    {
        $monitor->consecutive_successes = 0;
        $monitor->consecutive_failures++;

        $confirmsDown = $monitor->consecutive_failures >= $monitor->confirmation_threshold;
        $canGoDown = in_array($monitor->status, [MonitorStatus::UP, MonitorStatus::PENDING], true);

        if ($canGoDown && $confirmsDown) {
            $monitor->status = MonitorStatus::DOWN;

            return StateTransition::wentDown($result->error ?? 'down', $inMaintenance);
        }

        return StateTransition::none();
    }
}
