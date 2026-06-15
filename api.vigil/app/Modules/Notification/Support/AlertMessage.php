<?php

namespace App\Modules\Notification\Support;

use App\Modules\Incident\Enums\IncidentEvent;
use App\Modules\Incident\Models\Incident;
use App\Modules\Monitor\Models\Monitor;

/**
 * The content of a single alert, rendered to plain text by each channel.
 */
class AlertMessage
{
    public function __construct(
        public readonly Monitor $monitor,
        public readonly IncidentEvent $event,
        public readonly ?string $cause = null,
        public readonly ?Incident $incident = null,
    ) {}

    public function subject(): string
    {
        return match ($this->event) {
            IncidentEvent::DOWN => "🔴 DOWN: {$this->monitor->name}",
            IncidentEvent::STILL_DOWN => "🔴 STILL DOWN: {$this->monitor->name}",
            IncidentEvent::RECOVERED => "✅ RECOVERED: {$this->monitor->name}",
        };
    }

    public function text(): string
    {
        $lines = [
            $this->subject(),
            "Target: {$this->monitor->target}",
        ];

        if ($this->event !== IncidentEvent::RECOVERED && $this->cause !== null) {
            $lines[] = "Cause: {$this->cause}";
        }

        if ($this->incident?->started_at !== null) {
            $lines[] = 'Since: '.$this->incident->started_at->toDayDateTimeString();
        }

        if ($this->event === IncidentEvent::RECOVERED && $this->incident?->duration_seconds !== null) {
            $lines[] = "Downtime: {$this->incident->duration_seconds}s";
        }

        return implode("\n", $lines);
    }
}
