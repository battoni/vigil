<?php

namespace App\Modules\Check\Jobs;

use App\Modules\Check\Events\MonitorRecovered;
use App\Modules\Check\Events\MonitorWentDown;
use App\Modules\Check\Probes\ProbeFactory;
use App\Modules\Check\Services\StateMachineService;
use App\Modules\Check\Support\ProbeResult;
use App\Modules\Monitor\Enums\CheckOutcome;
use App\Modules\Monitor\Enums\MonitorStatus;
use App\Modules\Monitor\Models\Monitor;
use App\Modules\Monitor\Repositories\CheckResultRepository;
use App\Modules\Monitor\Repositories\MaintenanceWindowRepository;
use App\Modules\Monitor\Repositories\MonitorRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class RunCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $monitorId,
    ) {}

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        // One in-flight check per monitor; drop (don't stack) overlaps.
        return [(new WithoutOverlapping((string) $this->monitorId))->dontRelease()->expireAfter(180)];
    }

    public function handle(
        MonitorRepository $monitors,
        CheckResultRepository $checkResults,
        MaintenanceWindowRepository $maintenanceWindows,
        StateMachineService $stateMachine,
        ProbeFactory $probeFactory,
    ): void {
        $monitor = $monitors->findMonitorById($this->monitorId);

        if ($monitor === null || $monitor->status === MonitorStatus::PAUSED) {
            return;
        }

        if (! $probeFactory->supports($monitor->type)) {
            $monitors->updateMonitorState($monitor->id, $this->reschedule($monitor));

            return;
        }

        $result = $probeFactory->for($monitor->type)->run($monitor, $monitor->timeout_ms);

        $this->record($checkResults, $monitor, $result);

        $inMaintenance = $maintenanceWindows->hasActiveWindowForMonitor($monitor->id);
        $transition = $stateMachine->apply($monitor, $result, $inMaintenance);

        $monitors->updateMonitorState($monitor->id, array_merge([
            'status' => $monitor->status->value,
            'consecutive_failures' => $monitor->consecutive_failures,
            'consecutive_successes' => $monitor->consecutive_successes,
            'last_checked_at' => now(),
        ], $this->reschedule($monitor)));

        if (! $transition->shouldAlert()) {
            return;
        }

        if ($transition->isDown()) {
            MonitorWentDown::dispatch($monitor, $transition->cause);

            return;
        }

        MonitorRecovered::dispatch($monitor);
    }

    private function record(CheckResultRepository $checkResults, Monitor $monitor, ProbeResult $result): void
    {
        $checkResults->recordCheckResult([
            'monitor_id' => $monitor->id,
            'checked_at' => now(),
            'result' => ($result->up ? CheckOutcome::UP : CheckOutcome::DOWN)->value,
            'response_time_ms' => $result->responseTimeMs,
            'status_code' => $result->statusCode,
            'error' => $result->error,
            'region' => 'local',
        ]);
    }

    /**
     * Commit the authoritative next check and clear the dispatch lease.
     *
     * @return array<string, mixed>
     */
    private function reschedule(Monitor $monitor): array
    {
        return [
            'next_check_at' => now()->addSeconds($monitor->interval_seconds),
            'leased_until' => null,
        ];
    }
}
