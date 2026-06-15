<?php

namespace App\Modules\Monitor\Services;

use App\Modules\Check\Events\MonitorRecovered;
use App\Modules\Monitor\Enums\CheckOutcome;
use App\Modules\Monitor\Enums\MonitorStatus;
use App\Modules\Monitor\Models\Monitor;
use App\Modules\Monitor\Repositories\CheckResultRepository;
use App\Modules\Monitor\Repositories\MonitorRepository;

/**
 * Ingests push heartbeats. A ping records an up check, refreshes last_ping_at,
 * and — if the monitor was down — transitions it back to UP and fires
 * MonitorRecovered (which resolves the incident and sends the recovery alert).
 */
class HeartbeatService
{
    public function __construct(
        private MonitorRepository $monitorRepository,
        private CheckResultRepository $checkResultRepository,
    ) {}

    public function recordPing(string $token): ?Monitor
    {
        $monitor = $this->monitorRepository->findMonitorByHeartbeatToken($token);

        if ($monitor === null) {
            return null;
        }

        $this->checkResultRepository->recordCheckResult([
            'monitor_id' => $monitor->id,
            'checked_at' => now(),
            'result' => CheckOutcome::UP->value,
            'region' => 'local',
        ]);

        if ($monitor->status === MonitorStatus::PAUSED) {
            $this->monitorRepository->updateMonitorState($monitor->id, ['last_ping_at' => now()]);

            return $monitor;
        }

        $wasDown = $monitor->status === MonitorStatus::DOWN;

        $this->monitorRepository->updateMonitorState($monitor->id, [
            'status' => MonitorStatus::UP->value,
            'consecutive_failures' => 0,
            'consecutive_successes' => $monitor->consecutive_successes + 1,
            'last_ping_at' => now(),
            'last_checked_at' => now(),
        ]);

        if ($wasDown) {
            $monitor->status = MonitorStatus::UP;
            MonitorRecovered::dispatch($monitor);
        }

        return $monitor;
    }
}
