<?php

namespace App\Modules\StatusPage\Resources;

use App\Modules\Monitor\Enums\MonitorStatus;
use App\Modules\Monitor\Services\UptimeQueryService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class PublicStatusResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $uptimeService = app(UptimeQueryService::class);
        $monitors = $this->monitors;

        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'branding' => $this->branding ?? [],
            'overallStatus' => $this->overallStatus($monitors),
            'groups' => $this->groupMonitors($monitors, $uptimeService),
        ];
    }

    private function overallStatus(Collection $monitors): string
    {
        $hasDown = $monitors->contains(fn ($monitor) => $monitor->status === MonitorStatus::DOWN);

        return $hasDown ? 'degraded' : 'operational';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function groupMonitors(Collection $monitors, UptimeQueryService $uptimeService): array
    {
        return $monitors
            ->groupBy(fn ($monitor) => $monitor->pivot->group_name ?? 'Ungrouped')
            ->map(fn (Collection $grouped, string $groupName) => [
                'name' => $groupName,
                'monitors' => $grouped->map(fn ($monitor) => [
                    'name' => $monitor->name,
                    'status' => $monitor->status->value,
                    'uptime' => $uptimeService->uptimeForMonitor($monitor->id),
                ])->values(),
            ])
            ->values()
            ->all();
    }
}
