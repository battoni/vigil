<?php

namespace App\Modules\Incident\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncidentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'monitorId' => (string) $this->monitor_id,
            'monitorName' => $this->whenLoaded('monitor', fn () => $this->monitor->name),
            'startedAt' => $this->started_at?->toIso8601String(),
            'resolvedAt' => $this->resolved_at?->toIso8601String(),
            'isOpen' => $this->resolved_at === null,
            'cause' => $this->cause,
            'durationSeconds' => $this->duration_seconds,
            'acknowledgedBy' => $this->acknowledged_by ? (string) $this->acknowledged_by : null,
            'acknowledgedAt' => $this->acknowledged_at?->toIso8601String(),
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
