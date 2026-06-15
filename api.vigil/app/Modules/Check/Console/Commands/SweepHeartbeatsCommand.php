<?php

namespace App\Modules\Check\Console\Commands;

use App\Modules\Check\Events\MonitorWentDown;
use App\Modules\Monitor\Enums\CheckOutcome;
use App\Modules\Monitor\Enums\MonitorStatus;
use App\Modules\Monitor\Models\Monitor;
use App\Modules\Monitor\Repositories\CheckResultRepository;
use App\Modules\Monitor\Repositories\MaintenanceWindowRepository;
use App\Modules\Monitor\Repositories\MonitorRepository;
use Illuminate\Console\Command;

class SweepHeartbeatsCommand extends Command
{
    protected $signature = 'heartbeats:sweep';

    protected $description = 'Flag heartbeat monitors that have not pinged within their grace period.';

    private const DEFAULT_GRACE_SECONDS = 300;

    public function handle(
        MonitorRepository $monitors,
        CheckResultRepository $checkResults,
        MaintenanceWindowRepository $maintenanceWindows,
    ): int {
        $flagged = 0;

        foreach ($monitors->findDueHeartbeats() as $monitor) {
            if (! $this->hasMissedPing($monitor) || $monitor->status === MonitorStatus::DOWN) {
                continue;
            }

            $this->flagDown($monitor, $monitors, $checkResults);

            if (! $maintenanceWindows->hasActiveWindowForMonitor($monitor->id)) {
                MonitorWentDown::dispatch($monitor, 'heartbeat_missed');
            }

            $flagged++;
        }

        $this->info("{$flagged} heartbeat(s) flagged down.");

        return self::SUCCESS;
    }

    private function hasMissedPing(Monitor $monitor): bool
    {
        $grace = (int) ($monitor->config['grace_period'] ?? self::DEFAULT_GRACE_SECONDS);
        $deadline = now()->subSeconds($grace);

        return $monitor->last_ping_at === null || $monitor->last_ping_at->lt($deadline);
    }

    private function flagDown(Monitor $monitor, MonitorRepository $monitors, CheckResultRepository $checkResults): void
    {
        $monitor->consecutive_successes = 0;
        $monitor->consecutive_failures++;
        $monitor->status = MonitorStatus::DOWN;

        $monitors->updateMonitorState($monitor->id, [
            'status' => MonitorStatus::DOWN->value,
            'consecutive_failures' => $monitor->consecutive_failures,
            'consecutive_successes' => 0,
            'last_checked_at' => now(),
        ]);

        $checkResults->recordCheckResult([
            'monitor_id' => $monitor->id,
            'checked_at' => now(),
            'result' => CheckOutcome::DOWN->value,
            'error' => 'heartbeat_missed',
            'region' => 'local',
        ]);
    }
}
