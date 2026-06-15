<?php

namespace App\Modules\Notification\Jobs;

use App\Modules\Incident\Enums\IncidentEvent;
use App\Modules\Incident\Repositories\IncidentRepository;
use App\Modules\Monitor\Repositories\MonitorRepository;
use App\Modules\Notification\Services\AlertDispatchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Delivers a non-critical alert that was deferred past a quiet-hours window.
 * Fired at the window's end, it bypasses the quiet-hours check so the alert is
 * delivered rather than dropped. See PLAN.md §6.
 */
class SendDeferredAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $monitorId,
        public string $event,
        public ?int $incidentId = null,
        public ?string $cause = null,
    ) {}

    public function handle(
        AlertDispatchService $alertDispatchService,
        MonitorRepository $monitors,
        IncidentRepository $incidents,
    ): void {
        $monitor = $monitors->findMonitorById($this->monitorId);

        if ($monitor === null) {
            return;
        }

        $incident = $this->incidentId !== null ? $incidents->findIncidentById($this->incidentId) : null;

        $alertDispatchService->send($monitor, IncidentEvent::from($this->event), $incident, $this->cause, bypassQuietHours: true);
    }
}
