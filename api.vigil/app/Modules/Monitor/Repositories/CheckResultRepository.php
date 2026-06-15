<?php

namespace App\Modules\Monitor\Repositories;

use App\Modules\Monitor\Models\CheckResult;
use Illuminate\Support\Carbon;

class CheckResultRepository
{
    public function __construct(
        private CheckResult $model
    ) {}

    public function recordCheckResult(array $data): CheckResult
    {
        return $this->model->create($data);
    }

    /**
     * @return CheckResult[]
     */
    public function findRecentForMonitor(int $monitorId, Carbon $since): array
    {
        return $this->model
            ->where('monitor_id', $monitorId)
            ->where('checked_at', '>=', $since)
            ->orderByDesc('checked_at')
            ->get()
            ->all();
    }
}
