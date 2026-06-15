<?php

namespace App\Modules\Monitor\Resources;

use App\Modules\Monitor\Enums\MonitorType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonitorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isHeartbeat = $this->type === MonitorType::HEARTBEAT;

        return [
            'id' => (string) $this->id,
            'projectId' => (string) $this->project_id,
            'name' => $this->name,
            'type' => $this->type->value,
            'target' => $this->target,
            'config' => $this->config ?? [],
            'intervalSeconds' => $this->interval_seconds,
            'timeoutMs' => $this->timeout_ms,
            'confirmationThreshold' => $this->confirmation_threshold,
            'recoveryThreshold' => $this->recovery_threshold,
            'status' => $this->status->value,
            'consecutiveFailures' => $this->consecutive_failures,
            'consecutiveSuccesses' => $this->consecutive_successes,
            'heartbeatToken' => $this->when($isHeartbeat, $this->heartbeat_token),
            'heartbeatUrl' => $this->when(
                $isHeartbeat && $this->heartbeat_token,
                fn () => url("/api/heartbeats/{$this->heartbeat_token}")
            ),
            'channelIds' => $this->whenLoaded('channels', fn () => $this->channels->pluck('id')->map(fn ($id) => (string) $id)->values()),
            'lastPingAt' => $this->last_ping_at?->toIso8601String(),
            'lastCheckedAt' => $this->last_checked_at?->toIso8601String(),
            'nextCheckAt' => $this->next_check_at?->toIso8601String(),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
