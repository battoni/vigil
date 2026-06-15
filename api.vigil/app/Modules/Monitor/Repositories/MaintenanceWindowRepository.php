<?php

namespace App\Modules\Monitor\Repositories;

use App\Modules\Monitor\Models\MaintenanceWindow;

class MaintenanceWindowRepository
{
    public function __construct(
        private MaintenanceWindow $model
    ) {}

    /**
     * True when a maintenance window (monitor-scoped or global) is active now.
     */
    public function hasActiveWindowForMonitor(int $monitorId): bool
    {
        $now = now();

        return $this->model
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now)
            ->where(function ($query) use ($monitorId) {
                $query->whereNull('monitor_id')->orWhere('monitor_id', $monitorId);
            })
            ->exists();
    }
}
